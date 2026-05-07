# Documentación Técnica KODAN-HUB v3.0

## 1. Arquitectura del Sistema
KODAN-HUB es una pasarela (Gateway) **agnóstica de servicios**. Centraliza la seguridad de APIs críticas y, desde la v3.0, gestiona la **infraestructura de internacionalización dinámica** para todas las aplicaciones del ecosistema Antigravity.

### 1.1 Diagrama de Entidades (ERD) v3.0
Representación de las entidades principales incluyendo el motor de traducciones.

```mermaid
erDiagram
    APPS {
        int id PK
        string name "Nombre de la app"
        string token "Token maestro (KDN-...)"
        string app_id "UUID único global"
        string status "active | maintenance | pending"
        string gemini_key "API Key central"
    }

    TRANSLATIONS {
        int id PK
        int app_id FK "Relación con APPS.id"
        string language_code "ISO (es, en, pt...)"
        string content_json "Diccionario traducido"
        string version_hash "v3_SHA del maestro"
        datetime created_at
    }

    LOGS {
        int id PK
        int app_id FK
        string model "IA Model usado"
        int tokens_in "Prompt tokens"
        int tokens_out "Completion tokens"
        string status "success | error"
        float latency
    }

    APPS ||--o{ TRANSLATIONS : "gestiona idiomas para"
    APPS ||--o{ LOGS : "registra actividad en"
```

---

## 2. Flujos de Operación Avanzados

### 2.1 Sincronización Multilingüe (i18n Sync)
Este flujo garantiza que todas las aplicaciones tengan sus textos actualizados sin intervención manual.

1.  **Detección de Cambio**: La App genera un hash (`v3_hash`) basado en su archivo local `es.json`.
2.  **Registro de Maestro (Bypass)**: Si el hash cambia, la App envía el nuevo `es.json` al HUB. El HUB persiste este JSON directamente (sin costo de IA) y **elimina todas las traducciones de otros idiomas** para esa App.
3.  **Traducción On-Demand**: Cuando la App solicita un idioma (ej: `en`), el HUB detecta que no hay cache (fue borrado en el paso 2), invoca a Gemini para traducir el nuevo maestro y guarda el resultado.

### 2.2 Protocolo de Hash de Versión
Para evitar estados inconsistentes, el sistema utiliza un prefijo de versión en los hashes (`v3_`). 
*   **Invalida Caches Locales**: Si el hash del archivo físico no coincide con el guardado en el teléfono, se borra el cache local.
*   **Invalida Caches de Servidor**: Si el hash enviado por la App no coincide con el del HUB, el HUB re-traduce todo el proyecto.

---

## 3. Especificación de API (i18n)

### 3.1 Endpoint de Traducción
`POST /index.php`

#### Payload (Action: get_translation)
```json
{
  "action": "get_translation",
  "lang": "en",
  "v_hash": "v3_1669574185",
  "source_json": { ... }
}
```

#### Reglas de Negocio del HUB:
- **Idioma 'es'**: Bypass total. El HUB guarda el JSON y lo marca como `master_sync`.
- **Otros Idiomas**: Invocación a Gemini Flash con un prompt técnico estricto para mantener la integridad de las llaves JSON.
- **Cache Hit**: Si `v_hash` coincide con el registro en DB, se devuelve el JSON en < 100ms sin tocar la IA.

---

## 4. IA Agnostic Gateway (Integración AI v3.2)
Desde la versión 3.2, KODAN-HUB actúa como un orquestador agnóstico de modelos de lenguaje, permitiendo el intercambio caliente de proveedores (Google Gemini, OpenAI, NVIDIA, etc.) sin modificar las aplicaciones cliente.

### 4.1 Formato de Pedido Universal
Las aplicaciones deben enviar un payload genérico. El HUB se encarga de traducirlo al protocolo específico del proveedor configurado.

**Endpoint:** `POST /index.php`  
**Headers:** `X-KODAN-TOKEN: [Token de la App]`

#### Payload (Action: ai)
```json
{
  "action": "ai",
  "payload": {
    "messages": [
      { "role": "user", "content": "Tu prompt aquí" }
    ],
    "temperature": 0.7,
    "max_tokens": 4096
  }
}
```
*Nota: También se acepta el formato nativo de Gemini (`contents`) o un string simple.*

### 4.2 Respuesta Normalizada (Kodan Standard)
Sin importar qué IA responda, el HUB siempre entregará esta estructura limpia:

```json
{
  "status": "success",
  "response": "Texto generado por la IA",
  "usage": {
    "prompt_tokens": 120,
    "completion_tokens": 50,
    "total_tokens": 170
  },
  "hub_model": "gemini-1.5-flash",
  "provider": "Google"
}
```

### 4.3 Mecanismo de Fallback
Si el servicio de prioridad 1 falla, el HUB automáticamente re-intenta con el siguiente servicio configurado en la tabla `app_services`, re-formateando el prompt al vuelo para el nuevo protocolo.

---

## 5. Gestión de Seguridad
- **Ocultamiento de Keys**: Ninguna aplicación cliente posee las API Keys de los proveedores.
- **Handshake v2.0**: Registro automático de dispositivos nuevos en estado `pending`.
- **Mantenimiento Global**: Posibilidad de pausar todas las apps desde una sola bandera en el HUB.

---

## 6. Instrucciones de Instalación y Migración
1.  **Instalación Base**: Subir archivos al servidor.
2.  **Base de Datos**: Ejecutar `setup_db.php`.
3.  **Migración Agnostic**: Ejecutar `migration_v6_agnostic_hub.php` para habilitar el catálogo global y prioridades.
4.  **Configuración de App**: Vincular modelos del catálogo a la aplicación desde el panel administrativo.
