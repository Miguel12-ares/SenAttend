# Módulo QR - SENAttend

## Descripción General

El módulo QR de SENAttend permite el registro automático de asistencia mediante códigos QR, separando las responsabilidades entre aprendices e instructores:

- **Aprendices**: Generan su código QR personal con su información
- **Instructores**: Escanean códigos QR para registrar asistencia automáticamente

## Características

### ✅ Funcionalidades Implementadas

1. **Generación de Códigos QR**
   - Búsqueda de aprendiz por documento
   - Generación de código QR personalizado con diseño SENA
   - Descarga de código QR en formato PNG
   - Información codificada: ID aprendiz, documento, nombre, timestamp

2. **Escaneo de Códigos QR**
   - Selección de ficha para registro
   - Escaneo en tiempo real con cámara
   - Registro automático de asistencia
   - Detección automática de tardanzas (hora límite: 07:30 AM)
   - Historial de registros en la sesión actual
   - Estadísticas en tiempo real

3. **Validaciones de Seguridad**
   - Verificación de permisos por rol
   - Validación de aprendiz activo
   - Validación de ficha activa
   - Verificación de vinculación aprendiz-ficha
   - Prevención de duplicados (mismo día)
   - Headers de seguridad en todas las peticiones

## Arquitectura

### Estructura de Archivos

```
senattend/
├── src/
│   └── Controllers/
│       └── QRController.php          # Controlador principal del módulo
├── views/
│   └── qr/
│       ├── generar.php                # Vista para generar QR (aprendices)
│       └── escanear.php               # Vista para escanear QR (instructores)
├── public/
│   └── css/
│       └── qr.css                     # Estilos del módulo QR
└── docs/
    └── MODULO_QR.md                   # Esta documentación
```

### Rutas Implementadas

#### Rutas Web (GET)
- `/qr/generar` - Vista para generar código QR (todos los usuarios)
- `/qr/escanear` - Vista para escanear código QR (instructores, coordinadores, admins)

#### API (REST)
- `GET /api/qr/buscar?documento=xxx` - Buscar aprendiz por documento
- `POST /api/qr/procesar` - Procesar escaneo y registrar asistencia

## Uso del Módulo

### 1. Generar Código QR (Aprendiz)

**Acceso**: Todos los usuarios autenticados

1. Acceder a `/qr/generar` desde el dashboard
2. Ingresar número de documento
3. Verificar información del aprendiz
4. Descargar código QR generado
5. Guardar o imprimir el código QR

**Datos codificados en el QR:**
```json
{
  "aprendiz_id": 123,
  "documento": "1234567890",
  "nombre": "Juan Pérez García",
  "timestamp": "2024-01-15T10:30:00.000Z"
}
```

### 2. Escanear Código QR (Instructor)

**Acceso**: Instructores, Coordinadores, Administradores

1. Acceder a `/qr/escanear` desde el dashboard
2. Seleccionar la ficha activa
3. Iniciar el escáner (permitir acceso a la cámara)
4. Acercar el código QR del aprendiz a la cámara
5. El sistema registra automáticamente la asistencia
6. Ver historial y estadísticas de registros

**Flujo de Registro:**
- Hora <= 07:30 AM → Estado: **PRESENTE**
- Hora > 07:30 AM → Estado: **TARDANZA**

## Integraciones

### Bibliotecas Externas

1. **qr-code-styling** (v1.9.2)
   - Generación de códigos QR personalizados
   - Estilos y colores customizables
   - Descarga en formato PNG
   - CDN: `https://cdn.jsdelivr.net/npm/qr-code-styling@1.9.2/lib/qr-code-styling.js`

2. **html5-qrcode** (v2.3.8)
   - Escaneo de códigos QR con cámara
   - Compatible con dispositivos móviles y escritorio
   - Soporte para cámara frontal y trasera
   - CDN: `https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js`

### Servicios del Sistema

- **AsistenciaService**: Registro de asistencia
- **AuthService**: Autenticación y autorización
- **AprendizRepository**: Consulta de aprendices
- **FichaRepository**: Consulta de fichas

## Seguridad

### Validaciones Implementadas

1. **Autenticación**
   - Middleware de autenticación en todas las rutas
   - Verificación de sesión activa

2. **Autorización**
   - Verificación de rol para escaneo QR
   - Validación de permisos por operación

3. **Validaciones de Datos**
   - Sanitización de entradas
   - Verificación de formato JSON
   - Validación de campos requeridos
   - Verificación de existencia de entidades

4. **Prevención de Duplicados**
   - Constraint único: aprendiz + ficha + fecha
   - Validación en capa de servicio

5. **Headers de Seguridad**
   - X-Content-Type-Options: nosniff
   - X-Frame-Options: DENY
   - X-XSS-Protection
   - Cache-Control para APIs

### Auditoría

Todas las operaciones críticas se registran en logs con:
- Timestamp
- Usuario que ejecuta la acción
- Datos procesados
- IP del cliente
- User-Agent

## API Reference

### POST /api/qr/procesar

Procesa un código QR escaneado y registra la asistencia.

**Headers:**
```
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

**Body:**
```json
{
  "qr_data": "{\"aprendiz_id\":123,\"documento\":\"1234567890\",\"nombre\":\"Juan Pérez\"}",
  "ficha_id": 5
}
```

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "asistencia_id": 456,
    "aprendiz": {
      "id": 123,
      "documento": "1234567890",
      "nombre": "Juan Pérez García"
    },
    "estado": "presente",
    "fecha": "2024-01-15",
    "hora": "07:15:00"
  },
  "message": "Asistencia registrada exitosamente"
}
```

**Errores Posibles:**
- `400` - Datos inválidos o aprendiz no vinculado a la ficha
- `403` - Sin permisos para registrar asistencia
- `404` - Aprendiz o ficha no encontrados
- `500` - Error interno del servidor

### GET /api/qr/buscar

Busca un aprendiz por documento para generación de QR.

**Query Parameters:**
- `documento` (string, requerido): Número de documento del aprendiz

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "aprendiz": {
      "id": 123,
      "documento": "1234567890",
      "nombre": "Juan",
      "apellido": "Pérez García",
      "nombre_completo": "Juan Pérez García",
      "codigo_carnet": "ABC123"
    },
    "fichas": [
      {
        "id": 5,
        "numero_ficha": "2626339",
        "nombre": "ADSI - Análisis y Desarrollo de Sistemas de Información"
      }
    ]
  },
  "message": "Aprendiz encontrado"
}
```

**Errores Posibles:**
- `400` - Aprendiz no activo o sin fichas vinculadas
- `404` - Aprendiz no encontrado
- `500` - Error interno del servidor

## Configuración

### Hora Límite de Tardanza

Por defecto: **07:30 AM**

Para modificar, editar en `QRController.php`:
```php
$horaLimite = '07:30:00'; // Formato HH:MM:SS
```

### Personalización de QR

Los códigos QR se generan con el estilo corporativo SENA:
- Colores: Verde SENA (#39A900)
- Esquinas redondeadas
- Tamaño: 300x300 px

Para personalizar, editar en `views/qr/generar.php`:
```javascript
qrCode = new QRCodeStyling({
    width: 300,
    height: 300,
    // ... más opciones
});
```

## Flujo de Trabajo Típico

### Escenario 1: Registro de Asistencia Matutino

1. **07:00 AM** - Instructor abre `/qr/escanear`
2. **07:00 AM** - Selecciona ficha "2626339 - ADSI"
3. **07:00 AM** - Inicia escáner
4. **07:15 AM** - Aprendiz Juan Pérez muestra su QR
5. **07:15 AM** - Sistema registra: **PRESENTE** (antes de las 07:30)
6. **07:35 AM** - Aprendiz María López muestra su QR
7. **07:35 AM** - Sistema registra: **TARDANZA** (después de las 07:30)
8. **08:00 AM** - Instructor detiene el escáner
9. **08:00 AM** - Revisa historial: 25 presentes, 3 tardanzas

### Escenario 2: Aprendiz Genera su QR

1. Aprendiz accede a `/qr/generar`
2. Ingresa su documento: "1234567890"
3. Sistema valida y muestra información
4. Sistema genera código QR con sus datos
5. Aprendiz descarga el QR: `QR_1234567890_Juan_Perez.png`
6. Aprendiz imprime o guarda en su celular

## Troubleshooting

### Problema: Cámara no funciona

**Causas posibles:**
- Navegador no tiene permisos de cámara
- Dispositivo no tiene cámara
- Navegador no soporta getUserMedia

**Solución:**
1. Verificar permisos del navegador
2. Usar navegador compatible (Chrome, Firefox, Safari)
3. En móviles, usar HTTPS (requerido para acceso a cámara)

### Problema: QR no se escanea

**Causas posibles:**
- Código QR dañado o de baja calidad
- Iluminación insuficiente
- Código QR muy pequeño o muy grande

**Solución:**
1. Regenerar código QR
2. Mejorar iluminación
3. Ajustar distancia entre QR y cámara

### Problema: "Aprendiz no pertenece a esta ficha"

**Causa:** El aprendiz no está vinculado a la ficha seleccionada

**Solución:**
1. Verificar vinculación en módulo de Aprendices
2. Vincular aprendiz a la ficha correspondiente
3. Intentar nuevamente el escaneo

## Mejoras Futuras

### Fase 2 (Corto Plazo)

- [ ] Código QR dinámico con timeout (renovación automática)
- [ ] Escaneo masivo (múltiples QR en una sesión)
- [ ] Notificaciones push al registrar asistencia
- [ ] Exportación de historial de escaneos

### Fase 3 (Mediano Plazo)

- [ ] Geolocalización en registro QR
- [ ] Firma digital del instructor
- [ ] QR con foto del aprendiz
- [ ] Modo offline con sincronización posterior

### Fase 4 (Largo Plazo)

- [ ] Integración con app móvil nativa
- [ ] Reconocimiento facial complementario
- [ ] Dashboard de estadísticas QR
- [ ] Exportación a RFID/NFC

## Compatibilidad

### Navegadores Soportados

| Navegador | Versión Mínima | Soporte QR | Soporte Cámara |
|-----------|---------------|------------|----------------|
| Chrome    | 53+           | ✅         | ✅             |
| Firefox   | 49+           | ✅         | ✅             |
| Safari    | 11+           | ✅         | ✅             |
| Edge      | 79+           | ✅         | ✅             |
| Opera     | 40+           | ✅         | ✅             |

### Dispositivos Móviles

- ✅ Android 5.0+
- ✅ iOS 11.0+
- ✅ Tablets
- ✅ Laptops con cámara

## Contacto y Soporte

Para reportar problemas o sugerencias sobre el módulo QR:

- Repositorio: `senattend/`
- Documentación: `/docs/MODULO_QR.md`
- Logs: `/logs/php-error.log`

---

**Versión del Módulo:** 1.0  
**Última Actualización:** Noviembre 2024  
**Autor:** Equipo SENAttend  

