<?php
// ปิดการแสดง error โดยตรงกับผู้ใช้ แต่ให้บันทึกเป็น log แทน
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/php_error.log');

// --- การตั้งค่าหลัก ---
define('MAX_RETENTION_DAYS', 30); // Max history files retention in days
$settings_file = __DIR__ . '/../data/settings.json';
$data_directory = __DIR__ . '/../data/';
$file_prefix = 'graph_history_';
$file_extension = '.jsonl';

// --- ฟังก์ชันช่วยเหลือ ---
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * ฟังก์ชันสำหรับคำนวณสัปดาห์ในรูปแบบ YYYY-WXX
 * เช่น 2025-W05 สำหรับสัปดาห์ที่ 5 ของปี 2025
 */
function get_week_format($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date('Y-\\WW', $timestamp);
}

/**
 * Deletes history files older than MAX_RETENTION_DAYS.
 * Note: This compares the file's week to the cutoff week.
 */
function cleanup_old_history_files($data_dir, $prefix, $ext) {
    $files = glob($data_dir . $prefix . '*' . $ext);
    // Cutoff date based on weeks
    $cutoff_timestamp = strtotime('-' . MAX_RETENTION_DAYS . ' days');

    foreach ($files as $file) {
        if (!is_file($file)) continue;

        // Extract YYYY-WXX from filename
        $filename = basename($file, $ext);
        $date_part = str_replace($prefix, '', $filename);
        
        // Parse week format YYYY-WXX
        if (preg_match('/^(\d{4})-W(\d{2})$/', $date_part, $matches)) {
            $year = intval($matches[1]);
            $week = intval($matches[2]);
            
            // Convert week to timestamp (Monday of that week)
            $week_timestamp = strtotime("$year-01-01 +".($week-1)." weeks");
            // Adjust to Monday of the week
            $week_timestamp = strtotime('monday this week', $week_timestamp);
            
            if ($week_timestamp < $cutoff_timestamp) {
                @unlink($file);
            }
        }
    }
}

// --- การทำงานหลัก ---
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Clean up old files before serving data
        cleanup_old_history_files($data_directory, $file_prefix, $file_extension);
        handle_get_request($data_directory, $settings_file, $file_prefix, $file_extension);
        break;
    case 'POST':
        handle_post_request($data_directory, $file_prefix, $file_extension);
        break;
    default:
        send_json_response(['error' => 'Method Not Allowed'], 405);
        break;
}

/**
 * จัดการการอ่านข้อมูลจากไฟล์รายสัปดาห์
 */
function handle_get_request($data_dir, $settings_path, $prefix, $ext) {
    $retention_hours = 48; // ค่า default
    if (file_exists($settings_path)) {
        $config = json_decode(file_get_contents($settings_path), true);
        if ($config && isset($config['retentionHours'])) {
            $retention_hours = (int)$config['retentionHours'];
        }
    }
    
    $retention_seconds = $retention_hours * 3600;
    $now_timestamp = time();
    $cutoff_timestamp = $now_timestamp - $retention_seconds;
    
    $history_data = [];

    // หาไฟล์ที่ต้องอ่าน (รายสัปดาห์)
    $files_to_read = [];
    $weeks_to_check = ceil($retention_hours / 24 / 7) + 2; // เพิ่ม buffer 2 สัปดาห์
    
    for ($i = 0; $i <= $weeks_to_check; $i++) {
        $date_to_check = strtotime("-{$i} week", $now_timestamp);
        $week_format = get_week_format($date_to_check);
        $filename = $data_dir . $prefix . $week_format . $ext;
        if (file_exists($filename)) {
            $files_to_read[] = $filename;
        }
    }
    $files_to_read = array_unique($files_to_read);

    // อ่านข้อมูลจากไฟล์ที่เกี่ยวข้อง
    foreach ($files_to_read as $filepath) {
        $handle = @fopen($filepath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) === '') continue;

                $record = json_decode($line, true);
                if ($record && isset($record['data']['x'])) {
                    $point_timestamp = strtotime($record['data']['x']);
                    
                    if ($point_timestamp >= $cutoff_timestamp) {
                        $key = $record['key'];
                        if (!isset($history_data[$key])) {
                            $history_data[$key] = [];
                        }
                        $history_data[$key][] = $record['data'];
                    }
                }
            }
            fclose($handle);
        }
    }

    send_json_response($history_data);
}

/**
 * จัดการการเขียนข้อมูลลงไฟล์รายสัปดาห์
 */
function handle_post_request($data_dir, $prefix, $ext) {
    $input_json = file_get_contents('php://input');
    $input_data = json_decode($input_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
         send_json_response(['error' => 'Invalid data format provided.'], 400);
    }

    // Action: ล้างข้อมูลประวัติทั้งหมด
    if (isset($input_data['action']) && $input_data['action'] === 'clear_history') {
        $files = glob($data_dir . $prefix . '*' . $ext);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        send_json_response(['success' => true, 'message' => 'All graph history has been cleared.']);
        return;
    }
    
    // Action: เพิ่มข้อมูลใหม่
    if (isset($input_data['jsonKey']) && isset($input_data['dataPoint'])) {
        // เปลี่ยนจากรายเดือน (Y-m) เป็นรายสัปดาห์ (Y-WXX)
        $current_file = $data_dir . $prefix . get_week_format() . $ext;
        
        $json_key = $input_data['jsonKey'];
        $data_point = $input_data['dataPoint'];

        $record = ['key' => $json_key, 'data' => $data_point];
        $line_to_append = json_encode($record) . "\n";

        if (file_put_contents($current_file, $line_to_append, FILE_APPEND | LOCK_EX) !== false) {
            send_json_response(['success' => true]);
        } else {
            send_json_response(['error' => 'Could not write to history file. Check permissions for the /data/ folder.'], 500);
        }
        return;
    }
    
    send_json_response(['error' => 'Invalid action or data provided.'], 400);
}
?>