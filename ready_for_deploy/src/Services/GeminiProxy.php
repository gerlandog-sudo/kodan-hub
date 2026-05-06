<?php
namespace Kodan\Services;

class GeminiProxy {
    public static function generateContent($apiKey, $model, $payload, $endpoint = null) {
        $baseUrl = $endpoint ?: "https://generativelanguage.googleapis.com/v1/models/";
        $url = $baseUrl . $model . ":generateContent?key=" . trim($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log para depuración
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
        $logFile = $logDir . '/ai_debug.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] PROXY: Gemini | MODEL: $model | STATUS: $httpCode\n";
        $logMsg .= "RESPONSE: " . substr($response, 0, 1000) . "\n---\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);

        if ($error) {
            return [
                'status' => 'error',
                'message' => 'CURL Error: ' . $error,
                'http_code' => 500
            ];
        }

        $data = json_decode($response, true);

        return [
            'status' => ($httpCode === 200) ? 'success' : 'error',
            'http_code' => $httpCode,
            'data' => $data,
            'message' => $data['error']['message'] ?? ($httpCode === 200 ? 'OK' : 'API Error ' . $httpCode)
        ];
    }
}
