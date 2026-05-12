# QA & Consistency Report: KODAN-HUB

Informe de hallazgos técnicos tras la auditoría de documentación v3.2.

## Hallazgos de Arquitectura

### 1. Dualidad de Motores de DB (Medoo vs Eloquent)
- **Observación**: El proyecto utiliza `Medoo` para el core del Gateway (`index.php`) y el Panel Admin, mientras que las migraciones y los Modelos están basados en `Eloquent`.
- **Riesgo**: Inconsistencia en la gestión de tipos y duplicidad de lógica de conexión.
- **Recomendación**: Unificar bajo un solo motor en la v4.0 para reducir la deuda técnica.

### 2. Discrepancia en Rutas de Base de Datos
- **Observación**: `bootstrap.php` busca `database/database.sqlite`, pero el archivo de datos actual reside en `docs/hub.sqlite`.
- **Acción**: Se ha documentado el esquema basándose en la estructura lógica, pero se requiere verificar la configuración en producción para evitar fallos de "File Not Found".

### 3. Falta de Foreign Key Constraints (SQLite)
- **Observación**: Aunque existen relaciones lógicas entre `apps`, `app_services` y `logs`, las tablas no implementan `ON DELETE CASCADE` de forma explícita en todos los niveles.
- **Riesgo**: Generación de "Registros Huérfanos" al eliminar una aplicación desde el panel administrativo.

## Verificación de Seguridad

- **Protección de Datos**: Los archivos `.htaccess` están correctamente configurados para bloquear el acceso directo a la base de datos y archivos de configuración.
- **Inyección SQL**: El uso de `Medoo` con sentencias preparadas en el gateway garantiza la inmunidad contra SQLi básica.

## Estado Final de Documentación
- [x] **Esquema de Datos**: Reconstruido y verificado.
- [x] **Lógica de Negocio**: Mapeada en diagramas de flujo.
- [x] **Protocolos de IA**: Secuencias documentadas para ambos proveedores.
