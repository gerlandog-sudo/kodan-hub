<?php
namespace Kodan\Services;

use Kodan\Core\Database;

class LogService {
    /**
     * Registra una transacción de IA en la base de datos
     */
    public static function save($appId, $model, $tokensIn, $tokensOut, $latency, $status = 'success') {
        try {
            $db = Database::getInstance();
            $db->query("
                INSERT INTO logs (app_id, model, tokens_in, tokens_out, latency, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ", [$appId, $model, $tokensIn, $tokensOut, $latency, $status]);
            return true;
        } catch (\Exception $e) {
            error_log("KODAN LOG ERROR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extrae el conteo de tokens de la respuesta de Gemini o OpenAI
     */
    public static function extractTokens($data, $protocol = 'gemini-v1') {
        $in = 0;
        $out = 0;

        if ($protocol === 'openai-v1') {
            $in = $data['usage']['prompt_tokens'] ?? 0;
            $out = $data['usage']['completion_tokens'] ?? 0;
        } else {
            // Estructura Gemini
            $in = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $out = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
        }

        return [$in, $out];
    }
}
