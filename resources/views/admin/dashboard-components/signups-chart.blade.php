@php
    // Signups per month for the last 6 months
    $months = collect(range(5, 0))->map(function ($i) {
        return \Carbon\Carbon::now()->subMonths($i);
    });

    $counts = $months->map(function ($month) {
        return \App\Models\User::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count();
    });

    $labels = $months->map(fn($m) => $m->format('M Y'));
@endphp

<div class="ltd-panel ltd-chart-panel">
    <h2 class="ltd-panel__title">User Signups (Last 6 Months)</h2>
    <canvas id="ltdSignupsChart" height="90"></canvas>
</div>

<script>
    (function () {
        var ctx = document.getElementById('ltdSignupsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($labels),
                datasets: [{
                    label: 'New Users',
                    data: @json($counts),
                    borderColor: '#FFDE17',
                    backgroundColor: 'rgba(255, 222, 23, 0.15)',
                    borderWidth: 2,
                    pointBackgroundColor: '#3B3B3B',
                    pointRadius: 4,
                    tension: 0.35,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: '#eee' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    })();
</script>