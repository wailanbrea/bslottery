# Licensing and Print Agent

## Licensing

BSLottery uses the central BSolutions API. The project must exist in the
platform with code `bslottery` before a customer activation code is issued.

Required server configuration:

```env
BSOLUTIONS_API_URL=https://license-api.bsolutions.dev/v1
BSOLUTIONS_PROJECT_CODE=bslottery
BSOLUTIONS_DEFAULT_LOCATION_CODE=principal
BSOLUTIONS_DEVICE_TYPE=web_server
BSOLUTIONS_APP_VERSION=1.0.0
```

The server keeps the latest response as a local cache for the offline grace
period, but the central API remains the source of truth. Protected API routes
return a JSON `code` and `mode` when the license is missing, expired, revoked,
suspended, or outside offline grace.

The Android app activates with the same project code using its stable install
UUID as `device_fingerprint`. The license key is stored with Android Keystore
backed encrypted preferences. The app validates at startup and periodically;
network failures only use offline grace after a previous successful validation.

## Windows Agent

The browser bridge talks to the local agent at:

```text
http://127.0.0.1:8765
```

The web app keeps the existing server `print_jobs` queue. `public/js/print-agent.js`
polls jobs for the configured `terminal_key`, sends the plain ticket content to
`POST /api/print`, and acknowledges the job only after the agent confirms it.

The agent must allow the exact BSLottery origin:

```text
https://bslottery.bsolutions.dev
```

Do not put an agent HMAC token in browser JavaScript. Keep the service bound to
loopback, use the explicit origin allowlist, and install the agent as a Windows
service. A terminal key is obtained from the printer/terminal provisioning flow.

## Verification

```text
php artisan test
android\gradlew.bat :app:assembleDebug
```

For the Windows agent, run its Gradle `test` task from a machine with JDK 21
and Gradle available, then verify `/api/status`, `/api/devices`, and a real
test print from the target terminal.
