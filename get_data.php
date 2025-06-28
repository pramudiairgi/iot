<?php
header('Content-Type: application/json');
include 'koneksi.php';

$sql = "SELECT * FROM sensor_data ORDER BY created_at ASC";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'temperature' => floatval($row['temperature']),
            'humidity'    => floatval($row['humidity']),
            'status'      => $row['status'],
            'created_at'  => $row['created_at']
        ];
    }
}

echo json_encode($data);

$conn->close();
?>
