<?php
include 'koneksi.php';

// Ambil data terakhir untuk Summary Box
$result = $conn->query("SELECT * FROM sensor_data ORDER BY created_at DESC LIMIT 1");
$latest = $result->fetch_assoc();

// Ambil data 1 jam terakhir untuk chart dan tabel
$data_result = $conn->query("SELECT * FROM sensor_data WHERE created_at >= NOW() - INTERVAL 1 HOUR ORDER BY created_at ASC");

$timestamps = [];
$temperatures = [];
$humidities = [];
$tableRows = [];

while ($row = $data_result->fetch_assoc()) {
    $timestamps[] = $row['created_at'];
    $temperatures[] = $row['temperature'];
    $humidities[] = $row['humidity'];
    $tableRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Monitoring IoT - Data 1 Jam Terakhir</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1, h2, h3 { color: #333; }

        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .box {
            flex: 1;
            padding: 20px;
            color: white;
            border-radius: 10px;
            text-align: center;
            font-size: 1.5em;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .red { background-color: #e74c3c; }
        .blue { background-color: #3498db; }
        .green { background-color: #27ae60; }

        .chart-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .chart-box {
            flex: 1;
            min-width: 400px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 30px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h1>Dashboard Monitoring IoT - Data 1 Jam Terakhir</h1>

<!-- Summary Box -->
<div class="summary">
    <div class="box red">
        Suhu (°C)<br>
        <strong><?= $latest['temperature'] ?>°C</strong>
    </div>
    <div class="box blue">
        Kelembaban (%)<br>
        <strong><?= $latest['humidity'] ?>%</strong>
    </div>
    <div class="box green">
        Status<br>
        <strong><?= $latest['status'] ?></strong>
    </div>
</div>

<!-- Chart Container -->
<h2>Grafik Sensor (1 Jam Terakhir)</h2>
<div class="chart-container">
    <div class="chart-box">
        <h3>Grafik Suhu (°C)</h3>
        <canvas id="tempChart"></canvas>
    </div>
    <div class="chart-box">
        <h3>Grafik Kelembaban (%)</h3>
        <canvas id="humidityChart"></canvas>
    </div>
</div>

<!-- Tabel Data -->
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

<!-- Chart JS -->
<script>
const labels = <?= json_encode($timestamps) ?>;
const tempData = <?= json_encode($temperatures) ?>;
const humidityData = <?= json_encode($humidities) ?>;

// Chart Suhu
new Chart(document.getElementById('tempChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Suhu (°C)',
            data: tempData,
            borderColor: 'red',
            backgroundColor: 'rgba(255,0,0,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Waktu' } },
            y: { title: { display: true, text: 'Suhu (°C)' } }
        }
    }
});

// Chart Kelembaban
new Chart(document.getElementById('humidityChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Kelembaban (%)',
            data: humidityData,
            borderColor: 'blue',
            backgroundColor: 'rgba(0,0,255,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Waktu' } },
            y: { title: { display: true, text: 'Kelembaban (%)' } }
        }
    }
});
</script>

</body>
</html>
