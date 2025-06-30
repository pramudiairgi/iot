<?php
include 'koneksi.php';

// Ambil data terakhir
$result = $conn->query("SELECT * FROM sensor_data ORDER BY created_at DESC LIMIT 1");
$latest = $result->fetch_assoc();

// Ambil data 1 jam terakhir
$data_result = $conn->query("SELECT * FROM sensor_data WHERE created_at >= NOW() - INTERVAL 1 HOUR ORDER BY created_at ASC");

$timestamps = [];
$temperatures = [];
$humidities = [];
$tableRows = [];

while ($row = $data_result->fetch_assoc()) {
    $timestamps[] = date('H:i', strtotime($row['created_at']));
    $temperatures[] = $row['temperature'];
    $humidities[] = $row['humidity'];
    $tableRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IoT Monitoring Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #121212;
            --card: #1e1e1e;
            --text: #ffffff;
            --accent: #00adb5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 20px;
        }

        h1,
        h2 {
            margin-bottom: 15px;
        }

        .cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .card {
            flex: 1;
            min-width: 200px;
            background-color: var(--card);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .card h3 {
            font-size: 1em;
            color: #bbb;
        }

        .card span {
            font-size: 2em;
            color: var(--accent);
        }

        .charts {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .chart-box {
            flex: 1;
            min-width: 400px;
            background-color: var(--card);
            padding: 20px;
            border-radius: 12px;
        }

        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            background-color: var(--card);
        }

        table th,
        table td {
            border: 1px solid #444;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #222;
        }

        tbody {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>

<body>

    <h1>📡 IoT Monitoring Dashboard</h1>

    <div class="cards">
        <div class="card">
            <h3>Suhu</h3>
            <span><?= $latest['temperature'] ?>°C</span>
        </div>
        <div class="card">
            <h3>Kelembaban</h3>
            <span><?= $latest['humidity'] ?>%</span>
        </div>
        <div class="card">
            <h3>Status</h3>
            <span><?= $latest['status'] ?></span>
        </div>
    </div>

    <div class="charts">
        <div class="chart-box">
            <h2>Grafik Suhu</h2>
            <canvas id="tempChart"></canvas>
        </div>
        <div class="chart-box">
            <h2>Grafik Kelembaban</h2>
            <canvas id="humidityChart"></canvas>
        </div>
    </div>

    <h2>Data Sensor (1 Jam Terakhir)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Suhu (°C)</th>
                <th>Kelembaban (%)</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableRows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['temperature'] ?></td>
                    <td><?= $row['humidity'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Chart Script -->
    <script>
        const labels = <?= json_encode($timestamps) ?>;
        const tempData = <?= json_encode($temperatures) ?>;
        const humidityData = <?= json_encode($humidities) ?>;

        new Chart(document.getElementById('tempChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Suhu (°C)',
                    data: tempData,
                    borderColor: '#ff4e50',
                    backgroundColor: 'rgba(255,78,80,0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Suhu (°C)'
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('humidityChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kelembaban (%)',
                    data: humidityData,
                    borderColor: '#00adb5',
                    backgroundColor: 'rgba(0,173,181,0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Kelembaban (%)'
                        }
                    }
                }
            }
        });

        // Auto refresh setiap 30 detik
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>

</body>

</html>