# Guía de Migración: Nuevo Endpoint KODAN-HUB

Este documento contiene las instrucciones obligatorias para que los agentes de **TimeTracker**, **SmartCook** y cualquier otra aplicación conectada realicen la transición al nuevo dominio del Hub sin interrupciones de servicio.

## 1. Cambio de Endpoint Global
El dominio anterior quedará deprecado. Se debe actualizar la constante de conexión en todos los entornos (Local, Staging, Producción).

*   **URL Anterior:** `https://hub.pmaasglobal.com/` (o la configurada previamente)
*   **Nueva URL:** `https://hub.kodan.software/`

### Acciones requeridas:
1.  Localizar el archivo de configuración de entorno (ej: `.env`, `config.js`, `AiService.php`).
2.  Actualizar la variable `KODAN_HUB_URL` o `BASE_URL` del servicio de IA.
3.  Asegurarse de incluir la barra diagonal final (`/`) si el cliente lo requiere.

## 2. Verificación de Conectividad
Antes de dar por finalizada la migración, el agente debe realizar una prueba de "Handshake" para validar que las cabeceras de autenticación siguen siendo válidas en el nuevo dominio.

### Prueba de Humo (Smoke Test):
Realizar una petición GET al nuevo endpoint con los headers actuales:
```http
GET https://hub.kodan.software/
X-KODAN-TOKEN: [TU_TOKEN_ACTUAL]
X-KODAN-APP-ID: [TU_APP_ID]
```
**Resultado esperado:** `{"status":"success","message":"Handshake OK"}` o similar.

## 3. Actualización de CORS y White-listing
Si la aplicación es una Web App (React, Vue, Next.js), el agente debe verificar que el nuevo dominio `hub.kodan.software` no esté siendo bloqueado por políticas de seguridad del navegador o firewalls corporativos.

## 4. Fallback Temporal (Opcional)
Se recomienda mantener una lógica de reintento que, en caso de error 500 o Timeout en el nuevo dominio, intente una última petición al dominio anterior durante las primeras 24 horas de la migración.

---
**IMPORTANTE:** Una vez realizados los cambios, se debe ejecutar el protocolo de despliegue estándar para asegurar que la nueva configuración esté activa en el servidor de producción.

*KODAN Engineering - Protocolo de Migración v1.0*
