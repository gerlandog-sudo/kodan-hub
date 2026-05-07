<?php
namespace Kodan\Services;

class GeminiProxy {
    public static function generateContent($apiKey, $model, $payload, $endpoint = null) {
        $baseUrl = $endpoint ?: "https://generativelanguage.googleapis.com/v1/models/";
        $url = $baseUrl . $model . ":generateContent?key=" . trim($apiKey);

        // --- TRADUCCIÓN DE PROTOCOLO (OpenAI -> Gemini) ---
        $contents = $payload['contents'] ?? [];
        
        // Si viene en formato OpenAI (messages), traducimos a Gemini (contents)
        if (empty($contents) && isset($payload['messages'])) {
            foreach ($payload['messages'] as $msg) {
                $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
                $contents[] = [
                    "role" => $role,
                    "parts" => [["text" => $msg['content'] ?? '']]
                ];
            }
        }

        // Si es un prompt simple (string)
        if (empty($contents) && is_string($payload)) {
            $contents[] = ["parts" => [["text" => $payload]]];
        }

        $geminiPayload = [
            "contents" => $contents,
            "generationConfig" => [
                "temperature" => $payload['temperature'] ?? 0.7,
                "maxOutputTokens" => $payload['max_tokens'] ?? 4096
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($geminiPayload));
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
        $logMsg .= "REQUEST: " . json_encode($geminiPayload) . "\n";
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
        
        // Extracción de texto estandarizada
        $extractedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'status' => ($httpCode === 200 && !empty($extractedText)) ? 'success' : 'error',
            'http_code' => $httpCode,
            'response' => $extractedText,
            'data' => $data,
            'message' => $data['error']['message'] ?? ($httpCode === 200 ? 'OK' : 'API Error ' . $httpCode)
        ];
    }
}
