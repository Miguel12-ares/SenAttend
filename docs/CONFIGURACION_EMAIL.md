# 📧 Guía de Configuración de Correo Electrónico

Esta guía te ayudará a configurar el envío de correos electrónicos para los códigos QR en SENAttend.

---

## 📋 Requisitos Previos

1. **PHPMailer instalado** (ya está en `composer.json`)
2. **Archivo `.env` creado** en la raíz del proyecto
3. **Credenciales de correo** del proveedor que vayas a usar

---

## 🚀 Paso 1: Instalar PHPMailer

Si aún no has instalado las dependencias, ejecuta:

```bash
cd C:\xampp\htdocs\senattend
composer install
```

O si solo quieres instalar PHPMailer:

```bash
composer require phpmailer/phpmailer
```

---

## 📝 Paso 2: Crear/Configurar el archivo `.env`

Crea un archivo `.env` en la raíz del proyecto (`C:\xampp\htdocs\senattend\.env`) con el siguiente contenido:

```env
# Configuración de la aplicación
APP_ENV=local
APP_URL=http://localhost:8000

# Configuración de base de datos
DB_HOST=127.0.0.1
DB_NAME=sena_asistencia
DB_USER=root
DB_PASS=

# ============================================
# CONFIGURACIÓN DE CORREO ELECTRÓNICO
# ============================================

# Servidor SMTP (depende del proveedor)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# Credenciales de correo
SMTP_USERNAME=senattend@gmail.com
SMTP_PASSWORD=tu_contraseña_de_aplicacion

# Remitente
MAIL_FROM_EMAIL=senattend@gmail.com
MAIL_FROM_NAME=SENAttend - Sistema de Asistencia SENA
```

---

## 🔧 Paso 3: Configuración por Proveedor

### 📌 Gmail (Recomendado para desarrollo)

#### Opción A: Contraseña de Aplicación (Recomendado)

1. **Habilita la verificación en 2 pasos** en tu cuenta de Google:
   - Ve a: https://myaccount.google.com/security
   - Activa "Verificación en 2 pasos"

2. **Genera una contraseña de aplicación**:
   - Ve a: https://myaccount.google.com/apppasswords
   - Selecciona "Correo" y "Otro (nombre personalizado)"
   - Escribe "SENAttend" y genera la contraseña
   - **Copia la contraseña de 16 caracteres** (sin espacios)

3. **Configura en `.env`**:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=senattend@gmail.com
SMTP_PASSWORD=xxxx xxxx xxxx xxxx  # La contraseña de aplicación de 16 caracteres
MAIL_FROM_EMAIL=senattend@gmail.com
MAIL_FROM_NAME=SENAttend - Sistema de Asistencia SENA
```

#### Opción B: "Permitir aplicaciones menos seguras" (No recomendado, puede dejar de funcionar)

⚠️ **Nota**: Google puede desactivar esta opción en cualquier momento.

1. Ve a: https://myaccount.google.com/lesssecureapps
2. Activa "Permitir aplicaciones menos seguras"
3. Usa tu contraseña normal de Gmail

---

### 📌 Outlook / Hotmail / Microsoft 365

```env
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=tu_email@outlook.com
SMTP_PASSWORD=tu_contraseña
```

**Nota**: Para Microsoft 365, es posible que necesites una contraseña de aplicación si tienes autenticación de dos factores activada.

---

### 📌 Yahoo Mail

```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=tu_email@yahoo.com
SMTP_PASSWORD=tu_contraseña_de_aplicacion
```

**Nota**: Yahoo requiere una contraseña de aplicación. Genera una en: https://login.yahoo.com/account/security

---

### 📌 Servidor SMTP Personalizado

Si tienes tu propio servidor SMTP:

```env
SMTP_HOST=mail.tudominio.com
SMTP_PORT=587  # o 465 para SSL
SMTP_ENCRYPTION=tls  # o ssl para puerto 465
SMTP_USERNAME=usuario@tudominio.com
SMTP_PASSWORD=tu_contraseña
```

---

## ✅ Paso 4: Probar la Configuración

### Opción A: Probar desde el código

Crea un archivo temporal `test_email.php` en la raíz del proyecto:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use App\Services\EmailService;

$emailService = new EmailService();
$result = $emailService->enviarCorreoPrueba('tu_email_destino@ejemplo.com');

if ($result['success']) {
    echo "✅ Correo enviado exitosamente!\n";
} else {
    echo "❌ Error: " . $result['message'] . "\n";
}
```

Ejecuta:
```bash
php test_email.php
```

### Opción B: Probar desde la aplicación

1. Genera un código QR para un aprendiz que tenga email registrado
2. Verifica que recibas el correo en la bandeja de entrada
3. Revisa los logs en `logs/php-error.log` si hay errores

---

## 🔍 Solución de Problemas Comunes

### Error: "SMTP connect() failed"

**Causas posibles:**
- Credenciales incorrectas
- Puerto o encriptación incorrectos
- Firewall bloqueando la conexión

**Solución:**
1. Verifica las credenciales en `.env`
2. Prueba con diferentes puertos (587, 465, 25)
3. Verifica que XAMPP no esté bloqueando la conexión

---

### Error: "Authentication failed"

**Causas posibles:**
- Usuario o contraseña incorrectos
- Para Gmail: necesitas contraseña de aplicación, no la contraseña normal

**Solución:**
1. Para Gmail: usa una contraseña de aplicación
2. Verifica que el usuario esté correcto
3. Asegúrate de que no haya espacios extra en `.env`

---

### Error: "Could not instantiate mail function"

**Causa:** PHPMailer no está instalado correctamente

**Solución:**
```bash
composer install
# o
composer require phpmailer/phpmailer
```

---

### Los correos no llegan / Van a spam

**Soluciones:**
1. Verifica la carpeta de spam
2. Asegúrate de que `MAIL_FROM_EMAIL` sea un correo válido
3. Configura SPF y DKIM en tu servidor (para producción)
4. Usa un servicio de correo profesional para producción (SendGrid, Mailgun, etc.)

---

## 🎯 Configuración Recomendada para Producción

Para producción, se recomienda usar servicios profesionales:

### SendGrid

```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=apikey
SMTP_PASSWORD=tu_api_key_de_sendgrid
```

### Mailgun

```env
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=postmaster@tudominio.mailgun.org
SMTP_PASSWORD=tu_contraseña_de_mailgun
```

---

## 📚 Recursos Adicionales

- [Documentación de PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [Gmail - Contraseñas de aplicación](https://support.google.com/accounts/answer/185833)
- [Outlook - Configuración SMTP](https://support.microsoft.com/es-es/office/configuraci%C3%B3n-de-outlook-para-enviar-y-recibir-correo-por-smtp-69f58e99-b550-4bc0-89c7-52aadac1eacf)

---

## ✅ Checklist de Configuración

- [ ] PHPMailer instalado (`composer install`)
- [ ] Archivo `.env` creado en la raíz
- [ ] Variables de correo configuradas en `.env`
- [ ] Credenciales correctas (usuario y contraseña)
- [ ] Prueba de envío exitosa
- [ ] Correos llegando a la bandeja de entrada

---

**¿Necesitas ayuda?** Revisa los logs en `logs/php-error.log` para ver errores detallados.

