import { state } from './state.js';
import { CHART_COLORS } from './config.js';

export const graph = {
    elements: {}, // Initialized as empty
    mainChart: null, 
    brushChart: null, 
    timeRange: { min: 0, max: 0 },
    isInitialized: false,
    isHistoryLoaded: false, // *** เพิ่มตัวแปรใหม่ ***

    _cacheElements() {
        this.elements = { 
            checkboxes: document.getElementById('graph-param-checkboxes'), 
            mainChartEl: document.getElementById('mainChart'),
            brushChartEl: document.getElementById('brushChart'), 
            brushEl: document.getElementById('brush'), 
            brushContainer: document.getElementById('brush-chart-container'), 
            resetZoomBtn: document.getElementById('reset-zoom-btn'),
            statsContainer: document.getElementById('graph-stats-container'),
            historyTable: document.getElementById('history-table'),
            historyTableBody: document.getElementById('history-table-body'),
            exportCsvBtn: document.getElementById('export-csv-btn'),
        };
        
        // *** เพิ่ม CSS เพื่อป้องกัน brush หลุด ***
        if (this.elements.brushContainer) {
            this.elements.brushContainer.style.overflow = 'hidden';
        }
    },

    async init() {
        this._cacheElements();
        this.initMainChart();
        this.initBrushChart();
        this.initControls();
        // *** เปลี่ยนแปลงหลัก: ใช้ setupControlsWithoutHistory แทน setupControls ***
        await this.setupControlsWithoutHistory();
        this.isInitialized = true;
    },

    initMainChart() {
        if (!this.elements.mainChartEl) return;
        if (this.mainChart) this.mainChart.destroy();
        
        const currentConfig = state.getConfig();
        const styling = currentConfig.barChartStyling || {};

        this.mainChart = new Chart(this.elements.mainChartEl.getContext('2d'), {
            type: 'line', data: { datasets: [] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { 
                    x: { 
                        type: 'time', 
                        time: { tooltipFormat: 'PPpp', displayFormats: { hour: 'HH:mm', day: 'MMM dd' } }, 
                        grid: { color: styling.gridColor || 'rgba(107, 114, 128, 0.5)' },
                        ticks: { color: styling.textColor || '#e5e7eb' },
                        title: { display: true, text: 'Time', color: styling.textColor || '#e5e7eb' }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: { color: styling.gridColor || 'rgba(107, 114, 128, 0.5)' },
                        ticks: { color: styling.textColor || '#e5e7eb' },
                        title: { display: true, text: 'Value', color: styling.textColor || '#e5e7eb' }
                    }
                },
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top',
                        labels: { color: styling.textColor || '#e5e7eb' }
                    },
                    datalabels: { display: false }
                },
                onHover: (e, elements) => {
                    e.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                }
            }
        });
    },

    initBrushChart() {
        if (!this.elements.brushChartEl) return;
        if (this.brushChart) this.brushChart.destroy();
        this.brushChart = new Chart(this.elements.brushChartEl.getContext('2d'), {
            type: 'line', data: { datasets: [] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { x: { display: false }, y: { display: false } }, 
                plugins: { legend: { display: false }, tooltip: false, datalabels: { display: false } }, 
                animation: false, 
                elements: { point: { radius: 0 }, line: { borderWidth: 1 } } 
            }
        });
    },

    initControls() {
        this.elements.resetZoomBtn.addEventListener('click', () => this.resetView());
        this.initBrushHandlers();
        
        this.elements.checkboxes.addEventListener('change', (e) => {
            if (!this.mainChart) return;
            const targetKey = e.target.value;
            const isChecked = e.target.checked;
            const mainDataset = this.mainChart.data.datasets.find(d => d.jsonKey === targetKey);
            if (mainDataset) mainDataset.hidden = !isChecked;
            const brushDataset = this.brushChart.data.datasets.find(d => d.jsonKey === targetKey);
            if (brushDataset) brushDataset.hidden = !isChecked;
            this.mainChart.update('none');
            this.brushChart.update('none');
            this.updateHistoryDisplay();
        });

        this.elements.exportCsvBtn.addEventListener('click', () => this.exportToCSV());
    },

    initBrushHandlers() {
        // *** เคลียร์ event listeners เดิมก่อน ***
        if (this._brushHandlers) {
            window.removeEventListener("mousemove", this._brushHandlers.handleMouseMove);
            window.removeEventListener("mouseup", this._brushHandlers.handleMouseUp);
        }

        let isDragging = false, isResizing = null, startX, startLeft, startWidth;
        const MIN_BRUSH_WIDTH = 20; // ความกว้างขั้นต่ำของ brush
        
        const handleMouseDown = (e) => {
            startX = e.clientX;
            startLeft = this.elements.brushEl.offsetLeft;
            startWidth = this.elements.brushEl.offsetWidth;
            if (e.target.classList.contains("brush-handle")) {
                isResizing = e.target.classList.contains("left") ? "left" : "right";
            } else if (e.target === this.elements.brushEl) {
                isDragging = true;
            }
        };
        
        const handleMouseMove = (e) => {
            if (!isDragging && !isResizing) return;
            e.preventDefault();
            const dx = e.clientX - startX;
            const containerWidth = this.elements.brushContainer.offsetWidth;
            
            // *** เพิ่มการตรวจสอบ containerWidth ***
            if (containerWidth <= 0) return;
            
            if (isDragging) {
                let newLeft = startLeft + dx;
                // *** จำกัดการเลื่อนให้อยู่ในขอบเขต ***
                newLeft = Math.max(0, Math.min(newLeft, containerWidth - startWidth));
                this.elements.brushEl.style.left = `${newLeft}px`;
                this.updateMainChartFromBrush();
                
            } else if (isResizing === 'left') {
                let newLeft = startLeft + dx;
                let newWidth = startWidth - dx;
                
                // *** ป้องกันการเลื่อนซ้ายเกินขอบและความกว้างขั้นต่ำ ***
                newLeft = Math.max(0, newLeft);
                newWidth = Math.max(MIN_BRUSH_WIDTH, newWidth);
                
                // *** ถ้าความกว้างใหม่ทำให้เกินขอบขวา ให้ปรับ newLeft ***
                if (newLeft + newWidth > containerWidth) {
                    newLeft = containerWidth - newWidth;
                }
                
                // *** ป้องกันการเลื่อนซ้ายจนเกินขอบ ***
                if (newLeft < 0) {
                    newLeft = 0;
                    newWidth = startLeft + startWidth; // คืนค่าความกว้างเดิม
                }
                
                this.elements.brushEl.style.left = `${newLeft}px`;
                this.elements.brushEl.style.width = `${newWidth}px`;
                this.updateMainChartFromBrush();
                
            } else if (isResizing === 'right') {
                let newWidth = startWidth + dx;
                
                // *** จำกัดความกว้างขั้นต่ำและไม่ให้เกินขอบขวา ***
                newWidth = Math.max(MIN_BRUSH_WIDTH, newWidth);
                
                // *** ป้องกันการขยายเกินขอบขวา ***
                if (startLeft + newWidth > containerWidth) {
                    newWidth = containerWidth - startLeft;
                }
                
                // *** ตรวจสอบความกว้างขั้นต่ำอีกครั้ง ***
                if (newWidth < MIN_BRUSH_WIDTH) {
                    newWidth = MIN_BRUSH_WIDTH;
                }
                
                this.elements.brushEl.style.width = `${newWidth}px`;
                this.updateMainChartFromBrush();
            }
        };
        
        const handleMouseUp = () => {
            if(isDragging || isResizing) {
                // *** ตรวจสอบและแก้ไขขอบเขตสุดท้าย ***
                const containerWidth = this.elements.brushContainer.offsetWidth;
                const currentLeft = this.elements.brushEl.offsetLeft;
                const currentWidth = this.elements.brushEl.offsetWidth;
                
                // แก้ไขถ้าเกินขอบขวา
                if (currentLeft + currentWidth > containerWidth) {
                    this.elements.brushEl.style.width = `${containerWidth - currentLeft}px`;
                    this.updateMainChartFromBrush();
                }
                
                // แก้ไขถ้าเกินขอบซ้าย
                if (currentLeft < 0) {
                    this.elements.brushEl.style.left = '0px';
                    this.updateMainChartFromBrush();
                }
                
                // แก้ไขถ้าความกว้างน้อยเกินไป
                if (this.elements.brushEl.offsetWidth < MIN_BRUSH_WIDTH) {
                    this.elements.brushEl.style.width = `${MIN_BRUSH_WIDTH}px`;
                    this.updateMainChartFromBrush();
                }
                
                this.updateHistoryDisplay();
            }
            isDragging = false;
            isResizing = null;
        };

        // *** เก็บ reference ไว้เพื่อ cleanup ***
        this._brushHandlers = { handleMouseMove, handleMouseUp };
        
        this.elements.brushContainer.addEventListener("mousedown", handleMouseDown);
        window.addEventListener("mousemove", handleMouseMove);
        window.addEventListener("mouseup", handleMouseUp);
    },

    updateMainChartFromBrush() {
        if (!this.mainChart || !this.brushChart) return;
        const containerWidth = this.elements.brushContainer.offsetWidth;
        const brushLeft = this.elements.brushEl.offsetLeft;
        const brushWidth = this.elements.brushEl.offsetWidth;
        
        // *** แก้ไข: ใช้ timeRange แทนการคำนวณจาก brushChart data ***
        if (this.timeRange.min === 0 || this.timeRange.max === 0) {
            // ถ้าไม่มี timeRange ให้ใช้ข้อมูลจาก brushChart
            const allData = this.brushChart.data.datasets.flatMap(d => d.data);
            if (allData.length === 0) return;
            const times = allData.map(p => new Date(p.x).getTime()).sort((a, b) => a - b);
            this.timeRange.min = times[0];
            this.timeRange.max = times[times.length - 1];
        }
        
        const totalTimeSpan = this.timeRange.max - this.timeRange.min;
        const startRatio = brushLeft / containerWidth;
        const endRatio = (brushLeft + brushWidth) / containerWidth;
        
        this.mainChart.options.scales.x.min = this.timeRange.min + startRatio * totalTimeSpan;
        this.mainChart.options.scales.x.max = this.timeRange.min + endRatio * totalTimeSpan;
        this.mainChart.update("none");
    },
    
    async loadHistoryFromServer() {
        try {
            const response = await fetch('api/graph_data.php');
            if (!response.ok) {
                console.error('Failed to fetch graph history from server');
                return {};
            }
            const serverHistory = await response.json();
            const formattedHistory = {};
            for (const key in serverHistory) {
                formattedHistory[key] = serverHistory[key].map(p => ({ x: new Date(p.x), y: p.y }));
            }
            return formattedHistory;
        } catch (error) {
            console.error('Error loading graph history:', error);
            return {};
        }
    },

    async saveHistoryPoint(jsonKey, dataPoint) {
        try {
            await fetch('api/graph_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ jsonKey, dataPoint })
            });
        } catch (error) {
            console.error('Failed to save history point to server:', error);
        }
    },
    
    updateCharts(data) {
        if (!this.mainChart || !this.brushChart || !data) return;
        const now = new Date();
        const currentConfig = state.getConfig();
        const retentionMs = (currentConfig.retentionHours || 48) * 36e5;
        const retentionCutoff = now.getTime() - retentionMs;

        [this.mainChart, this.brushChart].forEach(chart => {
            chart.data.datasets.forEach(dataset => {
                if (data.hasOwnProperty(dataset.jsonKey)) {
                    const value = parseFloat(data[dataset.jsonKey]);
                    if (!isNaN(value)) {
                        const newDataPoint = { x: now, y: value };
                        dataset.data.push(newDataPoint);
                        const firstValidIndex = dataset.data.findIndex(p => new Date(p.x).getTime() > retentionCutoff);
                        if (firstValidIndex > 0) dataset.data.splice(0, firstValidIndex);

                        if (chart === this.mainChart) {
                           this.saveHistoryPoint(dataset.jsonKey, { x: now.toISOString(), y: value });
                        }
                    }
                }
            });
        });
        
        this.mainChart.update('none');
        this.brushChart.update('none');
        this.updateHistoryDisplay();
    },

    // *** ฟังก์ชันใหม่: setup controls โดยไม่โหลด history ***
    async setupControlsWithoutHistory() {
        const currentConfig = state.getConfig();
        if (!currentConfig.params) return;
        const params = currentConfig.params.filter(p => p.type === 'value');
        this.elements.checkboxes.innerHTML = '';

        // *** ไม่โหลด historical data ในขั้นตอนนี้ ***
        const mainDatasets = [];
        const brushDatasets = [];
        params.forEach((p, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center';
            div.innerHTML = `<input id="graph-param-${p.jsonKey}" type="checkbox" value="${p.jsonKey}" class="param-checkbox w-4 h-4" checked><label for="graph-param-${p.jsonKey}" class="ml-2 text-sm text-gray-300">${p.displayName}</label>`;
            this.elements.checkboxes.appendChild(div);

            const color = CHART_COLORS[index % CHART_COLORS.length];
            // *** ใช้ array ว่างแทน historical data ***
            const paramHistory = [];
            
            mainDatasets.push({ label: p.displayName, jsonKey: p.jsonKey, data: [...paramHistory], borderColor: color, backgroundColor: color + '33', fill: false, tension: 0.1, pointRadius: 1, borderWidth: 2 });
            brushDatasets.push({ jsonKey: p.jsonKey, data: [...paramHistory], borderColor: color, backgroundColor: color + '33' });
        });
        if (this.mainChart && this.brushChart) {
            this.mainChart.data.datasets = mainDatasets;
            this.brushChart.data.datasets = brushDatasets;
            await this.resetView();
        }
    },

    // *** ฟังก์ชันใหม่: lazy load history ***
    async lazyLoadHistory() {
        if (this.isHistoryLoaded) return; // ถ้าโหลดแล้ว ไม่ต้องโหลดซ้ำ
        
        console.log('Lazy loading graph history...');
        const historicalData = await this.loadHistoryFromServer();
        
        // อัปเดต datasets ด้วยข้อมูล history
        if (this.mainChart && this.brushChart) {
            this.mainChart.data.datasets.forEach(dataset => {
                if (historicalData[dataset.jsonKey]) {
                    dataset.data = [...historicalData[dataset.jsonKey]];
                }
            });
            
            this.brushChart.data.datasets.forEach(dataset => {
                if (historicalData[dataset.jsonKey]) {
                    dataset.data = [...historicalData[dataset.jsonKey]];
                }
            });
            
            await this.resetView();
        }
        
        this.isHistoryLoaded = true;
        console.log('Graph history loaded successfully');
    },

    async setupControls() {
        const currentConfig = state.getConfig();
        if (!currentConfig.params) return;
        const params = currentConfig.params.filter(p => p.type === 'value');
        this.elements.checkboxes.innerHTML = '';

        const historicalData = await this.loadHistoryFromServer();

        const mainDatasets = [];
        const brushDatasets = [];
        params.forEach((p, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center';
            div.innerHTML = `<input id="graph-param-${p.jsonKey}" type="checkbox" value="${p.jsonKey}" class="param-checkbox w-4 h-4" checked><label for="graph-param-${p.jsonKey}" class="ml-2 text-sm text-gray-300">${p.displayName}</label>`;
            this.elements.checkboxes.appendChild(div);

            const color = CHART_COLORS[index % CHART_COLORS.length];
            const paramHistory = historicalData[p.jsonKey] || [];
            
            mainDatasets.push({ label: p.displayName, jsonKey: p.jsonKey, data: [...paramHistory], borderColor: color, backgroundColor: color + '33', fill: false, tension: 0.1, pointRadius: 1, borderWidth: 2 });
            brushDatasets.push({ jsonKey: p.jsonKey, data: [...paramHistory], borderColor: color, backgroundColor: color + '33' });
        });
        if (this.mainChart && this.brushChart) {
            this.mainChart.data.datasets = mainDatasets;
            this.brushChart.data.datasets = brushDatasets;
            await this.resetView();
        }
        
        this.isHistoryLoaded = true;
    },

    async resetView(forceReload = false) {
        if (!this.mainChart || !this.brushChart) return;
        
        if(forceReload){
           await this.setupControls();
        }

        const currentConfig = state.getConfig();
        const retentionMs = (currentConfig.retentionHours || 48) * 36e5;
        const now = Date.now();
        
        // *** แก้ไข: ตั้งค่า timeRange ก่อนเรียก resetBrush ***
        this.timeRange = { min: now - retentionMs, max: now };
        
        // ตั้งค่า main chart ให้แสดงข้อมูลตามช่วงเวลาที่กำหนด
        this.mainChart.options.scales.x.min = this.timeRange.min;
        this.mainChart.options.scales.x.max = this.timeRange.max;
        
        this.mainChart.update();
        this.brushChart.update();
        
        this.updateHistoryDisplay();
        this.resetBrush(); // เรียกหลังจากตั้ง timeRange แล้ว
    },

    resetBrush() {
        if (!this.elements.brushEl || !this.elements.brushContainer) return;
        const containerWidth = this.elements.brushContainer.offsetWidth;
        // *** แก้ไข: ให้ brush ขยายเต็มความกว้างเป็นค่าเริ่มต้น ***
        const brushWidth = containerWidth; // เปลี่ยนจาก 0.2 เป็น 1 (100%)
        const brushLeft = 0; // เปลี่ยนจาก 0.8 เป็น 0 (เริ่มจากซ้ายสุด)
        this.elements.brushEl.style.left = brushLeft + 'px';
        this.elements.brushEl.style.width = brushWidth + 'px';
        this.updateMainChartFromBrush();
    },

    updateHistoryDisplay() {
        if (!this.elements.statsContainer || !this.elements.historyTableBody) return;
        const visibleDatasets = this.mainChart.data.datasets.filter(ds => !ds.hidden);
        const currentConfig = state.getConfig();
        const visibleParams = currentConfig.params.filter(p => visibleDatasets.some(d => d.jsonKey === p.jsonKey));
        
        this.elements.statsContainer.innerHTML = visibleParams.map(param => {
            const dataset = visibleDatasets.find(d => d.jsonKey === param.jsonKey);
            if (!dataset || dataset.data.length === 0) return `<div class="bg-gray-800 p-4 rounded-lg text-center"><h3 class="text-lg font-semibold">${param.displayName}</h3><p class="text-gray-400">No data</p></div>`;
            const values = dataset.data.map(p => p.y);
            const avg = values.reduce((a, b) => a + b, 0) / values.length;
            const min = Math.min(...values);
            const max = Math.max(...values);
            return `<div class="bg-gray-800 p-4 rounded-lg text-center"><h3 class="text-lg font-semibold">${param.displayName}</h3><p class="text-2xl font-bold">${avg.toFixed(2)} ${param.unit || ''}</p><p class="text-sm text-gray-400">Min: ${min.toFixed(2)} | Max: ${max.toFixed(2)}</p></div>`;
        }).join('');

        const tableData = visibleDatasets.flatMap(dataset => dataset.data.map(point => ({ ...point, param: dataset.label, jsonKey: dataset.jsonKey })));
        tableData.sort((a, b) => new Date(b.x) - new Date(a.x));
        const recentData = tableData.slice(0, 200);
        if (recentData.length === 0) {
            this.elements.historyTableBody.innerHTML = '<tr><td colspan="3" class="text-center py-4">No data available</td></tr>';
            return;
        }
        const headerKeys = [...new Set(recentData.map(d => d.jsonKey))];
        const headerHtml = '<tr><th class="px-2 py-1 text-left">Time</th>' + headerKeys.map(key => `<th class="px-2 py-1 text-left">${key}</th>`).join('') + '</tr>';
        this.elements.historyTable.querySelector('thead').innerHTML = headerHtml;
        const groupedByTime = {};
        recentData.forEach(point => {
            const timeKey = new Date(point.x).toISOString().slice(0, 19).replace('T', ' ');
            if (!groupedByTime[timeKey]) groupedByTime[timeKey] = {};
            groupedByTime[timeKey][point.jsonKey] = point.y;
        });
        const rowsHtml = Object.entries(groupedByTime).sort(([a], [b]) => b.localeCompare(a)).slice(0, 200).map(([time, values]) => {
            const cells = headerKeys.map(key => `<td class="px-2 py-1">${values[key] !== undefined ? values[key].toFixed(2) : '-'}</td>`).join('');
            return `<tr><td class="px-2 py-1">${time}</td>${cells}</tr>`;
        }).join('');
        this.elements.historyTableBody.innerHTML = rowsHtml;
    },

    exportToCSV() {
        if (!this.mainChart) return;
        const { min: visibleMin, max: visibleMax } = this.mainChart.options.scales.x;
        const visibleDatasets = this.mainChart.data.datasets.filter(ds => !ds.hidden);
        
        if (visibleDatasets.length === 0) {
            alert("No data to export.");
            return;
        }

        const headers = ['Timestamp', ...visibleDatasets.map(ds => ds.label)];
        const tableData = {};

        visibleDatasets.forEach(dataset => {
            const dataInRange = dataset.data.filter(p => p.x >= visibleMin && p.x <= visibleMax);
            dataInRange.forEach(point => {
                const timestamp = point.x.toISOString();
                if (!tableData[timestamp]) {
                    tableData[timestamp] = {};
                }
                tableData[timestamp][dataset.label] = point.y;
            });
        });
        
        let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";
        const sortedTimestamps = Object.keys(tableData).sort((a, b) => new Date(a) - new Date(b));

        sortedTimestamps.forEach(ts => {
            let row = [ts];
            visibleDatasets.forEach(ds => {
                const value = tableData[ts][ds.label];
                row.push(value !== undefined ? value.toFixed(4) : '');
            });
            csvContent += row.join(",") + "\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `history_export_${new Date().toISOString()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};