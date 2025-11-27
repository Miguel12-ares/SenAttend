<?php

namespace App\Services;

use App\Repositories\CodigoQRRepository;
use App\Repositories\AprendizRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;

/**
 * Servicio para gestión de códigos QR con expiración
 * Maneja la generación, validación y envío por correo
 */
class QRService
{
    private CodigoQRRepository $codigoQRRepository;
    private AprendizRepository $aprendizRepository;
    private EmailService $emailService;
    private int $expirationMinutes = 3;

    public function __construct(
        CodigoQRRepository $codigoQRRepository,
        AprendizRepository $aprendizRepository,
        EmailService $emailService
    ) {
        $this->codigoQRRepository = $codigoQRRepository;
        $this->aprendizRepository = $aprendizRepository;
        $this->emailService = $emailService;
    }

    /**
     * Genera un nuevo código QR para un aprendiz
     * Incluye token único, expiración de 3 minutos y envío por correo
     */
    public function generarCodigoQR(int $aprendizId, bool $enviarPorCorreo = true): array
    {
        try {
            // Verificar que el aprendiz existe
            $aprendiz = $this->aprendizRepository->findById($aprendizId);
            if (!$aprendiz) {
                return [
                    'success' => false,
                    'message' => 'Aprendiz no encontrado'
                ];
            }

            // Verificar que el aprendiz tiene email si se requiere envío
            if ($enviarPorCorreo && empty($aprendiz['email'])) {
                return [
                    'success' => false,
                    'message' => 'El aprendiz no tiene un correo electrónico registrado'
                ];
            }

            // Generar token único
            $token = $this->generarTokenUnico();

            // Calcular fecha de expiración (3 minutos desde ahora)
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime("+{$this->expirationMinutes} minutes"));

            // Preparar datos del QR (formato: TOKEN|ID_APRENDIZ|FECHA)
            $qrData = $token . '|' . $aprendizId . '|' . date('Y-m-d');

            // Guardar en base de datos
            $codigoQRId = $this->codigoQRRepository->create([
                'token' => $token,
                'id_aprendiz' => $aprendizId,
                'qr_data' => $qrData,
                'fecha_expiracion' => $fechaExpiracion
            ]);

            // Generar imagen del QR en base64 para el correo
            $qrImageBase64 = $this->generarImagenQR($qrData);

            // Enviar por correo si está habilitado
            $emailResult = null;
            if ($enviarPorCorreo && !empty($aprendiz['email'])) {
                $emailResult = $this->emailService->enviarCodigoQR(
                    $aprendiz['email'],
                    $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                    $qrData,
                    $qrImageBase64
                );
            }

            // Obtener el código QR recién creado para obtener fecha_generacion
            $codigoQRCreado = $this->codigoQRRepository->findByToken($token);

            return [
                'success' => true,
                'message' => 'Código QR generado exitosamente',
                'data' => [
                    'token' => $token,
                    'qr_data' => $qrData,
                    'fecha_generacion' => $codigoQRCreado['fecha_generacion'] ?? date('Y-m-d H:i:s'),
                    'fecha_expiracion' => $fechaExpiracion,
                    'aprendiz' => [
                        'id' => $aprendizId,
                        'nombre' => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
                        'email' => $aprendiz['email'] ?? null
                    ],
                    'email_enviado' => $emailResult ? $emailResult['success'] : false,
                    'email_message' => $emailResult ? $emailResult['message'] : null
                ]
            ];
        } catch (Exception $e) {
            error_log("Error generating QR code: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al generar el código QR: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida un código QR escaneado
     * Verifica expiración y uso previo
     */
    public function validarCodigoQR(string $qrData): array
    {
        try {
            // Parsear datos del QR (formato: TOKEN|ID_APRENDIZ|FECHA)
            $parts = explode('|', $qrData);
            
            if (count($parts) < 2) {
                return [
                    'success' => false,
                    'message' => 'Formato de código QR inválido'
                ];
            }

            $token = $parts[0];
            $aprendizId = (int)($parts[1] ?? 0);

            // Buscar código QR por token
            $codigoQR = $this->codigoQRRepository->findByToken($token);
            
            if (!$codigoQR) {
                return [
                    'success' => false,
                    'message' => 'Código QR no encontrado'
                ];
            }

            // Verificar si ya fue usado
            if ($codigoQR['usado']) {
                return [
                    'success' => false,
                    'message' => 'Este código QR ya fue utilizado'
                ];
            }

            // Verificar expiración
            $fechaExpiracion = strtotime($codigoQR['fecha_expiracion']);
            $ahora = time();

            if ($ahora > $fechaExpiracion) {
                return [
                    'success' => false,
                    'message' => 'El código QR ha expirado. Por favor genera uno nuevo.'
                ];
            }

            // Verificar que el ID del aprendiz coincide
            if ($codigoQR['id_aprendiz'] != $aprendizId) {
                return [
                    'success' => false,
                    'message' => 'Código QR inválido'
                ];
            }

            // Obtener información del aprendiz
            $aprendiz = $this->aprendizRepository->findById($aprendizId);
            if (!$aprendiz) {
                return [
                    'success' => false,
                    'message' => 'Aprendiz no encontrado'
                ];
            }

            // Marcar como usado
            $this->codigoQRRepository->markAsUsed($token);

            return [
                'success' => true,
                'message' => 'Código QR válido',
                'data' => [
                    'aprendiz_id' => $aprendizId,
                    'aprendiz' => $aprendiz,
                    'fecha_generacion' => $codigoQR['fecha_generacion'],
                    'fecha_expiracion' => $codigoQR['fecha_expiracion'],
                    'tiempo_restante' => $fechaExpiracion - $ahora,
                    'tiempo_transcurrido' => $ahora - strtotime($codigoQR['fecha_generacion'])
                ]
            ];
        } catch (Exception $e) {
            error_log("Error validating QR code: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al validar el código QR'
            ];
        }
    }

    /**
     * Genera un token único para el código QR
     */
    private function generarTokenUnico(): string
    {
        // Generar token usando hash seguro
        $randomBytes = random_bytes(32);
        return bin2hex($randomBytes);
    }

    /**
     * Genera una imagen del código QR en formato base64
     */
    private function generarImagenQR(string $qrData): ?string
    {
        try {
            // Verificar si GD está habilitado
            if (!extension_loaded('gd')) {
                error_log("GD extension is not loaded. QR image generation will fail.");
                // Intentar usar SVG como alternativa si GD no está disponible
                return $this->generarQRComoSVG($qrData);
            }

            $builder = new Builder(
                writer: new PngWriter(),
                data: $qrData,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();

            // Obtener el string de la imagen
            $imageString = $result->getString();
            
            // Verificar que la imagen se generó correctamente
            if (empty($imageString)) {
                error_log("QR image string is empty");
                return null;
            }
            
            // Convertir a base64
            $base64 = base64_encode($imageString);
            
            // Verificar que el base64 es válido
            if (empty($base64) || strlen($base64) < 100) {
                error_log("QR base64 encoding failed or too short: " . strlen($base64) . " chars");
                return null;
            }
            
            error_log("QR image generated successfully, base64 length: " . strlen($base64));
            return $base64;
        } catch (Exception $e) {
            error_log("Error generating QR image: " . $e->getMessage());
            // Intentar usar SVG como alternativa
            return $this->generarQRComoSVG($qrData);
        }
    }

    /**
     * Genera QR como SVG (no requiere GD)
     */
    private function generarQRComoSVG(string $qrData): ?string
    {
        try {
            $builder = new Builder(
                writer: new SvgWriter(),
                data: $qrData,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            );
            
            $result = $builder->build();

            // Convertir SVG a base64
            $svgContent = $result->getString();
            return base64_encode($svgContent);
        } catch (Exception $e) {
            error_log("Error generating QR SVG: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpia códigos QR expirados de la base de datos
     */
    public function limpiarCodigosExpirados(): int
    {
        return $this->codigoQRRepository->deleteExpired();
    }

    /**
     * Obtiene el tiempo restante de un código QR en segundos
     */
    public function obtenerTiempoRestante(string $token): ?int
    {
        $codigoQR = $this->codigoQRRepository->findByToken($token);
        
        if (!$codigoQR || $codigoQR['usado']) {
            return null;
        }

        $fechaExpiracion = strtotime($codigoQR['fecha_expiracion']);
        $ahora = time();
        $tiempoRestante = $fechaExpiracion - $ahora;

        return $tiempoRestante > 0 ? $tiempoRestante : 0;
    }
}

