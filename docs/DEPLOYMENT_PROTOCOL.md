# Protocolo de Despliegue de Aplicaciones (Agnóstico v3.0)

Este protocolo debe seguirse estrictamente para integrar cualquier aplicación (Web o Mobile) con el KODAN-HUB.

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
`BASE_URL = "https://hub.pmaasglobal.com/"`

Toda petición de IA debe seguir esta estructura de cabeceras:
*   `X-KODAN-TOKEN`: El token obtenido en el Paso 1.
*   `Content-Type`: `application/json`
*   `Expect`: (Vacío, para evitar errores HTTP 100).

## Paso 4: Lógica de Rotación de Tokens (Obligatorio)
La App debe estar preparada para leer la cabecera `X-KODAN-NEW-TOKEN` en CUALQUIER respuesta del Hub. Si esta cabecera viene, la App debe actualizar su token local inmediatamente para no perder la conexión.

## Paso 5: Verificación
Ejecutar el script de diagnóstico (`verify_hub_v3.php`) para confirmar el "PONG" de la IA antes de abrir el servicio a los usuarios.

---
*Protocolo Estándar Antigravity - Mayo 2026*
