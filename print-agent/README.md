# BSLotery Print Agent

Agente de impresión local para BSLotery. Se ejecuta en el PC del cajero como un proceso Java y recibe trabajos de impresión desde el navegador web via `http://127.0.0.1:8765`.

## Requisitos

- Java 17 o superior (`java -version`)
- Maven 3.8+ para compilar (o usar el JAR pre-compilado)
- Impresora térmica conectada por USB o red

## Compilar

```bash
cd print-agent
mvn clean package -DskipTests
# Genera: target/bslottery-print-agent.jar
```

## Configurar

Antes de ejecutar, editar `application.yml` con el token correcto:

```yaml
agent:
  token: "EL_MISMO_TOKEN_QUE_EN_EL_.ENV_DE_LARAVEL"
```

El token debe coincidir con `PRINT_AGENT_TOKEN` en el `.env` de BSLotery.

## Ejecutar

```bash
java -jar target/bslottery-print-agent.jar
```

O con variables de entorno:

```bash
java -DPRINT_AGENT_TOKEN=mi_token -DPRINT_AGENT_PORT=8765 -jar bslottery-print-agent.jar
```

El agente escucha **solo en 127.0.0.1** (localhost). No acepta conexiones externas.

## API REST

| Método | Ruta           | Auth  | Descripción                       |
|--------|----------------|-------|-----------------------------------|
| GET    | /api/status    | No    | Verificar que el agente está activo|
| GET    | /api/printers  | Token | Listar impresoras del sistema      |
| POST   | /api/print     | Token | Enviar trabajo de impresión        |
| POST   | /api/test      | Token | Imprimir página de prueba          |

### Ejemplo: imprimir

```json
POST /api/print
Authorization: Bearer mi_token
Content-Type: application/json

{
  "job_uuid": "abc-123",
  "printer_name": "EPSON TM-T20III",
  "connection_type": "USB",
  "content": "texto pre-formateado del ticket",
  "paper_width": "58MM"
}
```

`connection_type` puede ser: `USB`, `WINDOWS_SHARED`, `NETWORK`.

Para `NETWORK`, el campo `printer_name` debe ser `host:puerto` (ej. `192.168.1.50:9100`).

## Impresoras de red

La impresora de red debe ser accesible en el puerto RAW (9100 por defecto).
Ejemplo de configurador de IP en impresoras Epson/Star/Bixolon.

## Logs

Los logs se guardan en `logs/print-agent.log` junto al JAR.

## Autostart en Windows

Para que el agente inicie automáticamente con Windows, crear un archivo `.bat`:

```bat
@echo off
java -jar "C:\BSLotery\print-agent\bslottery-print-agent.jar"
```

Y agregar un acceso directo en `shell:startup` (Win+R → `shell:startup`).
