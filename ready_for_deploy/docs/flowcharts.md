# Logical Flowcharts: KODAN-HUB

Visualización de la lógica de negocio y flujos de decisión del sistema.

## 1. Flujo de Handshake (Autoregistro)

Este proceso ocurre cuando una aplicación cliente se comunica por primera vez o ha perdido su token.

```mermaid
flowchart TD
    Start([Petición Recibida]) --> CheckToken{¿Viene Token?}
    
    CheckToken -- Sí --> ValidateToken{¿Token Válido?}
    ValidateToken -- Sí --> AuthOK([Autorizado])
    ValidateToken -- No --> Error401([401 Unauthorized])
    
    CheckToken -- No --> CheckAppId{¿Viene App ID?}
    
    CheckAppId -- Sí --> CheckExists{¿App Registrada?}
    CheckExists -- Sí --> GetToken[Recuperar Token Existente]
    CheckExists -- No --> CreateApp[Crear Registro 'apps']
    
    CreateApp --> GenerateToken[Generar Nuevo KDN-TOKEN]
    GetToken --> ResponseHandshake([Responder con new_kodan_token])
    GenerateToken --> ResponseHandshake
    
    CheckAppId -- No --> Error401
```

## 2. Ciclo de Ejecución IA (Proxying)

Lógica de selección de servicio y gestión de fallos (Failover).

```mermaid
flowchart TD
    AuthOK([Autorizado]) --> GetServices[Cargar app_services activos]
    GetServices --> LoopServices{Para cada servicio}
    
    LoopServices -- "Priority 1..N" --> CallAPI[Llamar a Provider API]
    CallAPI --> CheckResult{¿Resultado OK?}
    
    CheckResult -- Sí --> SaveLog[Guardar Log 'success']
    SaveLog --> ExtractTokens[Extraer Conteo de Tokens]
    ExtractTokens --> FinalResponse([Responder al Cliente])
    
    CheckResult -- No --> SaveError[Guardar Log 'error']
    SaveError --> NextService{¿Hay más servicios?}
    NextService -- Sí --> LoopServices
    NextService -- No --> FinalError([Responder 'Todos fallaron'])
```
