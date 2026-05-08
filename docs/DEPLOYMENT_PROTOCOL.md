# Protocolo de Despliegue de Aplicaciones (Agnóstico v3.2)

Este protocolo debe seguirse estrictamente para integrar cualquier aplicación (Web o Mobile) con el KODAN-HUB.

> [!IMPORTANT]
> **El Hub NO requiere el formato Gemini (`contents`).** El formato universal es `messages`. El Hub lo traduce internamente al protocolo que corresponda.

## Paso 1: Registro de la Aplicación en el Hub
1.  **Handshake Inicial**: La aplicación debe intentar una llamada al Hub con las cabeceras `X-KODAN-APP-ID` y `X-KODAN-APP-NAME` (sin cuerpo POST).
2.  **Generación de Token**: El Hub responderá con un `new_kodan_token`. Este token debe guardarse en la base de datos de la App (tabla `system_config` o similar).

## Paso 2: Configuración de Servicios (IA)
1.  Entrar al panel administrativo del Hub.
2.  Vincular la nueva App con al menos **DOS (2) modelos** del catálogo:
    *   **Modelo Principal**: (Ej: Gemini 1.5 Flash). Prioridad 1.
    *   **Modelo Fallback**: (Ej: Mistral o NVIDIA). Prioridad 2.
3.  Asegurarse de poner las API Keys correspondientes para cada servicio.

## Paso 3: Implementación del AiService en la App
La aplicación NO debe contener URLs de Google o NVIDIA. Solo debe tener una constante:
`BASE_URL = "https://hub.kodan.software/"`

**Formato de petición (ÚNICO, invariable):**
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

**Headers obligatorios:**
```
X-KODAN-TOKEN: KDN-XXXXXXXX
Content-Type: application/json
```

**Respuesta que siempre devuelve el Hub (Kodan Standard):**
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
La aplicación solo debe leer el campo `response`. Si `status` es `error`, leer el campo `message`.

## Paso 4: Lógica de Rotación de Tokens (Obligatorio)
La App debe estar preparada para leer la cabecera `X-KODAN-NEW-TOKEN` en CUALQUIER respuesta del Hub. Si esta cabecera viene, la App debe actualizar su token local inmediatamente para no perder la conexión.

## Paso 5: Verificación
Usar el botón de **Test Neural** en el panel administrativo para confirmar el "PONG" de la IA antes de abrir el servicio a los usuarios.

---
*Protocolo Estándar Antigravity — Versión 3.2 — Mayo 2026*
