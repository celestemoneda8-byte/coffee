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
        if (!canvas) return;

        const labels = (typeof CHART_KPI_LABELS !== 'undefined') ? CHART_KPI_LABELS : ['Total Orders','Pending Orders','Total Customers','Total Sales'];
        const data = (typeof CHART_KPI_DATA !== 'undefined') ? CHART_KPI_DATA : [0,0,0,0];

        const colors = ['#e79a45','#f6c94d','#9ad29b','#7f5539'];

        try {
            new Chart(canvas.getContext('2d'), {
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
        if (!canvas) return;

        const labels = (typeof CHART_REV_LABELS !== 'undefined') ? CHART_REV_LABELS : [];
        const data = (typeof CHART_REV_DATA !== 'undefined') ? CHART_REV_DATA : [];

        try {
            const ctx = canvas.getContext('2d');

            // gradient should use canvas height; recreate on resize for accuracy
            function createGradient() {
                const height = canvas.clientHeight || 400;
                const gradient = ctx.createLinearGradient(0, 0, 0, height);
                gradient.addColorStop(0, 'rgba(199,142,88,0.18)');
                gradient.addColorStop(1, 'rgba(199,142,88,0.02)');
                return gradient;
            }

            const strokeColor = 'rgba(127,85,57,0.95)';

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: createGradient(),
                        borderColor: strokeColor,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: strokeColor,
                        pointBorderWidth: 2,
                        tension: 0.36,
                        fill: true,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 16/9,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#6b4e3a',
                            bodyColor: '#6b4e3a',
                            borderColor: 'rgba(127,85,57,0.1)',
                            borderWidth: 1,
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
                            ticks: { color: '#7b6a58' }
                        },
                        y: {
                            grid: {
                                color: 'rgba(127,85,57,0.06)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#7b6a58',
                                callback: function(value) { return (CURRENCY_SYMBOL ? CURRENCY_SYMBOL : '') + Number(value).toLocaleString(); }
                            }
                        }
                    }
                }
            });

            // update gradient on resize
            window.addEventListener('resize', function() {
                try {
                    chart.data.datasets[0].backgroundColor = createGradient();
                    chart.update();
                } catch(e) { /* ignore */ }
            });

        } catch (err) {
            console.error('Failed to render revenue chart', err);
        }
    })();
});