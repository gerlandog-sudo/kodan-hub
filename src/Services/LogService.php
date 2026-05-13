<?php
namespace App\Services;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Medoo.php';

use App\Core\Database;

class LogService {
    /**
     * Registra una transacción de IA usando Medoo
     */
    public static function save($appId, $model, $tokensIn, $tokensOut, $latency, $status = 'success') {
        try {
            $db = Database::getInstance()->getDB();
            $db->insert('logs', [
                'app_id' => $appId,
                'model' => $model,
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
                'latency' => $latency,
                'status' => $status
            ]);
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
