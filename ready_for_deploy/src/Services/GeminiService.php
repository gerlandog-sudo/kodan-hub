<?php
namespace App\Services;

class GeminiService {
    public static function generateContent($apiKey, $model, $payload) {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para evitar problemas en algunos cPanel

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 'error', 'message' => $error, 'http_code' => 500];
        }

        $decoded = json_decode($response, true);
        if ($httpCode !== 200) {
            return [
                'status' => 'error', 
                'message' => $decoded['error']['message'] ?? 'Error desconocido de Google',
                'http_code' => $httpCode
            ];
        }

        return ['status' => 'success', 'data' => $decoded, 'http_code' => 200];
    }
}
