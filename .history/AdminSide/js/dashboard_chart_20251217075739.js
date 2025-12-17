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
            window.kpiChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        hoverOffset: 8,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    cutout: '55%',
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

    // --- Styled Revenue Line (left column) ---
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
            const secondaryColor = computedStyle.getPropertyValue('--theme-secondary').trim() || '#5d4037';
            const accentColor = computedStyle.getPropertyValue('--theme-accent').trim() || '#d4a574';

            // Create gradient for bars
            function createBarGradient() {
                const height = canvas.clientHeight || 400;
                const gradient = ctx.createLinearGradient(0, height, 0, 0);
                gradient.addColorStop(0, primaryColor);
                gradient.addColorStop(1, secondaryColor);
                return gradient;
            }

            window.revenueChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: createBarGradient(),
                        borderColor: primaryColor,
                        borderWidth: 0,
                        borderRadius: 8,
                        borderSkipped: false,
                        hoverBackgroundColor: accentColor,
                        barThickness: 'flex',
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 16/9,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: primaryColor,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: accentColor,
                            borderWidth: 2,
                            cornerRadius: 8,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const v = context.parsed.y || 0;
                                    return (CURRENCY_SYMBOL ? CURRENCY_SYMBOL + ' ' : '') + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { 
                                color: '#7b6a58',
                                font: { weight: '500' }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(127,85,57,0.08)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#7b6a58',
                                callback: function(value) { return (CURRENCY_SYMBOL ? CURRENCY_SYMBOL : '') + Number(value).toLocaleString(); }
                            },
                            beginAtZero: true
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });

            // update gradient on resize
            window.addEventListener('resize', function() {
                try {
                    window.revenueChartInstance.data.datasets[0].backgroundColor = createBarGradient();
                    window.revenueChartInstance.update();
                } catch(e) { /* ignore */ }
            });

        } catch (err) {
            console.error('Failed to render revenue chart', err);
        }
    })();
});