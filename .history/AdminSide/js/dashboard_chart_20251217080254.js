// Updated dashboard-chart.js — center text removed from doughnut
document.addEventListener('DOMContentLoaded', function () {
    // Ensure Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not found. Make sure chart.umd.min.js is included in dashboard.php');
        return;
    }

    // --- KPI Doughnut (right column) ---
    (function renderKpiDoughnut() {
        const canvas = document.getElementById('kpiDoughnut');
        if (!canvas) {
            console.warn('Canvas element kpiDoughnut not found');
            return;
        }

        const labels = (typeof CHART_KPI_LABELS !== 'undefined') ? CHART_KPI_LABELS : ['Total Orders','Pending Orders','Total Customers','Total Sales'];
        const data = (typeof CHART_KPI_DATA !== 'undefined') ? CHART_KPI_DATA : [0,0,0,0];

        console.log('KPI Chart Data:', { labels, data });

        const colors = ['#e79a45','#f6c94d','#9ad29b','#7f5539'];

        try {
            // Destroy existing chart if it exists
            if (window.kpiChartInstance) {
                window.kpiChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');
            
            // Get theme colors
            const computedStyle = getComputedStyle(document.documentElement);
            const primaryColor = computedStyle.getPropertyValue('--theme-primary').trim() || '#3e2723';
            const secondaryColor = computedStyle.getPropertyValue('--theme-secondary').trim() || '#5d4037';
            const accentColor = computedStyle.getPropertyValue('--theme-accent').trim() || '#d4a574';
            
            const colors = [primaryColor, '#f57c00', '#00796b', '#5e35b1'];
            
            window.kpiChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        hoverOffset: 12,
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#6b4e3a',
                                boxWidth: 12,
                                padding: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    if (label && label.toLowerCase().includes('sales')) {
                                        return label + ': ' + (CURRENCY_SYMBOL ? CURRENCY_SYMBOL + ' ' : '') + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                    return label + ': ' + Number(value).toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        } catch (err) {
            console.error('Failed to render KPI doughnut', err);
        }
    })();

    // --- Monthly Trends Line Chart (left column) ---
    (function renderRevenueLine() {
        const canvas = document.getElementById('revenueLine');
        if (!canvas) {
            console.warn('Canvas element revenueLine not found');
            return;
        }

        const labels = (typeof CHART_REV_LABELS !== 'undefined') ? CHART_REV_LABELS : [];
        const data = (typeof CHART_REV_DATA !== 'undefined') ? CHART_REV_DATA : [];

        console.log('Revenue Chart Data:', { labels, data });

        try {
            // Destroy existing chart if it exists
            if (window.revenueChartInstance) {
                window.revenueChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');

            // Get theme colors from CSS variables
            const computedStyle = getComputedStyle(document.documentElement);
            const primaryColor = computedStyle.getPropertyValue('--theme-primary').trim() || '#3e2723';
            const accentColor = computedStyle.getPropertyValue('--theme-accent').trim() || '#d4a574';
            
            // Teal color like in the image
            const lineColor = '#26a69a';

            // Create gradient fill under the line
            function createLineGradient() {
                const height = canvas.clientHeight || 400;
                const gradient = ctx.createLinearGradient(0, 0, 0, height);
                gradient.addColorStop(0, 'rgba(38, 166, 154, 0.3)');
                gradient.addColorStop(0.5, 'rgba(38, 166, 154, 0.1)');
                gradient.addColorStop(1, 'rgba(38, 166, 154, 0.02)');
                return gradient;
            }

            window.revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: createLineGradient(),
                        borderColor: lineColor,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: lineColor,
                        pointBorderWidth: 2,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: lineColor,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.95)',
                            titleColor: primaryColor,
                            bodyColor: primaryColor,
                            borderColor: lineColor,
                            borderWidth: 2,
                            cornerRadius: 8,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label + ' ' + new Date().getFullYear();
                                },
                                label: function(context) {
                                    const v = context.parsed.y || 0;
                                    return 'Revenue: ' + (CURRENCY_SYMBOL ? CURRENCY_SYMBOL : '₱') + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { 
                                display: false, 
                                drawBorder: false 
                            },
                            ticks: { 
                                color: '#7b6a58',
                                font: { 
                                    size: 12,
                                    weight: '500' 
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(127, 85, 57, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#7b6a58',
                                font: { size: 11 },
                                callback: function(value) { 
                                    return (CURRENCY_SYMBOL ? CURRENCY_SYMBOL : '₱') + Number(value).toLocaleString(); 
                                },
                                maxTicksLimit: 8
                            },
                            beginAtZero: true
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart'
                    }
                }
            });

            // update gradient on resize
            window.addEventListener('resize', function() {
                try {
                    window.revenueChartInstance.data.datasets[0].backgroundColor = createLineGradient();
                    window.revenueChartInstance.update();
                } catch(e) { /* ignore */ }
            });

        } catch (err) {
            console.error('Failed to render revenue chart', err);
        }
    })();
});