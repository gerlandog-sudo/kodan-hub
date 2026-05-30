# White Paper 04: Diagramas de Interacción de APIs de KodanHUB

> **KodanHUB — AI Gateway Centralizado**
> Versión: 1.0.49 | Clasificación: Interno / White Paper
> Fecha: 2026-05-26

---

## 1. Diagrama de Secuencia: Handshake Completo

```mermaid
sequenceDiagram
    participant C as App Cliente
    participant H as KodanHUB<br/>index.php
    participant M as Medoo ORM
    participant S as SQLite Database
    
    Note over C,S: FASE 1: Handshake - Registro Inicial o Recuperación de Token
    
    C->>H: POST / HTTP/1.1
    C->>H: Host: hub.kodan.software
    C->>H: X-KODAN-APP-ID: SC-MASTER-8DAC5109A1508665
    C->>H: X-KODAN-APP-NAME: SmartCook Production
    C->>H: Content-Type: application/json
    C->>H: Content-Length: 0
    
    Note over H: Headers capturados via getallheaders()<br/>o $_SERVER['HTTP_X_KODAN_APP_ID']
    
    par Validación de Seguridad
        H->>H: Verificar Origin contra regex *.kodan.software
        H->>H: Verificar METHOD = POST
        H->>H: Content-Type: aplicación/json
    end
    
    H->>M: $db->select('apps', '*', ['app_id' => $appId])
    M->>S: SELECT * FROM apps<br/>WHERE app_id = 'SC-MASTER-8DAC5109A1508665'
    
    alt App NO Existe — Auto-Registro
        S-->>M: 0 rows (no encontrada)
        M-->>H: null
        
        Note over H: Generar Token KDN
        H->>H: $newToken = 'KDN-' . strtoupper(substr(md5(uniqid()), 0, 16))
        H->>H: Ejemplo: 'KDN-SC-8DAC5109A1508665'
        
        H->>M: $db->insert('apps', [
        H->>M:     'name' => $appName,
        H->>M:     'app_id' => $appId,
        H->>M:     'token' => $newToken,
        H->>M:     'status' => 'active'
        H->>M: ])
        M->>S: INSERT INTO apps<br/>(name, app_id, token, status)<br/>VALUES (?, ?, ?, 'active')
        S-->>M: lastInsertId = 42
        M-->>H: 42
        
        H-->>C: HTTP/1.1 200 OK
        H-->>C: {
        H-->>C:   "status": "success",
        H-->>C:   "new_kodan_token": "KDN-SC-8DAC5109A1508665",
        H-->>C:   "message": "Handshake OK (Registrado)"
        H-->>C: }
        
        Note over C: App persiste token en<br/>AsyncStorage / LocalStorage
        
    else App Sí Existe — Recuperación de Token
        S-->>M: 1 row (app encontrada)
        M-->>H: ['id' => 42, 'token' => 'KDN-SC-A1B2C3...', 'status' => 'active']
        
        H->>H: Verificar status = 'active'
        
        alt status != 'active'
            H-->>C: HTTP/1.1 403 Forbidden
            H-->>C: {
            H-->>C:   "status": "error",
            H-->>C:   "message": "Handshake rechazado: La aplicación está inactiva o pausada en el Hub."
            H-->>C: }
        else status = 'active'
            H-->>C: HTTP/1.1 200 OK
            H-->>C: {
            H-->>C:   "status": "success",
            H-->>C:   "new_kodan_token": "KDN-SC-A1B2C3...",
            H-->>C:   "message": "Handshake OK (Sincronizado)"
            H-->>C: }
        end
    end
```

**Figura 1.1** — Secuencia completa del handshake con validación, auto-registro y recuperación.

### Request/Response del Handshake

**Request:**
```http
POST / HTTP/1.1
Host: hub.kodan.software
X-KODAN-APP-ID: SC-MASTER-8DAC5109A1508665
X-KODAN-APP-NAME: SmartCook Production
Content-Type: application/json
Content-Length: 0
```

**Response (Registro exitoso):**
```json
{
  "status": "success",
  "new_kodan_token": "KDN-SC-8DAC5109A1508665",
  "message": "Handshake OK (Registrado)"
}
```

**Response (Recuperación exitosa):**
```json
{
  "status": "success",
  "new_kodan_token": "KDN-SC-A1B2C3D4E5F6789",
  "message": "Handshake OK (Sincronizado)"
}
```

**Response (App inactiva):**
```json
{
  "status": "error",
  "message": "Handshake rechazado: La aplicación está inactiva o pausada en el Hub."
}
```

---

## 2. Diagrama de Secuencia: Proxy IA con Traducción de Protocolo

```mermaid
sequenceDiagram
    participant C as App Cliente
    participant H as KodanHUB index.php
    participant M as Medoo ORM
    participant S as SQLite DB
    participant GP as GeminiProxy<br/>(si protocol=gemini-v1)
    participant OP as OpenAIProxy<br/>(si protocol=openai-v1)
    participant AI as Proveedor IA

    Note over C,AI: FASE 2: Proxy IA con Traducción Automática
    
    C->>H: POST / HTTP/1.1
    C->>H: X-KODAN-TOKEN: KDN-SC-8DAC5109A1508665
    C->>H: Content-Type: application/json
    C->>H: {
    C->>H:   "action": "ai",
    C->>H:   "payload": {
    C->>H:     "messages": [
    C->>H:       {"role": "user", "content": "Analiza este texto: El cielo es azul"}
    C->>H:     ],
    C->>H:     "temperature": 0.5,
    C->>H:     "max_tokens": 4096
    C->>H:   }
    C->>H: }
    
    Note over H: 1. Validar Token en DB
    H->>M: $db->select('apps', '*', ['token' => $token])
    M->>S: SELECT * FROM apps WHERE token = 'KDN-SC-...'
    S-->>M: ['id' => 42, 'status' => 'active', ...]
    M-->>H: App autenticada
    
    Note over H: 2. Verificar action = 'ai' y payload no vacío
    H->>H: $action = 'ai' ✓
    H->>H: $payload = {...} ✓
    
    Note over H: 3. Cargar servicios con failover
    H->>M: SELECT s.*, c.protocol, c.identifier, c.endpoint, c.provider
    H->>M: FROM app_services s
    H->>M: JOIN ai_catalog c ON s.catalog_id = c.id
    H->>M: WHERE s.app_id = 42 AND s.is_active = 1
    H->>M: ORDER BY s.priority ASC
    M->>S: Query JOIN
    S-->>M: [{protocol: 'gemini-v1', identifier: 'gemini-2.0-flash', ...}]
    M-->>H: Lista de servicios
    
    Note over H: 4. Intentar Servicio 1 (Gemini 2.0 Flash)
    
    H->>GP: generateContent(api_key, model, payload, endpoint)
    
    Note over GP: TRADUCCIÓN: messages → contents
    Note over GP: 'user' → 'user'
    Note over GP: content string → parts[].text
    Note over GP: Si hubiera imágenes: inlineData
    
    GP->>GP: Construir payload Gemini:
    Note over GP: {
    Note over GP:   "contents": [{
    Note over GP:     "role": "user",
    Note over GP:     "parts": [{"text": "Analiza este texto: El cielo es azul"}]
    Note over GP:   }],
    Note over GP:   "generationConfig": {
    Note over GP:     "temperature": 0.5,
    Note over GP:     "maxOutputTokens": 4096
    Note over GP:   }
    Note over GP: }
    
    GP->>AI: POST https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=AIza...
    GP->>AI: Content-Type: application/json
    GP->>AI: CURLOPT_SSL_VERIFYPEER: false
    GP->>AI: Timeout: 60s
    
    AI-->>GP: HTTP 200
    AI-->>GP: {
    AI-->>GP:   "candidates": [{
    AI-->>GP:     "content": {
    AI-->>GP:       "parts": [{"text": "Efectivamente, el cielo se ve azul debido a la dispersión Rayleigh de la luz solar en la atmósfera."}]
    AI-->>GP:     }
    AI-->>GP:   }],
    AI-->>GP:   "usageMetadata": {
    AI-->>GP:     "promptTokenCount": 15,
    AI-->>GP:     "candidatesTokenCount": 32
    AI-->>GP:   }
    AI-->>GP: }
    
    GP->>GP: Extraer texto de candidates[0].content.parts[0].text
    GP-->>H: ['status' => 'success', 'response' => '...', 'data' => [...]]
    
    Note over H: 5. Extraer tokens vía LogService
    H->>H: extractTokens(data, 'gemini-v1')
    H->>H: tokens_in = 15, tokens_out = 32
    
    Note over H: 6. Registrar log de auditoría
    H->>M: $db->insert('logs', [
    H->>M:     'app_id' => 42,
    H->>M:     'model' => 'gemini-2.0-flash',
    H->>M:     'tokens_in' => 15,
    H->>M:     'tokens_out' => 32,
    H->>M:     'latency' => 2.45,
    H->>M:     'status' => 'success'
    H->>M: ])
    M->>S: INSERT INTO logs ...
    S-->>M: OK
    
    Note over H: 7. Responder al cliente
    H-->>C: HTTP/1.1 200 OK
    H-->>C: {
    H-->>C:   "status": "success",
    H-->>C:   "response": "Efectivamente, el cielo se ve azul debido a la dispersión Rayleigh...",
    H-->>C:   "usage": {
    H-->>C:     "prompt_tokens": 15,
    H-->>C:     "completion_tokens": 32,
    H-->>C:     "total_tokens": 47
    H-->>C:   },
    H-->>C:   "hub_model": "gemini-2.0-flash",
    H-->>C:   "provider": "GOOGLE"
    H-->>C: }
    
    Note over C: App recibe respuesta<br/>Renderiza UI con contenido IA
```

**Figura 2.1** — Secuencia completa de proxy IA con traducción de protocolo, extracción de tokens y auditoría.

### Request/Response del Proxy IA

**Request:**
```http
POST / HTTP/1.1
Host: hub.kodan.software
X-KODAN-TOKEN: KDN-SC-8DAC5109A1508665
Content-Type: application/json

{
  "action": "ai",
  "payload": {
    "messages": [
      {"role": "user", "content": "Analiza este texto: El cielo es azul"}
    ],
    "temperature": 0.5,
    "max_tokens": 4096
  }
}
```

**Response exitosa:**
```json
{
  "status": "success",
  "response": "Efectivamente, el cielo se ve azul debido a la dispersión Rayleigh de la luz solar en la atmósfera.",
  "usage": {
    "prompt_tokens": 15,
    "completion_tokens": 32,
    "total_tokens": 47
  },
  "hub_model": "gemini-2.0-flash",
  "provider": "GOOGLE"
}
```

---

## 3. Diagrama de Secuencia: Failover entre Proveedores

```mermaid
sequenceDiagram
    participant C as App Cliente
    participant H as KodanHUB
    participant G as Gemini 2.0 Flash<br/>(Priority 1)
    participant O as GPT-4o Mini<br/>(Priority 2)
    participant L as Llama 3.1 8B<br/>(Priority 3)

    C->>H: Request IA con X-KODAN-TOKEN
    
    Note over H: Cargar servicios ordenados por prioridad:
    Note over H: [Gemini 2.0 Flash (P1), GPT-4o Mini (P2), Llama 3.1 8B (P3)]
    
    rect rgb(200, 50, 50)
        Note over H,G: INTENTO 1 - Gemini 2.0 Flash (Prioridad 1)
        H->>G: POST /v1/models/gemini-2.0-flash:generateContent
        H->>G: API Key: AIzaSy...
        Note over G: Servicio sobrecargado
        G-->>H: HTTP 429 Too Many Requests<br/>{error: {message: "Rate limit exceeded"}}
        
        H->>H: Log: status=error, tokens=0, latency=3.2s
        Note over H: ⚠ Latencia acumulada: 3.2s
    end
    
    rect rgb(200, 150, 50)
        Note over H,O: INTENTO 2 - GPT-4o Mini (Prioridad 2)
        H->>H: Traducir payload: contents → messages
        H->>O: POST https://api.openai.com/v1/chat/completions
        H->>O: Authorization: Bearer sk-proj-...
        O-->>H: HTTP 200 OK
        O-->>H: {choices: [{message: {content: "..."}}], usage: {...}}
        
        H->>H: Log: status=success, tokens={15, 32}, latency=1.8s
        Note over H: ✅ Éxito con proveedor secundario
        Note over H: Latencia total con failover: 5.0s
        
        H-->>C: 200 OK {response: "...", usage: {...}}
        Note over C: Respuesta entregada con éxito<br/>App no sabe que hubo failover
    end
    
    Note over H: Si GPT-4o también fallara, intentaría<br/>Llama 3.1 8B en NVIDIA (Prioridad 3)
    Note over H: Si todos fallan → 500 con<br/>mensaje "Todos los servicios fallaron"
```

**Figura 3.1** — Secuencia de failover donde Gemini falla (429) y se recupera automáticamente con GPT-4o Mini.

### Código de Failover (index.php)

```php
foreach ($services as $service) {
    $startTime = microtime(true);
    
    if ($service['protocol'] === 'openai-v1') {
        $result = OpenAIProxy::generateContent(...);
    } else {
        $result = GeminiProxy::generateContent(...);
    }
    
    $latency = round(microtime(true) - $startTime, 2);

    if ($result['status'] === 'success') {
        $tokens = LogService::extractTokens($result['data'], $service['protocol']);
        LogService::save($app['id'], $service['identifier'], $tokens[0], $tokens[1], $latency, 'success');
        echo json_encode([...]); // ❗ exit inmediato
        exit;
    } else {
        LogService::save($app['id'], $service['identifier'], 0, 0, $latency, 'error');
        // ❗ Siguiente iteración del foreach
    }
}
// Si se termina el foreach sin éxito:
echo json_encode(['status' => 'error', 'message' => 'Todos los servicios de IA fallaron.']);
```

---

## 4. Diagrama de Secuencia: Traducción Bidireccional de Protocolo

### 4.1 OpenAI a Gemini (GeminiProxy)

```mermaid
sequenceDiagram
    participant H as KodanHUB index.php
    participant GP as GeminiProxy.php
    participant AI as Gemini API

    H->>GP: generateContent(apiKey, model, payload, endpoint)
    
    Note over GP: Payload entrante (formato OpenAI):
    Note over GP: {
    Note over GP:   "messages": [
    Note over GP:     {"role": "user", "content": "Hola"},
    Note over GP:     {"role": "assistant", "content": "¿Cómo estás?"},
    Note over GP:     {"role": "user", "content": [
    Note over GP:       {"type": "text", "text": "Analiza esta imagen"},
    Note over GP:       {"type": "image_url", "image_url": {
    Note over GP:         "url": "data:image/jpeg;base64,/9j/4AAQ..."
    Note over GP:       }}
    Note over GP:     ]}
    Note over GP:   ],
    Note over GP:   "temperature": 0.7,
    Note over GP:   "max_tokens": 4096
    Note over GP: }
    
    GP->>GP: 1. contents[] existe? → NO
    GP->>GP: 2. messages[] existe? → SÍ
    
    GP->>GP: 3. Iterar cada mensaje:
    
    Note over GP: Message 1: user "Hola"
    GP->>GP: role 'user' → 'user' (sin cambios)
    GP->>GP: content string → parts[0].text = "Hola"
    
    Note over GP: Message 2: assistant "¿Cómo estás?"
    GP->>GP: role 'assistant' → 'model'
    GP->>GP: content string → parts[0].text = "¿Cómo estás?"
    
    Note over GP: Message 3: user [text + image]
    GP->>GP: type 'text' → parts[].text
    GP->>GP: type 'image_url' → regex extrae mimeType y base64
    GP->>GP: Construye parts[].inlineData = {mimeType, data}
    
    Note over GP: 4. Construir payload Gemini:
    Note over GP: {
    Note over GP:   "contents": [
    Note over GP:     {"role": "user", "parts": [{"text": "Hola"}]},
    Note over GP:     {"role": "model", "parts": [{"text": "¿Cómo estás?"}]},
    Note over GP:     {"role": "user", "parts": [
    Note over GP:       {"text": "Analiza esta imagen"},
    Note over GP:       {"inlineData": {"mimeType": "image/jpeg", "data": "/9j/4AAQ..."}}
    Note over GP:     ]}
    Note over GP:   ],
    Note over GP:   "generationConfig": {
    Note over GP:     "temperature": 0.7,
    Note over GP:     "maxOutputTokens": 4096
    Note over GP:   }
    Note over GP: }
    
    GP->>AI: POST con payload traducido
    
    AI-->>GP: HTTP 200 Response
    
    Note over GP: 5. Extraer respuesta:
    GP->>GP: data.candidates[0].content.parts[0].text
    GP->>GP: data.usageMetadata.promptTokenCount
    GP->>GP: data.usageMetadata.candidatesTokenCount
    
    GP-->>H: {status, http_code, response, data, message}
```

### 4.2 Gemini a OpenAI (OpenAIProxy)

```mermaid
sequenceDiagram
    participant H as KodanHUB index.php
    participant OP as OpenAIProxy.php
    participant AI as OpenAI / NVIDIA / Groq

    H->>OP: generateContent(apiKey, model, payload, endpoint)
    
    Note over OP: Payload entrante (formato Gemini):
    Note over OP: {
    Note over OP:   "contents": [
    Note over OP:     {"role": "user", "parts": [{"text": "Hola"}]},
    Note over OP:     {"role": "model", "parts": [{"text": "¿Cómo estás?"}]},
    Note over OP:     {"role": "user", "parts": [
    Note over OP:       {"text": "Analiza esta imagen"},
    Note over OP:       {"inlineData": {"mimeType": "image/jpeg", "data": "/9j/4AAQ..."}}
    Note over OP:     ]}
    Note over OP:   ]
    Note over OP: }
    
    OP->>OP: 1. messages[] existe? → NO
    OP->>OP: 2. contents[] existe? → SÍ
    
    OP->>OP: 3. Iterar cada content:
    
    Note over OP: Content 1: user parts[0].text
    OP->>OP: role 'user' → 'user'
    OP->>OP: parts[0].text → content string
    
    Note over OP: Content 2: model parts[0].text
    OP->>OP: role 'model' → 'assistant'
    OP->>OP: parts[0].text → content string
    
    Note over OP: Content 3: user [text + inlineData]
    OP->>OP: parts[0].text → content[0].text
    OP->>OP: parts[1].inlineData → content[1].image_url
    OP->>OP: Construir data URI: data:mimeType;base64,data
    
    Note over OP: 4. Construir payload OpenAI:
    Note over OP: {
    Note over OP:   "model": "gpt-4o-mini",
    Note over OP:   "messages": [
    Note over OP:     {"role": "user", "content": "Hola"},
    Note over OP:     {"role": "assistant", "content": "¿Cómo estás?"},
    Note over OP:     {"role": "user", "content": [
    Note over OP:       {"type": "text", "text": "Analiza esta imagen"},
    Note over OP:       {"type": "image_url", "image_url": {
    Note over OP:         "url": "data:image/jpeg;base64,/9j/4AAQ..."
    Note over OP:       }}
    Note over OP:     ]}
    Note over OP:   ],
    Note over OP:   "temperature": 0.7,
    Note over OP:   "max_tokens": 4096
    Note over OP: }
    
    Note over OP: 5. Safety: si messages sigue vacío
    Note over OP:    inyectar mensaje dummy para evitar error 400 de NVIDIA
    
    OP->>AI: POST con payload traducido
    OP->>AI: Authorization: Bearer sk-proj-...
    
    AI-->>OP: HTTP 200 Response
    
    Note over OP: 6. Extraer respuesta:
    OP->>OP: data.choices[0].message.content
    OP->>OP: data.usage.prompt_tokens
    OP->>OP: data.usage.completion_tokens
    
    OP-->>H: {status, http_code, response, data, message}
```

---

## 5. Diagrama de Secuencia: Manejo de Errores y Excepciones

```mermaid
sequenceDiagram
    participant C as App Cliente
    participant H as KodanHUB index.php
    participant log as Error Log

    Note over C,H: Escenario 1: Sin Credenciales
    C->>H: POST / (sin headers de auth)
    H->>H: Sin X-KODAN-TOKEN, sin X-KODAN-APP-ID
    H-->>C: HTTP 401 Unauthorized
    H-->>C: {"status": "error", "message": "App no autorizada, pausada o Token inválido."}
    
    Note over C,H: Escenario 2: App Inactiva en Handshake
    C->>H: POST / con X-KODAN-APP-ID (body vacío)
    H->>H: app.status = 'paused'
    H-->>C: HTTP 403 Forbidden
    H-->>C: {"status": "error", "message": "Handshake rechazado: La aplicación está inactiva o pausada en el Hub."}
    
    Note over C,H: Escenario 3: Payload Inválido
    C->>H: POST / con X-KODAN-TOKEN válido
    C->>H: {"action": "invalid", "payload": {}}
    H->>H: action != 'ai' || payload vacío
    H-->>C: HTTP 400 Bad Request
    H-->>C: {"status": "error", "message": "Acción IA no válida o sin contenido."}
    
    Note over C,H: Escenario 4: Sin Servicios Configurados
    C->>H: POST / con token válido y payload correcto
    H->>H: SELECT servicios → 0 rows
    H-->>C: HTTP 400 Bad Request
    H-->>C: {"status": "error", "message": "App sin servicios configurados."}
    
    Note over C,H: Escenario 5: Error Crítico del Sistema
    C->>H: POST / con token válido
    H->>H: Exception: SQLite connection failed
    H->>log: error_log("KODAN HUB CRITICAL: ...")
    H-->>C: HTTP 500 Internal Server Error
    H-->>C: {
    H-->>C:   "status": "error",
    H-->>C:   "message": "HUB CRITICAL: SQLSTATE[HY000] ...",
    H-->>C:   "file": "Medoo.php",
    H-->>C:   "line": 25
    H-->>C: }
```

**Figura 5.1** — Diagrama de errores con 5 escenarios distintos de fallo.

### Matriz de Códigos de Error

| HTTP Status | Condición | Respuesta |
|-------------|-----------|-----------|
| `200 OK` | Handshake exitoso o Proxy exitoso | `{"status": "success", ...}` |
| `400 Bad Request` | Payload inválido o sin servicios | `{"status": "error", "message": "..."}` |
| `401 Unauthorized` | Token inválido o ausente | `{"status": "error", "message": "..."}` |
| `403 Forbidden` | App inactiva/pausada | `{"status": "error", "message": "..."}` |
| `405 Method Not Allowed` | Método no POST | Bloqueado por CORS |
| `500 Internal Server Error` | Excepción no capturada | `{"status": "error", "message": "HUB CRITICAL: ...", "file": "...", "line": N}` |

---

## 6. Diagrama de Secuencia: Rotación de Tokens

```mermaid
sequenceDiagram
    participant Admin as Admin Panel
    participant Act as actions.php
    participant M as Medoo ORM
    participant S as SQLite
    participant App as App Cliente
    participant Hub as KodanHUB

    Note over Admin,Hub: FASE ADMIN: Rotación Manual de Token
    
    Admin->>Act: GET admin/actions.php?action=rotate_token&id=42
    
    Act->>M: $db->select('apps', ['name', 'token'], ['id' => 42])
    M->>S: SELECT name, token FROM apps WHERE id = 42
    S-->>M: ['name' => 'SmartCook', 'token' => 'KDN-SC-A1B2C3D4E5F6789']
    M-->>Act: App encontrada
    
    Note over Act: generateKodanToken('SmartCook')
    Note over Act: = 'KDN-SC-D4E5F6...'
    
    Act->>M: $db->update('apps', [
    Act->>M:     'old_token' => 'KDN-SC-A1B2C3D4E5F6789',
    Act->>M:     'token' => 'KDN-SC-D4E5F6...'
    Act->>M: ], ['id' => 42])
    M->>S: UPDATE apps SET<br/>old_token = 'KDN-SC-A1...',<br/>token = 'KDN-SC-D4E5...'<br/>WHERE id = 42
    S-->>M: rowCount = 1
    M-->>Act: OK
    
    Act-->>Admin: Redirect a index.php?tab=apps-tab

    Note over Admin,Hub: FASE APP: Re-sincronización
    
    App->>Hub: POST / con KDN-SC-A1B2C3... (token viejo)
    Hub->>S: SELECT * FROM apps WHERE token = 'KDN-SC-A1...'
    S-->>Hub: 0 results (token viejO ya no es principal)
    Hub-->>App: HTTP 401 Unauthorized
    
    Note over App: App detecta 401<br/>Limpia token local<br/>Inicia re-handshake
    
    App->>Hub: POST / con X-KODAN-APP-ID (body vacío)
    Hub->>S: SELECT * FROM apps WHERE app_id = ?
    S-->>Hub: App encontrada con nuevo token
    Hub-->>App: 200 {new_kodan_token: "KDN-SC-D4E5F6..."}
    
    Note over App: App persiste nuevo token<br/>Reintenta request original
    App->>Hub: POST / con KDN-SC-D4E5F6...
    Hub-->>App: 200 {status: "success", ...}
```

**Figura 6.1** — Secuencia de rotación manual de token desde el admin hasta la re-sincronización de la app.

---

## 7. Diagrama de Secuencia: Diagnóstico de Servicios (Ping)

```mermaid
sequenceDiagram
    participant Admin as Admin Panel
    participant Act as actions.php
    participant P as Proxy (Gemini/OpenAI)
    participant AI as Proveedor IA

    Admin->>Act: GET actions.php?action=test_service_ajax&id=5
    
    Act->>Act: SELECT s.*, c.protocol, c.identifier, c.endpoint
    Act->>Act: FROM app_services s JOIN ai_catalog c WHERE s.id = 5
    Act->>Act: Servicio encontrado: GPT-4o Mini, protocol=openai-v1
    
    Note over Act: Construir payload de prueba:
    Note over Act: {"messages": [{"role": "user", "content": "respond only with 'pong'"}]}
    
    Act->>P: generateContent(api_key, model, payload, endpoint)
    
    P->>AI: POST /v1/chat/completions
    P->>AI: Authorization: Bearer sk-...
    P->>AI: {"model": "gpt-4o-mini", "messages": [...], "temperature": 0.7}
    
    alt Servicio Responde
        AI-->>P: 200 {"choices": [{"message": {"content": "pong"}}], "usage": {...}}
        P-->>Act: {status: "success", response: "pong", data: {...}}
        
        Act->>Act: LogService::save(..., status='success')
        
        Act-->>Admin: JSON {
        Act-->>Admin:   status: "success",
        Act-->>Admin:   response: "pong",
        Act-->>Admin:   latency: "1.23s",
        Act-->>Admin:   debug_endpoint: "https://api.openai.com/v1/chat/completions"
        Act-->>Admin: }
    else Servicio Falló
        AI-->>P: 500 / Timeout
        P-->>Act: {status: "error", message: "API Error..."}
        
        Act->>Act: LogService::save(..., status='error')
        
        Act-->>Admin: JSON {
        Act-->>Admin:   status: "error",
        Act-->>Admin:   message: "OpenAI API Error 500",
        Act-->>Admin:   latency: "30.02s",
        Act-->>Admin:   debug_response: {...}
        Act-->>Admin: }
    end
```

**Figura 7.1** — Secuencia de diagnóstico de servicios desde el panel de administración.

---

## 8. Diagrama de Secuencia: CORS y OPTIONS Preflight

```mermaid
sequenceDiagram
    participant Browser as Navegador
    participant Hub as KodanHUB
    participant App as Backend App

    Note over Browser,App: Request desde smartcook.kodan.software
    
    Browser->>Hub: OPTIONS / HTTP/1.1
    Browser->>Hub: Origin: https://smartcook.kodan.software
    Browser->>Hub: Access-Control-Request-Method: POST
    Browser->>Hub: Access-Control-Request-Headers: X-KODAN-TOKEN, Content-Type
    
    Note over Hub: Validar Origin con regex:
    Note over Hub: /^https?:\/\/(.*\.?kodan\.software)$/
    Note over Hub: https://smartcook.kodan.software → MATCH ✓
    
    Hub-->>Browser: HTTP 204 No Content
    Hub-->>Browser: Access-Control-Allow-Origin: https://smartcook.kodan.software
    Hub-->>Browser: Access-Control-Allow-Methods: POST, OPTIONS
    Hub-->>Browser: Access-Control-Allow-Headers: Content-Type, X-KODAN-TOKEN, X-KODAN-APP-ID, Authorization
    Hub-->>Browser: Access-Control-Max-Age: 86400
    
    Note over Browser: Preflight OK<br/>Navegador procede con request real
    
    Browser->>Hub: POST / HTTP/1.1
    Browser->>Hub: Origin: https://smartcook.kodan.software
    Browser->>Hub: X-KODAN-TOKEN: KDN-SC-...
    Browser->>Hub: Content-Type: application/json
    Browser->>Hub: {"action": "ai", "payload": {...}}
    
    Hub->>Hub: Validar Origin nuevamente en headers
    Hub->>Hub: Proceder con autenticación y proxy
    
    Hub-->>Browser: HTTP 200 OK
    Hub-->>Browser: Access-Control-Allow-Origin: https://smartcook.kodan.software
    Hub-->>Browser: Content-Type: application/json
    
    alt Origin NO coincide
        Note over Hub: https://evil.com → NO MATCH ✗
        Hub->>Hub: No se envía Access-Control-Allow-Origin
        Browser-->>Browser: CORS Error - Bloqueado por el navegador
    end
```

**Figura 8.1** — Secuencia completa de CORS preflight con OPTIONS y validación de origen.

---

## 9. Diagrama de Secuencia: Administración (Dashboard Stats)

```mermaid
sequenceDiagram
    participant Admin as Admin Panel<br/>admin/index.php
    participant Act as actions.php
    participant M as Medoo ORM
    participant S as SQLite

    Admin->>Act: GET actions.php?action=get_stats_ajax
    
    par Métricas en Paralelo
        Act->>M: SELECT SUM(tokens_in + tokens_out) FROM logs
        M->>S: Query agregada
        S-->>M: 1250000
        M-->>Act: Total tokens: 1,250,000
        
        Act->>M: SELECT COUNT(*) FROM logs
        M->>S: Query conteo
        S-->>M: 8500
        M-->>Act: Total requests: 8,500
        
        Act->>M: SELECT COUNT(*) FROM apps WHERE status = 'active'
        M->>S: Query conteo activas
        S-->>M: 12
        M-->>Act: Apps activas: 12
        
        Act->>M: SELECT COUNT(*) FROM logs WHERE timestamp >= datetime('now', '-1 hour')
        M->>S: Query ventana 1 hora
        S-->>M: 45
        M-->>Act: Requests última hora: 45
        
        Act->>M: SELECT COUNT(*) FROM logs WHERE status = 'error'
        M->>S: Query errores
        S-->>M: 120
        M-->>Act: Errores totales: 120
        
        Act->>M: SELECT a.id, (SELECT SUM(...) FROM logs WHERE app_id = a.id) as app_tokens, (SELECT COUNT(*) FROM logs WHERE app_id = a.id) as app_requests FROM apps a
        M->>S: Query por app + subqueries
        S-->>M: [{id: 42, app_tokens: 500000, app_requests: 3200}, ...]
        M-->>Act: Grid de apps con consumo
    end
    
    Act-->>Admin: JSON {
    Act-->>Admin:   tokens: "1,250,000",
    Act-->>Admin:   requests: "8,500",
    Act-->>Admin:   apps_active: 12,
    Act-->>Admin:   hour: "45",
    Act-->>Admin:   errors: "120",
    Act-->>Admin:   apps_grid: [{id, app_tokens, app_requests}, ...]
    Act-->>Admin: }
    
    Admin->>Admin: Renderizar dashboard con GSAP animations
```

**Figura 9.1** — Secuencia de obtención de estadísticas del dashboard administrativo con queries paralelas.

---

## 10. Mapa Completo de Endpoints

```mermaid
graph TB
    subgraph "Endpoints Públicos (index.php)"
        POST_ROOT[POST /<br/>Entry Point<br/>Content-Type: application/json]
        OPTIONS[OPTIONS /<br/>CORS Preflight]
    end

    subgraph "Endpoints de Administración (actions.php)"
        ADD_APP[add_app<br/>Crear nueva app]
        ROTATE_TOKEN[rotate_token<br/>Rotar KDN token]
        UPDATE_NAME[update_app_name<br/>Renombrar app]
        DELETE_APP[delete_app<br/>Archivar app]
        TOGGLE_STATUS[toggle_status<br/>Activar/Desactivar]
        ADD_MODEL[add_catalog_model<br/>Agregar modelo IA]
        EDIT_MODEL[edit_catalog_model<br/>Editar modelo]
        DELETE_MODEL[delete_catalog_model<br/>Eliminar modelo]
        ADD_SERVICE[add_app_service<br/>Asignar servicio]
        EDIT_SERVICE[edit_app_service<br/>Editar servicio]
        DELETE_SERVICE[delete_service<br/>Desvincular servicio]
        TEST_SERVICE[test_service_ajax<br/>Diagnóstico ping]
        STATS_AJAX[get_stats_ajax<br/>Dashboard metrics]
        ERRORS_AJAX[get_errors_ajax<br/>Error logs]
        CONSUMPTION[get_consumption_stats_ajax<br/>Consumo filtrado]
    end

    subgraph "Endpoints de IA (Proxies)"
        GEMINI[Gemini API<br/>generativelanguage.googleapis.com]
        OPENAI[OpenAI API<br/>api.openai.com]
        NVIDIA[NVIDIA API<br/>integrate.api.nvidia.com]
        GROQ[Groq API<br/>api.groq.com]
    end

    POST_ROOT -->|Handshake| ROTATE_TOKEN
    POST_ROOT -->|Proxy IA| GEMINI
    POST_ROOT -->|Proxy IA| OPENAI
    POST_ROOT -->|Proxy IA| NVIDIA
    POST_ROOT -->|Proxy IA| GROQ
```

**Figura 10.1** — Mapa completo de todos los endpoints del sistema KodanHUB.

---

## Referencias

- Código fuente: `index.php`, `admin/actions.php`, `src/Services/*.php`
- Flujo de handshake: `docs/whitepaper_02_flow_diagrams.md`
- [Mermaid Sequence Diagram Documentation](https://mermaid.js.org/syntax/sequenceDiagram.html)
- [HTTP CORS Specification](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)

---

> **Fin de White Paper 04** — Próximo documento: HTML Maestro Exportable a PDF
