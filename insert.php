<?php
include 'koneksi.php';
header('Content-Type: application/json');

// Pastikan metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (is_array($data)) {
        $temperature = isset($data['temperature']) ? floatval($data['temperature']) : null;
        $humidity    = isset($data['humidity']) ? floatval($data['humidity']) : null;
        $status      = isset($data['status']) ? $conn->real_escape_string($data['status']) : null;

        if ($temperature !== null && $humidity !== null && $status !== null) {
            $sql = "INSERT INTO sensor_data (temperature, humidity, status) VALUES ('$temperature', '$humidity', '$status')";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["success" => true, "message" => "Data berhasil disimpan"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Gagal simpan data: " . $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Data JSON tidak lengkap"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "JSON tidak valid"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Metode harus POST"]);
}

$conn->close();
?>
