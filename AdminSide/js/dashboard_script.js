document.addEventListener('DOMContentLoaded', function() {
    // Load Chart.js dynamically if not present (we included CDN in markup)
    const ctx = document.getElementById('statusDoughnut');
    if (!ctx) return;

    const data = {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: statusColors,
            hoverOffset: 6
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            plugins: {
                legend: { position: 'bottom' }
            },
            maintainAspectRatio: false
        }
    });
});