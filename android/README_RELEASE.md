# BSLoteria Android - Builds y firma

## Flavors

La app usa tres ambientes:

- `emulator`: servidor por defecto `http://10.0.2.2:8000`.
- `lan`: servidor LAN configurable por propiedad Gradle.
- `production`: servidor HTTPS configurable por propiedad Gradle.

## Comportamiento de URL por flavor

- `production` queda fijado a `BSLOTTERY_PRODUCTION_SERVER_URL` o al default `https://bslottery.bsolutions.dev`.
- `production` ignora cualquier `server_url` viejo guardado en el dispositivo.
- Solo `emulator` y `lan` permiten cambiar la URL desde `Settings`.

Esto evita que un telefono actualizado siga apuntando a una IP LAN o a un ambiente viejo por un override persistido.

## Builds utiles

```powershell
.\gradlew.bat :app:assembleEmulatorDebug
.\gradlew.bat :app:assembleLanDebug -PBSLOTTERY_LAN_SERVER_URL=http://192.168.1.50:8000
.\gradlew.bat :app:assembleProductionRelease -PBSLOTTERY_PRODUCTION_SERVER_URL=https://tu-dominio.com
```

Para el VPS actual:

```powershell
.\build-production-release.ps1
```

## Seguridad de red

- `emulator` y `lan` permiten HTTP para XAMPP/LAN privada.
- `production` bloquea cleartext traffic y debe usar HTTPS.
- No construyas `productionRelease` con URL `http://`; usa un dominio TLS real.

## Firma release

1. Crear un keystore seguro fuera del control de versiones:

```powershell
keytool -genkeypair -v -keystore release-keystore.jks -alias bslottery -keyalg RSA -keysize 2048 -validity 10000
```

2. Copiar `keystore.properties.example` como `keystore.properties`.
3. Completar:

```properties
storeFile=release-keystore.jks
storePassword=...
keyAlias=bslottery
keyPassword=...
```

4. Generar release:

```powershell
.\gradlew.bat :app:assembleProductionRelease -PBSLOTTERY_PRODUCTION_SERVER_URL=https://tu-dominio.com
```

`keystore.properties` y archivos `.jks` no deben subirse al repositorio.
