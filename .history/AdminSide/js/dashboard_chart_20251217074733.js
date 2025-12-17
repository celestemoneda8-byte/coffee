/**
 * Enhanced Dashboard Charts - EXpresso Admin
 * Features: Animated charts, better styling, responsive design
 */

document.addEventListener('DOMContentLoaded', function () {
    // Ensure Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not found. Make sure chart.umd.min.js is included in dashboard.php');
        return;
    }

    // Get theme colors from CSS variables or use defaults
    const getThemeColor = (varName, fallback) => {
        const computed = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
        return computed || fallback;
    };

    const primaryColor = getThemeColor('--theme-primary', '#3e2723');
    const secondaryColor = getThemeColor('--theme-secondary', '#5d4037');
    const accentColor = getThemeColor('--theme-accent', '#d4a574');

    // Chart color palette
    const chartColors = {
        primary: primaryColor,
        secondary: secondaryColor,
        accent: accentColor,
        success: '#4caf50',
        warning: '#ff9800',
        danger: '#f44336',
        info: '#2196f3',
        orange: '#e79a45',
        yellow: '#f6c94d',
        green: '#66bb6a',
        brown: '#8d6e63'
    };

    // --- Enhanced KPI Doughnut Chart ---
    (function renderKpiDoughnut() {
        const canvas = document.getElementById('kpiDoughnut');
        if (!canvas) {
            console.warn('Canvas element kpiDoughnut not found');
            return;
        }

        const labels = (typeof CHART_KPI_LABELS !== 'undefined') ? CHART_KPI_LABELS : ['Total Orders','Pending Orders','Total Customers','Total Sales'];
        const data = (typeof CHART_KPI_DATA !== 'undefined') ? CHART_KPI_DATA : [0,0,0,0];

        const colors = [
            chartColors.orange,
            chartColors.yellow,
            chartColors.green,
            chartColors.primary
        ];

        const hoverColors = [
            '#d88a35',
            '#e5b83d',
            '#56ab56',
            '#2e1d17'
        ];

        try {
            if (window.kpiChartInstance) {
                window.kpiChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');

            // Center text plugin
            const centerTextPlugin = {
                id: 'centerText',
                beforeDraw: function(chart) {
                    const width = chart.width;
                    const height = chart.height;
                    const ctx = chart.ctx;
                    ctx.restore();
                    
                    // Calculate total (excluding sales for count)
                    const total = data[0] + data[1] + data[2];
                    
                    // Draw total number
                    const fontSize = (height / 8).toFixed(2);
                    ctx.font = 'bold ' + fontSize + 'px Poppins, sans-serif';
                    ctx.textBaseline = 'middle';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = chartColors.primary;
                    ctx.fillText(total, width / 2, height / 2 - 10);
                    
                    // Draw label
                    const labelSize = (height / 16).toFixed(2);
                    ctx.font = labelSize + 'px Poppins, sans-serif';
                    ctx.fillStyle = '#7b6a58';
                    ctx.fillText('Total Items', width / 2, height / 2 + 15);
                    
                    ctx.save();
                }
            };

            window.kpiChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        hoverBackgroundColor: hoverColors,
                        hoverOffset: 12,
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.2,
                    cutout: '65%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#5d4037',
                                font: {
                                    family: 'Poppins, sans-serif',
                                    size: 12,
                                    weight: '500'
                                },
                                boxWidth: 14,
                                boxHeight: 14,
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'rectRounded'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(62, 39, 35, 0.95)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: { family: 'Poppins, sans-serif', size: 13, weight: '600' },
                            bodyFont: { family: 'Poppins, sans-serif', size: 12 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            boxPadding: 5,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    if (label && label.toLowerCase().includes('sales')) {
                                        return ' ' + label + ': ' + (typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : '₱') + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                    return ' ' + label + ': ' + Number(value).toLocaleString();
                                }
                            }
                        }
                    }
                },
                plugins: [centerTextPlugin]
            });
        } catch (err) {
            console.error('Failed to render KPI doughnut', err);
        }
    })();

    // --- Enhanced Revenue Line Chart ---
    (function renderRevenueLine() {
        const canvas = document.getElementById('revenueLine');
        if (!canvas) {
            console.warn('Canvas element revenueLine not found');
            return;
        }

        const labels = (typeof CHART_REV_LABELS !== 'undefined') ? CHART_REV_LABELS : [];
        const data = (typeof CHART_REV_DATA !== 'undefined') ? CHART_REV_DATA : [];

        try {
            if (window.revenueChartInstance) {
                window.revenueChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');

            // Create beautiful gradient
            function createGradient() {
                const height = canvas.clientHeight || 300;
                const gradient = ctx.createLinearGradient(0, 0, 0, height);
                gradient.addColorStop(0, 'rgba(212, 165, 116, 0.4)');
                gradient.addColorStop(0.5, 'rgba(212, 165, 116, 0.15)');
                gradient.addColorStop(1, 'rgba(212, 165, 116, 0.02)');
                return gradient;
            }

            const strokeColor = chartColors.primary;

            window.revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: createGradient(),
                        borderColor: strokeColor,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: strokeColor,
                        pointBorderWidth: 3,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: strokeColor,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(62, 39, 35, 0.95)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: { family: 'Poppins, sans-serif', size: 13, weight: '600' },
                            bodyFont: { family: 'Poppins, sans-serif', size: 14, weight: '500' },
                            padding: 14,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                title: function(tooltipItems) {
                                    return '📅 ' + tooltipItems[0].label;
                                },
                                label: function(context) {
                                    const v = context.parsed.y || 0;
                                    const symbol = (typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : '₱');
                                    return '💰 Revenue: ' + symbol + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                                    family: 'Poppins, sans-serif',
                                    size: 11,
                                    weight: '500'
                                },
                                padding: 8
                            },
                            border: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(127, 85, 57, 0.08)',
                                drawBorder: false,
                                lineWidth: 1
                            },
                            ticks: {
                                color: '#7b6a58',
                                font: {
                                    family: 'Poppins, sans-serif',
                                    size: 11,
                                    weight: '500'
                                },
                                padding: 10,
                                callback: function(value) { 
                                    const symbol = (typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : '₱');
                                    return symbol + Number(value).toLocaleString(); 
                                }
                            },
                            border: {
                                display: false
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            // Update gradient on resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    try {
                        window.revenueChartInstance.data.datasets[0].backgroundColor = createGradient();
                        window.revenueChartInstance.update('none');
                    } catch(e) { /* ignore */ }
                }, 250);
            });

        } catch (err) {
            console.error('Failed to render revenue chart', err);
        }
    })();

    // --- Order Status Bar Chart (if canvas exists) ---
    (function renderOrderStatusChart() {
        const canvas = document.getElementById('orderStatusChart');
        if (!canvas) return;

        const statusData = (typeof CHART_ORDER_STATUS !== 'undefined') ? CHART_ORDER_STATUS : null;
        if (!statusData) return;

        try {
            if (window.orderStatusChartInstance) {
                window.orderStatusChartInstance.destroy();
            }

            const ctx = canvas.getContext('2d');

            window.orderStatusChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: statusData.labels || ['Pending', 'Processing', 'Completed', 'Cancelled'],
                    datasets: [{
                        label: 'Orders',
                        data: statusData.data || [0, 0, 0, 0],
                        backgroundColor: [
                            chartColors.warning,
                            chartColors.info,
                            chartColors.success,
                            chartColors.danger
                        ],
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 40,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    animation: {
                        duration: 1200,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(62, 39, 35, 0.95)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { 
                                color: '#7b6a58',
                                font: { family: 'Poppins, sans-serif', size: 11, weight: '500' }
                            }
                        },
                        y: {
                            grid: { color: 'rgba(127, 85, 57, 0.08)' },
                            ticks: { 
                                color: '#7b6a58',
                                font: { family: 'Poppins, sans-serif', size: 11 },
                                stepSize: 1
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        } catch (err) {
            console.error('Failed to render order status chart', err);
        }
    })();

    console.log('✅ Dashboard charts initialized successfully');
});