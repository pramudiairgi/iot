<?php
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;

// Konfigurasi MQTT Broker
$server = 'mqtt.revolusi-it.com';
$port = 1883;
$clientId = 'php-subscriber-' . uniqid();
$username = 'usm';
$password = 'usmjaya1';
$topic = 'iot/G.231.22.0173';

try {
    $mqtt = new MqttClient($server, $port, $clientId);

    // ✅ Buat object ConnectionSettings
    $connectionSettings = (new ConnectionSettings)
        ->setUsername($username)
        ->setPassword($password)
        ->setKeepAliveInterval(60)
        ->setLastWillTopic(null)
        ->setLastWillMessage(null)
        ->setLastWillQualityOfService(0);

    $mqtt->connect($connectionSettings);

    echo "✅ Terhubung ke MQTT Broker...\n";
    echo "🟢 Menunggu pesan di topik: {$topic}\n";

    // ✅ Subscribe dan proses pesan
    $mqtt->subscribe($topic, function (string $topic, string $message) {
        echo "📥 Pesan diterima di topik [$topic]: $message\n";

        $url = 'http://localhost/IOT/web/insert.php';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($message)
        ]);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "❌ CURL Error: " . curl_error($ch) . "\n";
        } else {
            echo "✅ Response dari insert.php: $result\n";
        }
        curl_close($ch);
    }, 0);

    // ✅ Loop MQTT agar terus berjalan
    $mqtt->loop(true);
} catch (MqttClientException $e) {
    echo "❌ MQTT Error: " . $e->getMessage();
}
