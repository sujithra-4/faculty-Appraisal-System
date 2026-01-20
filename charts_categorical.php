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
    <title>Categorical Based Charts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: transparent;
            margin: 0;
            padding: 0;
        }
        .main-content {
            text-align: center;
            padding: 30px 20px;
        }
        canvas {
            max-width: 400px;
            max-height: 400px;
            margin: 20px auto;
            display: block;
        }
        .chart-box {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.1);
            display: inline-block;
        }
        button {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="main-content text-center">
        <h3 class="mb-4 text-dark">Categorical Based Eligible Faculties</h3>
        <div>
            <button id="incrementBtn" class="btn btn-primary me-2 active">Increment</button>
            <button id="incentiveBtn" class="btn btn-outline-primary">Incentive</button>
        </div>

        <div class="chart-box mt-4">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

<script>
let currentType = 'increment';
const ctx = document.getElementById('categoryChart').getContext('2d');
let chart;

function loadChart() {
    fetch(`fetch_chart_data.php?mode=categorical&type=${currentType}`)
        .then(res => res.json())
        .then(data => {
            const labels = data.map(d => d.label);
            const values = data.map(d => d.value);

            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#0077b6','#00b4d8','#90e0ef'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    plugins: {
                        title: {
                            display: true,
                            text: `${currentType.toUpperCase()} - Eligible Faculties (%)`,
                            font: { size: 18 }
                        },
                        legend: { position: 'bottom' }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1500
                    }
                }
            });
        });
}

document.getElementById('incrementBtn').addEventListener('click', () => {
    currentType = 'increment';
    document.getElementById('incrementBtn').classList.add('btn-primary');
    document.getElementById('incrementBtn').classList.remove('btn-outline-primary');
    document.getElementById('incentiveBtn').classList.add('btn-outline-primary');
    document.getElementById('incentiveBtn').classList.remove('btn-primary');
    loadChart();
});

document.getElementById('incentiveBtn').addEventListener('click', () => {
    currentType = 'incentive';
    document.getElementById('incentiveBtn').classList.add('btn-primary');
    document.getElementById('incentiveBtn').classList.remove('btn-outline-primary');
    document.getElementById('incrementBtn').classList.add('btn-outline-primary');
    document.getElementById('incrementBtn').classList.remove('btn-primary');
    loadChart();
});

loadChart();
</script>
</body>
</html>
