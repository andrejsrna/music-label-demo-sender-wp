document.addEventListener('DOMContentLoaded', function() {
    const stats = mldsChartData.stats;
    
    // Prepare data for daily activity chart
    const dates = Object.keys(stats.daily_activity);
    const activityData = {
        opens: dates.map(date => stats.daily_activity[date].opens),
        downloads: dates.map(date => stats.daily_activity[date].downloads),
        emails: dates.map(date => stats.daily_activity[date].emails),
        feedback: dates.map(date => stats.daily_activity[date].feedback)
    };
    
    // Daily Activity Chart
    new Chart(document.getElementById('dailyActivityChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Opens',
                    data: activityData.opens,
                    borderColor: '#0073aa',
                    tension: 0.1
                },
                {
                    label: 'Downloads',
                    data: activityData.downloads,
                    borderColor: '#46b450',
                    tension: 0.1
                },
                {
                    label: 'Emails Sent',
                    data: activityData.emails,
                    borderColor: '#ffb900',
                    tension: 0.1
                },
                {
                    label: 'Feedback',
                    data: activityData.feedback,
                    borderColor: '#dc3232',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Track Performance Chart
    new Chart(document.getElementById('trackPerformanceChart'), {
        type: 'bar',
        data: {
            labels: stats.track_stats.map(track => track.title),
            datasets: [
                {
                    label: 'Emails',
                    data: stats.track_stats.map(track => track.emails),
                    backgroundColor: '#ffb900'
                },
                {
                    label: 'Opens',
                    data: stats.track_stats.map(track => track.opens),
                    backgroundColor: '#0073aa'
                },
                {
                    label: 'Downloads',
                    data: stats.track_stats.map(track => track.downloads),
                    backgroundColor: '#46b450'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}); 