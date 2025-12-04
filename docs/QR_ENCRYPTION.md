# Cifrado de Códigos QR de Equipos

## 📋 Resumen

Se ha implementado un sistema de cifrado para los códigos QR de equipos, de manera que los datos sensibles no sean visibles en texto plano cuando se escanea el código.

---

## 🔐 Implementación

### Servicio de Cifrado

Se creó el servicio `QREncryptionService` que utiliza **AES-256-GCM** para cifrar y descifrar los datos del QR.

**Ubicación**: `src/GestionEquipos/Services/QREncryptionService.php`

### Características de Seguridad

- **Algoritmo**: AES-256-GCM (Advanced Encryption Standard con Galois/Counter Mode)
- **Longitud de clave**: 32 bytes (256 bits)
- **IV aleatorio**: Cada cifrado genera un vector de inicialización único
- **Autenticación**: GCM proporciona autenticación integrada para detectar modificaciones

---

## ⚙️ Configuración

### Variable de Entorno

Para usar una clave de cifrado personalizada, debe configurar la variable de entorno `QR_ENCRYPTION_KEY` en el archivo `.env`:

```env
QR_ENCRYPTION_KEY=tu_clave_de_32_caracteres_exactos
```

**Importante**: La clave debe tener exactamente **32 caracteres** (32 bytes).

### Generar una Clave Segura

Puede generar una clave segura usando PHP:

```php
<?php
echo bin2hex(random_bytes(16)); // Genera 32 caracteres hexadecimales
// O
echo base64_encode(random_bytes(24)); // Genera 32 caracteres base64
```

O usando OpenSSL desde la línea de comandos:

```bash
# Generar 32 bytes aleatorios en hexadecimal
openssl rand -hex 32

# O en base64 (32 caracteres)
openssl rand -base64 24
```

### Clave por Defecto

Si no se configura `QR_ENCRYPTION_KEY`, el sistema usará una clave por defecto. **⚠️ ADVERTENCIA**: Esta clave es solo para desarrollo. En producción, **DEBE** configurar su propia clave segura.

---

## 🔄 Flujo de Trabajo

### 1. Generación del QR (Cifrado)

Cuando un aprendiz registra un equipo:

1. Se crean los datos del payload (equipo_id, aprendiz_id, numero_serial, marca)
2. Los datos se cifran usando `QREncryptionService::encrypt()`
3. Los datos cifrados se guardan en la base de datos (`qr_equipos.qr_data`)
4. El QR se genera con los datos cifrados (el usuario no puede leer el contenido)

**Archivo**: `src/GestionEquipos/Services/EquipoRegistroService.php`

### 2. Visualización del QR

El QR se genera directamente con los datos cifrados. El usuario final solo ve una cadena de caracteres cifrada que no puede interpretar.

**Archivo**: `src/GestionEquipos/Services/EquipoQRService.php`

### 3. Escaneo del QR (Descifrado)

Cuando el portero escanea el QR:

1. Se reciben los datos cifrados del QR
2. Se intenta descifrar usando `QREncryptionService::decrypt()`
3. Si el descifrado es exitoso, se obtienen los datos originales
4. Se valida y procesa el ingreso/salida del equipo

**Archivo**: `src/GestionEquipos/Services/PorteroIngresoService.php`

---

## 🔙 Compatibilidad hacia Atrás

El sistema mantiene compatibilidad con QRs antiguos que no estaban cifrados:

- Si el descifrado falla, se intenta parsear como JSON (formato antiguo)
- Esto permite que QRs generados antes de esta actualización sigan funcionando
- Los nuevos QRs siempre se generan cifrados

---

## 📝 Formato de Datos

### Antes del Cifrado (JSON)
```json
{
  "equipo_id": 123,
  "aprendiz_id": 456,
  "numero_serial": "ABC123",
  "marca": "Dell"
}
```

### Después del Cifrado (Base64)
```
aGVsbG93b3JsZGhlbGxvd29ybGNoZWxsb3dvcmxk...
```
*(Cadena base64 que contiene: IV + Tag + Datos cifrados)*

---

## 🛠️ Archivos Modificados

1. **`src/GestionEquipos/Services/QREncryptionService.php`** (NUEVO)
   - Servicio de cifrado/descifrado

2. **`src/GestionEquipos/Services/EquipoRegistroService.php`**
   - Modificado para cifrar datos antes de guardarlos

3. **`src/GestionEquipos/Services/PorteroIngresoService.php`**
   - Modificado para descifrar datos al escanear el QR

4. **`public/index.php`**
   - Agregada instanciación del servicio de cifrado

---

## 🔒 Seguridad

### Buenas Prácticas

1. **Nunca** comparta la clave de cifrado públicamente
2. **Nunca** comite el archivo `.env` al repositorio
3. Use una clave diferente para cada entorno (desarrollo, producción)
4. Rotar la clave periódicamente si es necesario
5. Mantenga backups seguros de la clave

### Limitaciones

- Si se pierde la clave de cifrado, los QRs existentes no podrán ser descifrados
- Los QRs antiguos (sin cifrar) seguirán funcionando pero son menos seguros
- El cifrado protege contra lectura casual, pero no contra ataques dirigidos si se obtiene la clave

---

## 🧪 Pruebas

Para probar el sistema:

1. Configure `QR_ENCRYPTION_KEY` en `.env`
2. Registre un nuevo equipo como aprendiz
3. Genere el QR del equipo
4. Intente escanear el QR con un lector genérico (debería mostrar datos cifrados)
5. Escanee el QR con el sistema del portero (debería procesarse correctamente)

---

## 📞 Soporte

Si encuentra problemas con el cifrado:

1. Verifique que `QR_ENCRYPTION_KEY` esté configurada correctamente (32 caracteres)
2. Revise los logs en `logs/php-error.log`
3. Asegúrese de que la extensión OpenSSL esté habilitada en PHP

