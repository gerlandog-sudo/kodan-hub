# Tech Stack: KODAN-HUB AI Gateway

Análisis técnico del ecosistema de desarrollo y arquitectura de ejecución.

## Core Technologies

| Capa | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Lenguaje** | PHP 8.x | Motor de ejecución del Proxy y API Gateway. |
| **Base de Datos**| SQLite 3 | Persistencia ligera y portable para configuraciones y logs. |
| **Arquitectura** | Service-Oriented | Desacoplamiento de proveedores de IA (Gemini/OpenAI). |
| **Frontend** | Vanilla HTML/JS | Panel de administración de alto rendimiento. |
| **Estilos** | CSS Moderno | Estética Glassmorphism con variables y micro-interacciones. |

## Dependencias Críticas (Composer)

Basado en el análisis de `composer.json` y `bootstrap.php`:

- **Medoo**: Database framework ultra-ligero para queries SQL seguras.
- **Illuminate/Database (Eloquent)**: Utilizado específicamente en el sistema de migraciones y modelos avanzados.
- **Google Generative AI SDK**: Integrado en `GeminiProxy.php`.
- **OpenAI PHP Client**: Integrado en `OpenAIProxy.php`.

## Infraestructura de Seguridad

- **Proxy-Pass Architecture**: Las aplicaciones cliente nunca exponen las API Keys de los proveedores.
- **Handshake Protocol**: Sistema de autoregistro dinámico basado en `X-KODAN-APP-ID`.
- **Security Headers**: Implementación estricta de HSTS, CSP y X-Content-Type-Options.
- **Database Hardening**: Protección de archivos `.sqlite` vía `.htaccess` y fuera de la raíz pública.

## Herramientas de UI/UX

- **Lucide Icons**: Librería de iconografía vectorial.
- **GSAP (GreenSock)**: Motor de animaciones para transiciones fluidas en el panel.
- **Inter (Google Font)**: Tipografía oficial para máxima legibilidad.
