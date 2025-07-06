import { state } from './state.js';
import { getYoutubeVideoId } from './helpers.js';

export const dashboard = {
    elements: {}, // Initialized as empty
    barChart: null,
    rotationState: {
        timerId: null,
        currentIndex: 0,
        activeSequence: []
    },
    
    _cacheElements() {
        this.elements = { 
            mainContainer: document.getElementById('view-dashboard'),
            dashboardContent: document.querySelector('#view-dashboard .dashboard-content'),
            header: document.getElementById('dashboard-header'),
            logo: document.getElementById('dashboard-logo'),
            headerCaption: document.getElementById('header-caption'),
            headerSubCaption: document.getElementById('header-subcaption'),
            operationLights: document.getElementById('operation-mode-lights'),
            barChartEl: document.getElementById('dashboardBarChart'), 
            valuesContainer: document.getElementById('dashboard-values-container'),
            graphContent: document.getElementById('dashboard-graph-content'),
            videoContent: document.getElementById('dashboard-video-content'),
            imageContent: document.getElementById('dashboard-image-content'),
            youtubeContent: document.getElementById('dashboard-youtube-content'),
            videoEl: document.getElementById('dashboard-video'),
            imageEl: document.getElementById('dashboard-image'),
            youtubeIframe: document.getElementById('dashboard-youtube-iframe'),
        };
    },

    stopRotation() {
        if (this.rotationState.timerId) {
            clearTimeout(this.rotationState.timerId);
            this.rotationState.timerId = null;
        }
    },
    startRotation() {
        this.stopRotation();
        const currentConfig = state.getConfig();
        const rotationConfig = currentConfig.contentRotation;
        if (!rotationConfig || !rotationConfig.enabled) {
            this.switchToView(0, false);
            return;
        }
        this.rotationState.activeSequence = rotationConfig.sequence.filter(item => item.enabled);
        if (this.rotationState.activeSequence.length > 0) {
            this.rotationState.currentIndex = 0;
            this.switchToView(this.rotationState.currentIndex, true);
        } else {
             this.switchToView(0, false);
        }
    },
    switchToView(index, scheduleNext) {
        const allContent = [this.elements.graphContent, this.elements.videoContent, this.elements.imageContent, this.elements.youtubeContent];
        allContent.forEach(el => el.classList.remove('active'));

        this.elements.videoEl.pause();
        this.elements.youtubeIframe.src = 'about:blank';

        const activeSequence = this.rotationState.activeSequence;
        if (!activeSequence || activeSequence.length === 0) {
             document.body.classList.remove('ad-mode');
             this.elements.graphContent.classList.add('active');
             return;
        }
        
        this.rotationState.currentIndex = index;
        const viewConfig = activeSequence[index];

        if(!viewConfig) {
             document.body.classList.remove('ad-mode');
             this.elements.graphContent.classList.add('active');
             return;
        }
        
        document.body.classList.toggle('ad-mode', viewConfig.type === 'video' || viewConfig.type === 'image');

        switch(viewConfig.type) {
            case 'graph':
                this.elements.graphContent.classList.add('active');
                break;
            case 'video':
                const youtubeId = getYoutubeVideoId(viewConfig.source.value);
                if (youtubeId) {
                    this.elements.youtubeIframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1&mute=1&loop=1&playlist=${youtubeId}&controls=0`;
                    this.elements.youtubeContent.classList.add('active');
                } else {
                    this.elements.videoEl.src = viewConfig.source.value || '';
                    this.elements.videoContent.classList.add('active');
                    this.elements.videoEl.play().catch(e => console.error("Video play failed:", e));
                }
                break;
            case 'image':
                this.elements.imageEl.src = viewConfig.source.value || '';
                this.elements.imageContent.classList.add('active');
                break;
        }

        if (scheduleNext && activeSequence.length > 1) {
            const nextIndex = (index + 1) % activeSequence.length;
            this.rotationState.timerId = setTimeout(() => {
                this.switchToView(nextIndex, true);
            }, viewConfig.duration * 1000);
        }
    },
    initBarChart() {
        if (!this.elements.barChartEl) return;
        
        if(this.barChart) this.barChart.destroy();
        const ctx = this.elements.barChartEl.getContext('2d');
        const currentConfig = state.getConfig();
        const styling = currentConfig.barChartStyling || {};

        this.barChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: [], datasets: [{ data: [] }] },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'x',
                scales: {
                     x: { 
                         grid: { 
                            color: styling.gridColor || 'rgba(255, 255, 255, 0.2)',
                            lineWidth: parseInt(styling.gridLineWidth, 10) || 1
                         }, 
                         ticks: { 
                             color: styling.labelTextColor || 'white', 
                             font: { 
                                 size: parseInt(styling.labelFontSize, 10) || 14, 
                                 weight: styling.valueFontWeight || 'bold' 
                            } 
                        } 
                     },
                    xTop: { 
                        position: 'top', 
                        grid: { drawOnChartArea: false }, 
                        ticks: { 
                            color: styling.rangeTextColor || 'white', 
                            font: { 
                                size: parseInt(styling.rangeFontSize, 10) || 12, 
                                weight: styling.rangeFontWeight || 'normal' 
                            } 
                        } 
                    },
                    y: { 
                        display: true, 
                        min: 0, 
                        max: 100, 
                        grid: { 
                            display: true, 
                            color: styling.gridColor || 'rgba(255, 255, 255, 0.2)',
                            lineWidth: parseInt(styling.gridLineWidth, 10) || 1
                        }, 
                        ticks: { display: false } 
                    }
                },
                plugins: { legend: { display: false }, datalabels: { display: false }, tooltip: { callbacks: { label: (context) => `${context.raw.toFixed(2)}%` } } }
            }
        });
    },
    applySettings: function() {
        const currentConfig = state.getConfig();
        if (!currentConfig) return;
        const { 
            logo, mainBackground, headerBackground, operationModes, 
            headerCaption, headerSubCaption, headerBackgroundColor, mainBackgroundColor, barChartStyling,
            showLogo, showHeaderBg, showMainBg, headerCaptionFontSize, headerSubcaptionFontSize, dashboardPadding
        } = currentConfig;

        if (this.elements.dashboardContent && dashboardPadding) {
            this.elements.dashboardContent.style.padding = `${dashboardPadding.top}px ${dashboardPadding.right}px ${dashboardPadding.bottom}px ${dashboardPadding.left}px`;
        }
        
        this.elements.logo.style.display = showLogo ? 'block' : 'none';
        this.elements.logo.src = (logo && logo.value) || 'https://placehold.co/200x200/transparent/white?text=Logo';
        
        this.elements.header.style.backgroundColor = headerBackgroundColor;
        this.elements.header.style.backgroundImage = (showHeaderBg && headerBackground && headerBackground.value) ? `url('${headerBackground.value}')` : 'none';
        
        this.elements.mainContainer.style.backgroundImage = (showMainBg && mainBackground && mainBackground.value) ? `url('${mainBackground.value}')` : 'none';
        document.body.style.backgroundColor = mainBackgroundColor;
        
        this.elements.headerCaption.textContent = headerCaption;
        this.elements.headerSubCaption.textContent = headerSubCaption;
        this.elements.headerCaption.style.fontSize = `${headerCaptionFontSize}px`;
        this.elements.headerSubCaption.style.fontSize = `${headerSubcaptionFontSize}px`;

        this.elements.operationLights.innerHTML = '';
        if(operationModes) {
            operationModes.forEach(mode => {
                const lightDiv = document.createElement('div');
                lightDiv.className = 'flex items-center gap-2';
                lightDiv.innerHTML = `<div id="light-${mode.name}" class="status-light"></div><span class="text-white font-semibold">${mode.name}</span>`;
                this.elements.operationLights.appendChild(lightDiv);
            });
        }
        
        this.initBarChart();
        
        if(this.barChart && barChartStyling) {
            this.barChart.data.datasets[0].backgroundColor = barChartStyling.barColor;
            this.barChart.data.datasets[0].borderColor = barChartStyling.barColor;
        }
        this.startRotation();
    },
    updateDisplay: function(data, error = null) {
        const currentConfig = state.getConfig();
        if (!this.barChart || !data || !currentConfig.params) return;
        
        const valueParams = currentConfig.params.filter(p => p.type === 'value');
        const styling = currentConfig.barChartStyling;

        // Re-apply dynamic styles before updating chart
        if (this.barChart.options.scales.x.ticks.font) {
            this.barChart.options.scales.x.ticks.font.weight = styling.valueFontWeight || 'bold';
            this.barChart.options.scales.x.ticks.color = styling.labelTextColor || 'white';
            this.barChart.options.scales.x.ticks.font.size = parseInt(styling.labelFontSize, 10) || 14;
        }
        if (this.barChart.options.scales.xTop.ticks.font) {
            this.barChart.options.scales.xTop.ticks.font.weight = styling.rangeFontWeight || 'normal';
            this.barChart.options.scales.xTop.ticks.color = styling.rangeTextColor || 'white';
            this.barChart.options.scales.xTop.ticks.font.size = parseInt(styling.rangeFontSize, 10) || 12;
        }
        if (this.barChart.options.scales.x.grid) {
            this.barChart.options.scales.x.grid.lineWidth = parseInt(styling.gridLineWidth, 10) || 1;
            this.barChart.options.scales.x.grid.color = styling.gridColor || 'rgba(255, 255, 255, 0.2)';
        }
         if (this.barChart.options.scales.y.grid) {
            this.barChart.options.scales.y.grid.lineWidth = parseInt(styling.gridLineWidth, 10) || 1;
            this.barChart.options.scales.y.grid.color = styling.gridColor || 'rgba(255, 255, 255, 0.2)';
        }

        const labels = valueParams.map(p => p.displayName);
        const rangeLabels = valueParams.map(p => `${p.min}-${p.max} ${p.unit}`.trim());
        const displayValues = valueParams.map(p => parseFloat(data[p.jsonKey]) || 0);
        
        const normalizedValues = displayValues.map((v, i) => {
            const min = valueParams[i].min || 0;
            const max = valueParams[i].max || 100;
            if (max <= min) return 0;
            return Math.max(0, Math.min(100, ((v - min) / (max - min)) * 100));
        });

        this.barChart.data.labels = labels;
        this.barChart.data.datasets[0].data = normalizedValues;
        
        this.elements.valuesContainer.innerHTML = '';
        displayValues.forEach((val, i) => {
            const unit = valueParams[i].unit || '';
            const valueDiv = document.createElement('div');
            valueDiv.className = 'flex-1';
            valueDiv.innerHTML = `<span style="font-size: ${styling.valueFontSize}px; color: ${styling.valueTextColor}; font-weight: ${styling.valueFontWeight || 'bold'};">${val.toFixed(2)}</span> <span style="font-size: ${styling.unitFontSize}px; color: ${styling.unitTextColor}; font-weight: ${styling.unitFontWeight || 'normal'};">${unit}</span>`;
            this.elements.valuesContainer.appendChild(valueDiv);
        });

        this.barChart.config.options.scales.xTop.ticks.callback = (value, index) => rangeLabels[index];
        this.barChart.update();

        const { modeStatusKey, operationModes } = currentConfig;
        if(operationModes) {
            const currentModeValue = data[modeStatusKey];
            const matchedMode = (currentModeValue != null) ? operationModes.find(m => String(m.value) === String(currentModeValue)) : null;
            const activeModeName = matchedMode ? matchedMode.name : 'MEASURING';
            operationModes.forEach(m => {
                const lightEl = document.getElementById(`light-${m.name}`);
                if (lightEl) lightEl.classList.toggle('active', m.name === activeModeName);
            });
        }
    },
    init() {
        this._cacheElements();
        this.initBarChart();
    }
};