<?php
namespace Kodan\Services;

/**
 * OpenAIProxy - Manejador universal para protocolos OpenAI / NVIDIA / Groq
 * v3.1: Ahora soporta traducción automática desde formato Gemini (contents) a OpenAI (messages).
 */
class OpenAIProxy {
    public static function generateContent($apiKey, $model, $payload, $endpoint) {
        $ch = curl_init($endpoint);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($apiKey)
        ];

        // --- TRADUCCIÓN DE PROTOCOLO (Lecciones Aprendidas) ---
        $messages = $payload['messages'] ?? [];
        
        // Si viene en formato Gemini (contents), traducimos a OpenAI (messages)
        if (empty($messages) && isset($payload['contents'])) {
            foreach ($payload['contents'] as $content) {
                $role = ($content['role'] ?? 'user') === 'user' ? 'user' : 'assistant';
                $text = '';
                $images = [];

                foreach ($content['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                    if (isset($part['inlineData'])) {
                        $images[] = [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:" . $part['inlineData']['mimeType'] . ";base64," . $part['inlineData']['data']
                            ]
                        ];
                    }
                }

                if (!empty($images)) {
                    $msgContent = [["type" => "text", "text" => $text]];
                    $msgContent = array_merge($msgContent, $images);
                    $messages[] = ["role" => $role, "content" => $msgContent];
                } else {
                    $messages[] = ["role" => $role, "content" => $text];
                }
            }
        }

        // Si es un prompt simple (string)
        if (empty($messages) && is_string($payload)) {
            $messages[] = ["role" => "user", "content" => $payload];
        }

        // Si después de todo sigue vacío, inyectamos un mensaje de error para evitar el 400 de NVIDIA
        if (empty($messages)) {
            $messages[] = ["role" => "user", "content" => "Hello (System Auto-Ping)"];
        }

        $openAiPayload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? ($payload['generationConfig']['temperature'] ?? 0.7),
            'max_tokens' => $payload['max_tokens'] ?? ($payload['generationConfig']['maxOutputTokens'] ?? 4096)
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($openAiPayload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'status' => 'error',
                'message' => 'OpenAI Proxy CURL Error: ' . $error,
                'http_code' => 500
            ];
        }

        $data = json_decode($response, true);
        
        // Extracción de texto estandarizada
        $extractedText = $data['choices'][0]['message']['content'] ?? ($data['choices'][0]['text'] ?? '');

        // Log para depuración
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
        $logFile = $logDir . '/ai_debug.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] PROXY: OpenAI | MODEL: $model | STATUS: $httpCode\n";
        $logMsg .= "RESPONSE: " . substr($response, 0, 1000) . "\n---\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);

        return [
            'status' => ($httpCode === 200 && !empty($extractedText)) ? 'success' : 'error',
            'http_code' => $httpCode,
            'response' => $extractedText,
            'data' => $data,
            'message' => $data['error']['message'] ?? ($httpCode === 200 ? 'OK' : 'OpenAI API Error ' . $httpCode)
        ];
    }
}
