# Sequence Diagrams: Protocolo KODAN-Sync

Interacci贸n entre actores del ecosistema KODAN.

## Transacci贸n de IA Exitosa

Muestra el flujo est谩ndar de una consulta desde que sale de la App cliente hasta que retorna la respuesta procesada.

```mermaid
sequenceDiagram
    participant App as Aplicaci贸n Cliente
    participant Hub as KODAN-HUB (Proxy)
    participant DB as SQLite Master
    participant AI as Provider (Gemini/OpenAI)

    App->>Hub: POST /index.php (X-KODAN-TOKEN + Payload)
    Hub->>DB: Validar Token en tabla 'apps'
    DB-->>Hub: App Identificada (ID: 12)
    
    Hub->>DB: Obtener servicios (app_services JOIN ai_catalog)
    DB-->>Hub: Lista de Modelos (Prioridad 1: Gemini)

    Note over Hub, AI: Inicio de Transacci贸n IA
    Hub->>AI: POST API Request (Payload + API Key)
    AI-->>Hub: JSON Response (Content + Usage)
    
    Hub->>Hub: Calcular Latencia y Extraer Tokens
    Hub->>DB: INSERT INTO logs (success)
    
    Hub-->>App: JSON { status: success, response: "...", usage: {...} }
    Note over App: Actualiza UI con respuesta de IA
```

## Flujo de Failover (Recuperaci贸n ante Fallos)

Muestra el comportamiento del sistema cuando el proveedor principal falla y se activa el secundario.

```mermaid
sequenceDiagram
    participant Hub as KODAN-HUB
    participant P1 as Provider 1 (Principal)
    participant P2 as Provider 2 (Respaldo)

    Hub->>P1: Intento 1 (Gemini-1.5-Pro)
    P1-->>Hub: ERROR 500 / Overloaded
    
    Hub->>Hub: Registro de Error en Logs
    
    Note right of Hub: Activando Failover por Prioridad
    
    Hub->>P2: Intento 2 (Gemma-2-9b)
    P2-->>Hub: 脡xito (JSON Response)
    
    Hub-->>Hub: Registro de 脡xito en Logs
    Hub-->>App: Respuesta del Modelo de Respaldo
```

## Handshake Idempotente (Auto-Onboarding & Shared Identity)

Flujo utilizado para registro inicial o recuperaci髇 de tokens en despliegues multi-usuario.

`mermaid
sequenceDiagram
    participant App as App (Cualquier Instancia)
    participant Hub as KODAN-HUB
    participant DB as SQLite Master

    App->>Hub: POST / [Headers: X-KODAN-APP-ID] (Body Vac韔)
    Hub->>DB: SELECT * FROM apps WHERE app_id = ?
    
    alt Registro Nuevo
        DB-->>Hub: NULL
        Hub->>DB: INSERT INTO apps (ID, Token, Status)
        Hub-->>App: 200 OK { new_kodan_token: " KDN-NEW\, message: \Registrado\ }
 else Recuperaci髇 (Shared Identity)
 DB-->>Hub: App Record (Token: KDN-EXISTING)
 Hub-->>App: 200 OK { new_kodan_token: \KDN-EXISTING\, message: \Sincronizado\ }
 end
`
