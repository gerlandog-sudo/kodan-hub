# Manual Maestro KODAN-HUB v3.2 (Arquitectura Agnóstica)

## 1. Introducción
KODAN-HUB v3.2 es un orquestador centralizado de Inteligencia Artificial. Su objetivo es desacoplar las aplicaciones (TimeTracker, SmartCook, etc.) de los modelos específicos (Gemini, OpenAI, NVIDIA). Esto permite cambiar de proveedor de IA en segundos sin tocar una sola línea de código en las aplicaciones finales.

> [!IMPORTANT]
> **El Hub NO requiere que la aplicación envíe el formato de Google Gemini (`contents`).** Acepta el formato universal `messages` (OpenAI) y lo traduce automáticamente al protocolo del proveedor configurado. Las aplicaciones siempre deben usar el formato `messages`.

## 2. Arquitectura de Comunicación
El Hub funciona mediante un sistema de **"Traducción en Caliente"**:

1.  **App Final**: Envía una petición en **formato universal** (`messages`). No importa qué IA esté configurada.
2.  **Hub Gateway**: Autentica la App mediante un `X-KODAN-TOKEN`.
3.  **Selector de Servicios**: Busca en la base de datos qué modelos tiene asignados esa App y cuál tiene mayor prioridad.
4.  **Proxy Inteligente con Traducción Automática**:
    *   Si el modelo es **Google (Gemini)**: traduce `messages` → `contents` antes de enviarlo.
    *   Si el modelo es **OpenAI/NVIDIA/Mistral**: usa `messages` directamente.
5.  **Normalización de Respuesta**: Sin importar qué proveedor responde, el Hub siempre devuelve la misma estructura (Kodan Standard).
6.  **Fallback**: Si el modelo prioritario falla, el Hub salta automáticamente al siguiente, re-traduciendo el payload al nuevo protocolo.

## 3. Formato de Pedido Universal (ÚNICO formato que debe usar la App)

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

## 4. Respuesta Normalizada (Kodan Standard) — SIEMPRE esta estructura
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
La aplicación SOLO debe leer el campo `response`. Los campos `hub_model` y `provider` son informativos.

## 5. Gestión de Aplicaciones
Las aplicaciones se gestionan en la tabla `apps`.
*   `app_id`: Identificador único (ej: `TT-2a54b0a6`).
*   `token`: Llave de seguridad para las peticiones (`X-KODAN-TOKEN`).

## 6. Catálogo de Modelos (`ai_catalog`)
Define todos los cerebros disponibles en el mercado.
*   **Protocolo `gemini-v1`**: Para modelos nativos de Google. El Hub traduce `messages` → `contents` automáticamente.
*   **Protocolo `openai-v1`**: Para NVIDIA, Mistral, Kimi y OpenAI. El Hub usa `messages` directamente.

## 7. Vinculación y Prioridades (`app_services`)
Es el corazón del sistema. Aquí decides qué modelo usa cada app:
*   **Prioridad 1**: El modelo que se intentará usar siempre.
*   **Prioridad 2+**: Los modelos de respaldo (Fallbacks) que entrarán solo si el 1 falla.

## 8. Procedimiento para Añadir un Nuevo Modelo
1.  Añadir el modelo al `ai_catalog` con su endpoint y protocolo.
2.  Vincularlo a la App en `app_services` con su `api_key` propia.
3.  Establecer la prioridad.

---
*Diseñado por Antigravity para KODAN-HUB Platform — Versión 3.2*
