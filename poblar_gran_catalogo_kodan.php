<?php
/**
 * POBLAR GRAN CATÁLOGO KODAN v3
 * Carga la lista maestra de 48 modelos de Google con endpoint v1beta.
 */
require_once __DIR__ . '/src/Core/Database.php';

try {
    $db = \Kodan\Core\Database::getInstance()->getConnection();
    
    // Limpieza total de Google para evitar basura
    $db->exec("DELETE FROM ai_catalog WHERE provider = 'Google'");

    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
    $protocol = 'gemini-v1';

    $masterList = [
        ["id" => "gemini-2.5-flash", "name" => "Gemini 2.5 Flash"],
        ["id" => "gemini-2.5-pro", "name" => "Gemini 2.5 Pro"],
        ["id" => "gemini-2.0-flash", "name" => "Gemini 2.0 Flash"],
        ["id" => "gemini-2.0-flash-001", "name" => "Gemini 2.0 Flash 001"],
        ["id" => "gemini-2.0-flash-lite-001", "name" => "Gemini 2.0 Flash-Lite 001"],
        ["id" => "gemini-2.0-flash-lite", "name" => "Gemini 2.0 Flash-Lite"],
        ["id" => "gemini-2.5-flash-preview-tts", "name" => "Gemini 2.5 Flash Preview TTS"],
        ["id" => "gemini-2.5-pro-preview-tts", "name" => "Gemini 2.5 Pro Preview TTS"],
        ["id" => "gemma-4-26b-a4b-it", "name" => "Gemma 4 26B A4B IT"],
        ["id" => "gemma-4-31b-it", "name" => "Gemma 4 31B IT"],
        ["id" => "gemini-flash-latest", "name" => "Gemini Flash Latest"],
        ["id" => "gemini-flash-lite-latest", "name" => "Gemini Flash-Lite Latest"],
        ["id" => "gemini-pro-latest", "name" => "Gemini Pro Latest"],
        ["id" => "gemini-2.5-flash-lite", "name" => "Gemini 2.5 Flash-Lite"],
        ["id" => "gemini-2.5-flash-image", "name" => "Gemini 2.5 Flash Image"],
        ["id" => "gemini-3-pro-preview", "name" => "Gemini 3 Pro Preview"],
        ["id" => "gemini-3-flash-preview", "name" => "Gemini 3 Flash Preview"],
        ["id" => "gemini-3.1-pro-preview", "name" => "Gemini 3.1 Pro Preview"],
        ["id" => "gemini-3.1-pro-preview-customtools", "name" => "Gemini 3.1 Pro Preview Custom Tools"],
        ["id" => "gemini-3.1-flash-lite-preview", "name" => "Gemini 3.1 Flash Lite Preview"],
        ["id" => "gemini-3-pro-image-preview", "name" => "Gemini 3 Pro Image Preview"],
        ["id" => "nano-banana-pro-preview", "name" => "Nano Banana Pro"],
        ["id" => "gemini-3.1-flash-image-preview", "name" => "Gemini 3.1 Flash Image Preview"],
        ["id" => "lyria-3-clip-preview", "name" => "Lyria 3 Clip Preview"],
        ["id" => "lyria-3-pro-preview", "name" => "Lyria 3 Pro Preview"],
        ["id" => "gemini-3.1-flash-tts-preview", "name" => "Gemini 3.1 Flash TTS Preview"],
        ["id" => "gemini-robotics-er-1.5-preview", "name" => "Gemini Robotics-ER 1.5 Preview"],
        ["id" => "gemini-robotics-er-1.6-preview", "name" => "Gemini Robotics-ER 1.6 Preview"],
        ["id" => "gemini-2.5-computer-use-preview-10-2025", "name" => "Gemini 2.5 Computer Use Preview"],
        ["id" => "deep-research-max-preview-04-2026", "name" => "Deep Research Max Preview"],
        ["id" => "deep-research-preview-04-2026", "name" => "Deep Research Preview"],
        ["id" => "deep-research-pro-preview-12-2025", "name" => "Deep Research Pro Preview"],
        ["id" => "gemini-embedding-001", "name" => "Gemini Embedding 001"],
        ["id" => "gemini-embedding-2-preview", "name" => "Gemini Embedding 2 Preview"],
        ["id" => "gemini-embedding-2", "name" => "Gemini Embedding 2"],
        ["id" => "aqa", "name" => "AQA Model"],
        ["id" => "imagen-4.0-generate-001", "name" => "Imagen 4"],
        ["id" => "imagen-4.0-ultra-generate-001", "name" => "Imagen 4 Ultra"],
        ["id" => "imagen-4.0-fast-generate-001", "name" => "Imagen 4 Fast"],
        ["id" => "veo-2.0-generate-001", "name" => "Veo 2"],
        ["id" => "veo-3.0-generate-001", "name" => "Veo 3"],
        ["id" => "veo-3.0-fast-generate-001", "name" => "Veo 3 fast"],
        ["id" => "veo-3.1-generate-preview", "name" => "Veo 3.1"],
        ["id" => "veo-3.1-fast-generate-preview", "name" => "Veo 3.1 fast"],
        ["id" => "veo-3.1-lite-generate-preview", "name" => "Veo 3.1 lite"],
        ["id" => "gemini-2.5-flash-native-audio-latest", "name" => "Gemini 2.5 Flash Audio Latest"],
        ["id" => "gemini-2.5-flash-native-audio-preview-09-2025", "name" => "Gemini 2.5 Flash Audio Preview"],
        ["id" => "gemini-3.1-flash-live-preview", "name" => "Gemini 3.1 Flash Live Preview"]
    ];

    $stmt = $db->prepare("INSERT INTO ai_catalog (provider, name, identifier, protocol, endpoint) VALUES ('Google', ?, ?, ?, ?)");
    
    foreach ($masterList as $m) {
        $stmt->execute([$m['name'], $m['id'], $protocol, $endpoint]);
    }

    echo "✅ SE HAN CARGADO LOS " . count($masterList) . " MODELOS DE LA LISTA MAESTRA.";

} catch (Exception $e) { die("❌ ERROR: " . $e->getMessage()); }
