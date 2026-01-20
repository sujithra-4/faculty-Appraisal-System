<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hr') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Charts – Department Based</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-content {
            text-align: center;
            padding: 40px;
        }
        .chart-container {
            position: relative;
            width: 70%;
            max-width: 900px;
            height: 450px;
            margin: 40px auto;
        }
        button {
            min-width: 120px;
        }
        .active {
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h3 class="mb-4">Department Based Eligible Faculties</h3>
        <div>
            <button id="incrementBtn" class="btn btn-primary me-2 active">Increment</button>
            <button id="incentiveBtn" class="btn btn-outline-primary">Incentive</button>
        </div>

        <div class="chart-container">
            <canvas id="deptChart"></canvas>
        </div>
    </div>

<script>
let currentType = 'increment';
const ctx = document.getElementById('deptChart').getContext('2d');
let chart;

function loadChart() {
    fetch(`fetch_chart_data.php?mode=department&type=${currentType}&view=share`)
        .then(res => res.json())
        .then(data => {
            const labels = data.map(d => d.label);
            const values = data.map(d => d.value);

            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Eligible Percentage (%)',
                        data: values,
                        backgroundColor: 'rgba(0, 180, 216, 0.7)',
                        borderColor: '#0077b6',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    // ✅ Added your bounce + stagger animation
                    animation: {
                        duration: 1200,
                        easing: 'easeOutBounce'
                    },
                    animations: {
                        y: {
                            from: 0,
                            duration: 1200,
                            easing: 'easeOutBounce',
                            delay(ctx) {
                                return ctx.type === 'data' ? ctx.dataIndex * 150 : 0;
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { stepSize: 10 },
                            title: { display: true, text: 'Percentage (%)', font: { size: 14, weight: 'bold' } }
                        },
                        x: {
                            title: { display: true, text: 'Departments', font: { size: 14, weight: 'bold' } }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `${currentType.toUpperCase()} - Eligible Faculties by Department`,
                            font: { size: 18 }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.label}: ${ctx.parsed.y}%`
                            }
                        },
                        legend: { display: false }
                    }
                }
            });
        });
}

document.getElementById('incrementBtn').addEventListener('click', () => {
    currentType = 'increment';
    document.getElementById('incrementBtn').classList.add('btn-primary', 'active');
    document.getElementById('incrementBtn').classList.remove('btn-outline-primary');
    document.getElementById('incentiveBtn').classList.add('btn-outline-primary');
    document.getElementById('incentiveBtn').classList.remove('btn-primary', 'active');
    loadChart();
});

document.getElementById('incentiveBtn').addEventListener('click', () => {
    currentType = 'incentive';
    document.getElementById('incentiveBtn').classList.add('btn-primary', 'active');
    document.getElementById('incentiveBtn').classList.remove('btn-outline-primary');
    document.getElementById('incrementBtn').classList.add('btn-outline-primary');
    document.getElementById('incrementBtn').classList.remove('btn-primary', 'active');
    loadChart();
});

loadChart();
</script>
</body>
</html>
