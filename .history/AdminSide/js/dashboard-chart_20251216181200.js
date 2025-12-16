/**
 * Dashboard Charts - Revenue and KPI visualization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart (Line)
    const revenueCtx = document.getElementById('revenueLine');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: CHART_REV_LABELS,
                datasets: [{
                    label: 'Daily Revenue',
                    data: CHART_REV_DATA,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return CURRENCY_SYMBOL + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }

    // KPI Doughnut Chart
    const kpiCtx = document.getElementById('kpiDoughnut');
    if (kpiCtx) {
        new Chart(kpiCtx, {
            type: 'doughnut',
            data: {
                labels: CHART_KPI_LABELS,
                datasets: [{
                    data: CHART_KPI_DATA,
                    backgroundColor: [
                        '#0d6efd',
                        '#6c757d',
                        '#198754',
                        '#fd7e14'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
