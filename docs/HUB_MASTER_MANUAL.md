# Manual Maestro KODAN-HUB v3.0 (Arquitectura Agnóstica)

## 1. Introducción
KODAN-HUB v3.0 es un orquestador centralizado de Inteligencia Artificial. Su objetivo es desacoplar las aplicaciones (TimeTracker, SmartCook, etc.) de los modelos específicos (Gemini, OpenAI, NVIDIA). Esto permite cambiar de proveedor de IA en segundos sin tocar una sola línea de código en las aplicaciones finales.

## 2. Arquitectura de Comunicación
El Hub funciona mediante un sistema de **"Traducción en Caliente"**:

1.  **App Final**: Envía una petición en formato estándar (Gemini format).
2.  **Hub Gateway**: Autentica la App mediante un `X-KODAN-TOKEN` o `X-KODAN-APP-ID`.
3.  **Selector de Servicios**: Busca en la base de datos qué modelos tiene asignados esa App y cuál tiene mayor prioridad.
4.  **Proxy Inteligente**: 
    *   Si el modelo es **Google (Gemini)**, pasa la petición directa.
    *   Si el modelo es **OpenAI/NVIDIA/Mistral**, traduce el mensaje al formato `messages` de OpenAI antes de enviarlo.
5.  **Fallback**: Si el modelo prioritario falla, el Hub salta automáticamente al siguiente en la lista sin que el usuario note nada.

## 3. Gestión de Aplicaciones
Las aplicaciones se gestionan en la tabla `apps`. 
*   `app_id`: Identificador único (ej: `TT-2a54b0a6`).
*   `token`: Llave de seguridad para las peticiones (`X-KODAN-TOKEN`).

## 4. Catálogo de Modelos (`ai_catalog`)
Define todos los cerebros disponibles en el mercado.
*   **Protocolo `gemini-v1`**: Para modelos nativos de Google.
*   **Protocolo `openai-v1`**: Para NVIDIA, Mistral, Kimi y OpenAI. El Hub traduce automáticamente a este formato.

## 5. Vinculación y Prioridades (`app_services`)
Es el corazón del sistema. Aquí decides qué modelo usa cada app:
*   **Prioridad 1**: El modelo que se intentará usar siempre.
*   **Prioridad 2+**: Los modelos de respaldo (Fallbacks) que entrarán solo si el 1 falla.

## 6. Procedimiento para Añadir un Nuevo Modelo
1.  Añadir el modelo al `ai_catalog` con su endpoint y protocolo.
2.  Vincularlo a la App en `app_services` con su `api_key` propia.
3.  Establecer la prioridad.

---
*Diseñado por Antigravity para KODAN-HUB Platform*
