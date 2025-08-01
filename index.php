<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Dashboard (PHP Version)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-black text-white">
    
    <nav class="bg-gray-900 p-4 shadow-lg">
        <div class="container mx-auto flex justify-center items-center gap-2 sm:gap-4">
            <button data-view="dashboard" class="nav-btn px-4 py-2 rounded-lg text-sm sm:text-base active">Dashboard</button>
            <button data-view="graph" class="nav-btn px-4 py-2 rounded-lg text-sm sm:text-base">Graph</button>
            <button data-view="status" class="nav-btn px-4 py-2 rounded-lg text-sm sm:text-base">Device Status</button>
            <button data-view="settings" class="nav-btn px-4 py-2 rounded-lg text-sm sm:text-base">Settings</button>
        </div>
    </nav>

    <div id="main-content-wrapper">
        <div id="view-dashboard" class="view active dashboard-background">
            <div class="dashboard-overlay"></div>
            <div class="dashboard-content">
                <div class="dashboard-ui-elements">
                    <header id="dashboard-header" style="background-size: cover; background-position: center;">
                        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                            <div class="flex items-center gap-6 h-24">
                                <img id="dashboard-logo" src="https://placehold.co/200x200/transparent/white?text=Logo" alt="Logo" class="h-full w-auto object-contain flex-shrink-0">
                                <div class="flex-grow">
                                    <h2 id="header-caption" class="text-2xl font-bold text-white"></h2>
                                    <p id="header-subcaption" class="text-lg text-gray-300"></p>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
                <div class="dashboard-main-area">
                    <div class="w-full">
                         <div id="operation-mode-container" class="bg-gray-800 bg-opacity-70 p-3 my-4 rounded-lg border border-gray-600 flex justify-center items-center gap-6">
                            <h3 class="text-white font-bold">OPERATION MODE</h3>
                           <div id="operation-mode-lights" class="flex gap-4"></div>
                        </div>
                    </div>
                    <main class="w-full">
                        <div id="dashboard-graph-content" class="dashboard-main-content active">
                            <canvas id="dashboardBarChart"></canvas>
                        </div>
                        <div id="dashboard-video-content" class="dashboard-main-content">
                            <video id="dashboard-video" controls autoplay muted loop></video>
                        </div>
                        <div id="dashboard-image-content" class="dashboard-main-content">
                            <img id="dashboard-image" alt="Dashboard Image">
                        </div>
                        <div id="dashboard-youtube-content" class="dashboard-main-content">
                            <iframe id="dashboard-youtube-iframe" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </main>
                    <footer class="w-full">
                        <div id="dashboard-values-container" class="flex justify-around text-white text-center py-2"></div>
                    </footer>
                </div>
            </div>
        </div>

        <div id="view-graph" class="view">
            <div class="container mx-auto p-4 sm:p-6 lg:p-8">
                <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                    <div><h1 id="graph-title" class="text-3xl lg:text-4xl font-bold">Real-time Graph</h1></div>
                    <button id="reset-zoom-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg mt-4 sm:mt-0">Reset View</button>
                </header>
                
                <section class="bg-gray-900 p-4 rounded-lg mb-4"><h3 class="text-lg font-semibold mb-3">Select parameters to display:</h3><div id="graph-param-checkboxes" class="flex flex-wrap gap-x-6 gap-y-2"></div></section>
                
                <main class="bg-gray-900 p-4 sm:p-6 rounded-lg">
                    <div style="height: 400px;"><canvas id="mainChart"></canvas></div>
                    <div id="brush-chart-container" class="mt-4">
                        <canvas id="brushChart"></canvas>
                        <div id="brush">
                            <div class="brush-handle left"></div>
                            <div class="brush-handle right"></div>
                        </div>
                    </div>
                </main>
                
                <section class="mt-8">
                    <h2 class="text-xl font-bold mb-4">Summary (for selected time range)</h2>
                    <div id="graph-stats-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    </div>
                </section>

                <section class="mt-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Data Log (latest 200 entries)</h2>
                        <button id="export-csv-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                            Export as CSV
                        </button>
                    </div>
                    <div class="bg-gray-900 p-1 rounded-lg overflow-x-auto">
                        <table id="history-table" class="w-full text-sm text-left text-gray-400">
                            <thead class="text-xs text-gray-300 uppercase bg-gray-800 sticky top-0 z-10">
                                </thead>
                            <tbody id="history-table-body">
                                </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div id="view-status" class="view">
             <div class="container mx-auto p-4 sm:p-6 lg:p-8">
                <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                    <div><h1 class="text-4xl lg:text-5xl font-bold">System Status</h1></div>
                    <div class="text-right mt-4 sm:mt-0">
                        <p id="status-current-time" class="text-2xl lg:text-3xl font-bold"></p>
                        <p id="status-current-date" class="text-base text-gray-300"></p>
                    </div>
                </header>
                
                <main class="space-y-8">
                    <section class="bg-gray-900 p-6 rounded-xl">
                        <h2 class="text-xl font-bold text-yellow-400 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            Sensor Status
                        </h2>
                        <div id="sensor-status-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            </div>
                    </section>

                    <section class="bg-gray-900 p-6 rounded-xl">
                        <h2 class="text-xl font-bold text-yellow-400 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            System Status
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="status-card bg-gray-800 p-4 rounded-lg">
                                <h3 class="text-gray-400 text-sm">Connection</h3>
                                <div id="system-connection-status" class="text-2xl font-bold mt-1">Connecting...</div>
                            </div>
                            <div class="status-card bg-gray-800 p-4 rounded-lg">
                                <h3 class="text-gray-400 text-sm">Last Data</h3>
                                <div id="system-last-data-time" class="text-2xl font-bold mt-1">N/A</div>
                            </div>
                            <div class="status-card bg-gray-800 p-4 rounded-lg">
                                <h3 class="text-gray-400 text-sm">Data Count (Session)</h3>
                                <div id="system-data-count" class="text-2xl font-bold mt-1">0</div>
                            </div>
                            <div class="status-card bg-gray-800 p-4 rounded-lg">
                                <h3 class="text-gray-400 text-sm">Uptime</h3>
                                <div id="system-uptime" class="text-2xl font-bold mt-1">0s</div>
                            </div>
                        </div>
                    </section>
                </main>
             </div>
        </div>

        <div id="view-settings" class="view">
            <div class="container mx-auto p-4 sm:p-6 lg:p-8">
                 <div class="bg-gray-100 text-gray-800 rounded-xl shadow-lg" id="settings-form-container">
                    <div class="p-8 pb-4 flex-shrink-0">
                        <button id="app-version-btn" class="absolute top-4 right-4 text-xs text-gray-400 hover:text-gray-600 underline focus:outline-none"></button>
                        <div class="text-center"><h1 class="text-2xl font-bold mb-2">Display Settings</h1><p class="text-gray-500">Configure connection, parameters, and display options</p></div>
                    </div>

                    <div class="px-8 space-y-6 pb-8">
                        <div id="localStorage-warning" class="hidden p-4 text-sm text-yellow-800 rounded-lg bg-yellow-50" role="alert">
                            <span class="font-medium">Browser storage is unavailable!</span> Settings will be temporary.
                        </div>
                       <!-- แก้ไข tooltip ใน index.php บรรทัดที่ 308 -->
<div class="pt-6 border-t">
    <h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">
        Connection & Data Settings
        <span class="tooltip-trigger">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="tooltip-content">
                - Data Source URL: The web address (API endpoint) where the dashboard fetches its data.<br>
                - Refresh every: How often (in seconds) the dashboard should request new data.<br>
                - Data Retention: How far back (in hours) the graph should display historical data from the server.
            </span>
        </span>
    </h3>
    <div class="space-y-4">
        <div><label for="settings-api-url" class="block text-sm font-medium mb-2">Data Source URL</label><input type="url" id="settings-api-url" class="settings-input"></div>
        <div><label for="settings-interval" class="block text-sm font-medium mb-2">Refresh every (seconds)</label><input type="number" id="settings-interval" min="1" class="settings-input"></div>
        <div>
            <label for="settings-retention" class="block text-sm font-medium mb-2">Data Retention (hours)</label>
            <input type="number" id="settings-retention" min="1" class="settings-input">
            <p id="storage-estimate" class="text-xs text-gray-500 mt-1 h-4"></p>
        </div>
    </div>
</div>
                        <div class="pt-6 border-t">
                            <h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Dashboard Appearance
                                <span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">Customize the visual elements of the dashboard header and backgrounds. You can toggle the visibility of each image.</span></span>
                            </h3>
                            <div class="space-y-6">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">Dashboard Padding (%)</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div><input type="number" id="settings-padding-top" class="settings-input" placeholder="Top"></div>
                                        <div><input type="number" id="settings-padding-right" class="settings-input" placeholder="Right"></div>
                                        <div><input type="number" id="settings-padding-bottom" class="settings-input" placeholder="Bottom"></div>
                                        <div><input type="number" id="settings-padding-left" class="settings-input" placeholder="Left"></div>
                                    </div>
                                </div>
                                <div>
                                    <label for="settings-header-caption" class="block text-sm font-medium mb-2">Header Caption</label>
                                    <input type="text" id="settings-header-caption" class="settings-input">
                                </div>
                                <div>
                                    <label for="settings-header-caption-fontsize" class="block text-sm font-medium mb-2">Header Caption Font Size (px)</label>
                                    <input type="number" id="settings-header-caption-fontsize" class="settings-input">
                                </div>
                                <div>
                                    <label for="settings-header-subcaption" class="block text-sm font-medium mb-2">Header Sub-caption</label>
                                    <input type="text" id="settings-header-subcaption" class="settings-input">
                                </div>
                                <div>
                                    <label for="settings-header-subcaption-fontsize" class="block text-sm font-medium mb-2">Header Sub-caption Font Size (px)</label>
                                    <input type="number" id="settings-header-subcaption-fontsize" class="settings-input">
                                </div>

                                <div class="pt-4 border-t"></div>

                                <div class="flex items-center">
                                    <input id="settings-show-logo" type="checkbox" class="h-4 w-4 rounded">
                                    <label for="settings-show-logo" class="ml-2 block text-sm font-medium">Show Logo</label>
                                </div>
                                <div id="logo-picker-container"></div>
                                <p class="text-xs text-gray-500 mt-1">แนะนำ: 200x200px (ภาพจะถูกปรับขนาดให้พอดี)</p>

                                <div class="pt-4 border-t"></div>

                                <div class="flex items-center">
                                    <input id="settings-show-header-bg" type="checkbox" class="h-4 w-4 rounded">
                                    <label for="settings-show-header-bg" class="ml-2 block text-sm font-medium">Show Header Background Image</label>
                                </div>
                                <div id="header-bg-picker-container"></div>
                                <p class="text-xs text-gray-500 mt-1">แนะนำ: 1920x200px (แนวนอนกว้าง)</p>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Header Background Color (Fallback)</label>
                                    <input type="color" id="settings-header-bg-color">
                                </div>

                                <div class="pt-4 border-t"></div>

                                <div class="flex items-center">
                                    <input id="settings-show-main-bg" type="checkbox" class="h-4 w-4 rounded">
                                    <label for="settings-show-main-bg" class="ml-2 block text-sm font-medium">Show Main Background Image</label>
                                </div>
                                <div id="main-bg-picker-container"></div>
                                <p class="text-xs text-gray-500 mt-1">แนะนำ: 1920x1080px (สัดส่วน 16:9)</p>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Main Background Color (Fallback)</label>
                                    <input type="color" id="settings-main-bg-color">
                                </div>
                            </div>
                        </div>
                        <div class="pt-6 border-t"><h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Dashboard Content Rotation<span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">Automatically rotate between graph, video, and image on the dashboard.<br>- Enable/disable the rotation.<br>- Drag to reorder.<br>- Set duration for each view.<br>- Set sources for video and image.</span></span></h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input id="settings-rotation-enabled" type="checkbox" class="h-4 w-4 rounded">
                                    <label for="settings-rotation-enabled" class="ml-2 block text-sm font-medium">Enable Content Rotation</label>
                                </div>
                                <div id="settings-rotation-list" class="space-y-3">
                                    </div>
                                <div id="add-slide-buttons" class="flex gap-2">
                                    <button data-type="graph" class="add-slide-btn text-sm bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">+ Graph</button>
                                    <button data-type="video" class="add-slide-btn text-sm bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">+ Video</button>
                                    <button data-type="image" class="add-slide-btn text-sm bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">+ Image</button>
                                </div>
                            </div>
                        </div>
                        <div class="pt-6 border-t"><h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Dashboard Bar Chart Styling<span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">Fine-tune the colors and font sizes for all elements in the main dashboard bar chart.</span></span></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium mb-2">Bar Color</label><input type="color" id="settings-bar-color"></div>
                                <div><label class="block text-sm font-medium mb-2">Grid Line Color</label><input type="color" id="settings-grid-color"></div>
                                <div><label class="block text-sm font-medium mb-2">Range Text Color</label><input type="color" id="settings-bar-range-text-color"></div>
                                <div><label class="block text-sm font-medium mb-2">Label Text Color</label><input type="color" id="settings-bar-label-text-color"></div>
                                <div><label class="block text-sm font-medium mb-2">Value Text Color</label><input type="color" id="settings-bar-value-text-color"></div>
                                <div><label class="block text-sm font-medium mb-2">Unit Text Color</label><input type="color" id="settings-bar-unit-text-color"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                <div><label class="block text-sm font-medium mb-2">Grid Line Width (px)</label><input type="number" id="settings-grid-linewidth" class="settings-input"></div>
                                <div><label class="block text-sm font-medium mb-2">Range Font Size (px)</label><input type="number" id="settings-bar-range-font-size" class="settings-input"></div>
                                <div><label class="block text-sm font-medium mb-2">Label Font Size (px)</label><input type="number" id="settings-bar-label-font-size" class="settings-input"></div>
                                <div><label class="block text-sm font-medium mb-2">Value Font Size (px)</label><input type="number" id="settings-bar-value-font-size" class="settings-input"></div>
                                <div><label class="block text-sm font-medium mb-2">Unit Font Size (px)</label><input type="number" id="settings-bar-unit-font-size" class="settings-input"></div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Value/Label Font Weight</label>
                                    <select id="settings-value-font-weight" class="settings-input">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Range Font Weight</label>
                                    <select id="settings-range-font-weight" class="settings-input">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Unit Font Weight</label>
                                    <select id="settings-unit-font-weight" class="settings-input">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="pt-6 border-t"><h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Operation Mode Settings<span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">- Mode Status JSON Key: The name of the variable in your data source that indicates the current operation mode.<br>- Mode Values: Define the exact value from your data that corresponds to each mode name. This links the data to the status lights on the dashboard.</span></span></h3>
                            <div class="space-y-4">
                                <div><label for="settings-mode-key" class="block text-sm font-medium mb-2">Mode Status JSON Key</label><input type="text" id="settings-mode-key" class="settings-input"></div>
                                <p class="text-sm text-gray-600">Define the value for each mode that triggers the light.</p>
                                <div id="settings-mode-values" class="space-y-2">
                                    </div>
                            </div>
                        </div>
                        <div class="pt-6 border-t"><h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Proxy Settings (Managed by Server)<span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">Proxy is now handled by the PHP backend. All data requests from this dashboard are automatically routed through the server to prevent CORS issues. There are no client-side proxy settings to configure.</span></span></h3><div class="space-y-4"><div class="flex items-center p-3 bg-indigo-100 text-indigo-800 rounded-lg"><svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg><p>Proxy is enabled and managed by the server.</p></div></div></div>
                        <div class="pt-6 border-t"><h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">Test & Refresh <span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">- Test: Fetches data from the URL to check if the connection is working and displays the raw response.<br>- Refresh: Immediately fetches new data and updates the entire dashboard, without waiting for the next refresh interval.</span></span></h3><div class="flex gap-4"><button id="settings-test-connection-btn" class="w-1/2 px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg">Test</button><button id="settings-refresh-data-btn" class="w-1/2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg">Refresh</button></div><div class="mt-2"><pre id="settings-raw-result" class="bg-gray-800 text-white p-2 rounded-md text-xs whitespace-pre-wrap break-all h-24 overflow-y-auto"></pre></div></div>
                        
                        <div class="pt-6 border-t">
                            <h3 class="flex items-center text-lg font-semibold text-gray-700 mb-4">PIN Management
                                <span class="tooltip-trigger">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="tooltip-content">
                                        - <b>Change PIN:</b> Allows you to change your current 6-digit PIN after verifying the old one.
                                    </span>
                                </span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <button id="settings-change-pin-btn" class="w-full px-4 py-3 bg-yellow-600 text-white font-semibold rounded-lg shadow-md hover:bg-yellow-700 text-sm">Change PIN</button>
                            </div>
                        </div>

                        <div class="pt-6 border-t"><div class="flex justify-between items-center mb-4"><h3 class="flex items-center text-lg font-semibold text-gray-700">Parameter Settings <span class="tooltip-trigger"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-content">- Key: The exact variable name from the data source (JSON).<br>- Formula: A mathematical expression to convert the raw value. Use 'x' as the variable (e.g., x * 0.01).<br>- Min/Max Value: Sets the 0-100% range for the bar graph on the dashboard.<br>- Display Max: Caps the numerical value shown on the dashboard (does not affect the graph page).<br>- Type: 'Value' is for numerical data. 'Status' is for text-based data.<br>- Mode: 'Real' uses the data source URL. 'Simulated' generates random test data.</span></span></h3><button id="settings-add-param-btn" class="text-sm bg-green-500 text-white px-3 py-1 rounded-md hover:bg-green-600">+</button></div><div id="settings-parameter-list" class="space-y-3"></div></div>
                    </div>

                    <div class="settings-footer mt-auto">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <button id="settings-save-btn" class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 text-sm">Save and Apply</button>
                                <button id="settings-import-btn" class="w-full px-4 py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 text-sm">Import from File</button>
                                <button id="settings-export-btn" class="w-full px-4 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 text-sm">Export to File</button>
                                <button id="settings-restore-btn" class="w-full px-4 py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 text-sm">Restore Defaults</button>
                            </div>
                            <p id="settings-feedback" class="text-green-600 text-sm mt-2 h-5 text-center"></p>
                        </div>
                    </div>
                    <form id="import-form" class="hidden">
                        <input type="file" id="settings-import-file-input" name="settings-import-file-input" accept=".json">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div id="unsaved-changes-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white text-gray-800 p-8 rounded-lg shadow-xl max-w-sm w-full">
            <h3 class="text-xl font-bold mb-4">Unsaved Changes</h3>
            <p class="mb-6">You have unsaved changes. Are you sure you want to leave this page?</p>
            <div class="flex justify-end gap-4">
                <button id="cancel-leave-btn" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                <button id="confirm-leave-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Leave Page</button>
            </div>
        </div>
    </div>

    <div id="restore-defaults-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white text-gray-800 p-8 rounded-lg shadow-xl max-w-sm w-full">
            <h3 class="text-xl font-bold mb-4">Restore Default Settings?</h3>
            <p class="mb-6">Are you sure? This will remove all your current settings and restore the original defaults. This action cannot be undone.</p>
            <div class="flex justify-end gap-4">
                <button id="cancel-restore-btn" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                <button id="confirm-restore-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Restore Defaults</button>
            </div>
        </div>
    </div>

    <div id="changelog-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white text-gray-800 p-6 rounded-lg shadow-xl max-w-md w-full m-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Changelog</h3>
                <button id="close-changelog-btn" class="text-2xl font-bold text-gray-500 hover:text-gray-800">×</button>
            </div>
            <div id="changelog-content" class="space-y-4 text-sm max-h-80 overflow-y-auto pr-2">
                </div>
            <div class="mt-6 text-center text-xs">
                <a href="https://ilustro.co" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">
                    Powered by Ilustro.co
                </a>
            </div>
        </div>
    </div>
    
    <div id="pin-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white text-gray-800 p-8 rounded-lg shadow-xl max-w-sm w-full">
            <h3 id="pin-modal-title" class="text-xl font-bold mb-2 text-center">Enter PIN</h3>
            <p id="pin-modal-subtitle" class="text-gray-600 mb-6 text-center">Please enter your 6-digit PIN to access settings.</p>
            <div class="w-64 mx-auto mb-4">
                <input type="text" id="pin-input" maxlength="6" class="w-full text-3xl font-bold bg-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <p id="pin-error" class="text-red-500 text-sm text-center h-5 mb-4"></p>
            <div id="pin-keypad" class="grid grid-cols-3 gap-4">
                </div>
             <div class="flex justify-center mt-6">
                <button id="cancel-pin-btn" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg">Cancel</button>
            </div>
        </div>
    </div>

    <template id="settings-param-row-template">
        <div class="parameter-row p-4 border rounded-lg bg-white shadow-sm relative space-y-4" draggable="true">
            <div class="drag-handle" title="Drag to reorder">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Display Name</label>
                    <input type="text" class="param-displayName settings-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Key</label>
                    <input type="text" class="param-jsonKey settings-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Unit</label>
                    <input type="text" class="param-unit settings-input mt-1 w-full">
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-500">Formula (use 'x')</label>
                    <input type="text" class="param-formula settings-input mt-1 w-full" placeholder="e.g., x * 0.01">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Min Value</label>
                    <input type="number" step="any" class="param-min settings-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Max Value</label>
                    <input type="number" step="any" class="param-max settings-input mt-1 w-full">
                </div>
                <div>
                     <label class="block text-xs font-medium text-gray-500 mb-1">Display Max</label>
                    <input type="number" step="any" class="param-displayMax settings-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Type</label>
                    <select class="param-type settings-input mt-1 w-full">
                        <option value="value">Value</option>
                        <option value="status">Status</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Mode</label>
                    <select class="param-mode settings-input mt-1 w-full">
                        <option value="real">Real</option>
                        <option value="simulated">Simulated</option>
                    </select>
                </div>
            </div>
             <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Range Text (Auto)</label>
                    <input type="text" class="param-rangeText settings-input mt-1 w-full bg-gray-200" readonly>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-500">Raw Value (Live)</label>
                    <input type="text" class="param-rawValue settings-input mt-1 w-full bg-gray-200" readonly>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-500">Calculated Value (Live)</label>
                    <input type="text" class="param-calculatedValue settings-input mt-1 w-full bg-gray-200" readonly>
                </div>
            </div>
            <div class="sim-settings hidden pt-4 border-t grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
                 <div>
                     <label class="block text-xs font-medium text-gray-500">Sim. Initial</label>
                     <input type="number" step="0.01" class="param-sim-initial settings-input w-full mt-1">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Sim. Range (+/-)</label>
                    <input type="number" step="0.01" class="param-sim-range settings-input w-full mt-1">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Sim. Min</label>
                    <input type="number" step="0.01" class="param-sim-min settings-input w-full mt-1">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Sim. Max</label>
                    <input type="number" step="0.01" class="param-sim-max settings-input w-full mt-1">
                </div>
            </div>
            <button class="remove-param-btn bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center absolute -top-3 -right-3 text-sm font-bold shadow-lg">-</button>
        </div>
    </template>
    
    <script type="module" src="assets/js/app.js"></script>

</body>
</html>