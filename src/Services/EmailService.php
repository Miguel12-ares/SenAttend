<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Servicio para envío de correos electrónicos
 * Utiliza PHPMailer para el envío de correos
 */
class EmailService
{
    private PHPMailer $mailer;
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    /**
     * Carga la configuración de correo desde variables de entorno
     */
    private function loadConfig(): array
    {
        return [
            'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
            'smtp_username' => getenv('SMTP_USERNAME') ?: '',
            'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
            'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'senattend@gmail.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'SENAttend - Sistema de Asistencia SENA',
        ];
    }

    /**
     * Configura el objeto PHPMailer
     */
    private function configureMailer(): void
    {
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_encryption'];
            $this->mailer->Port = $this->config['smtp_port'];
            $this->mailer->CharSet = 'UTF-8';

            // Remitente
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);

            // Configuración adicional
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Error configuring mailer: " . $e->getMessage());
        }
    }

    /**
     * Envía un código QR por correo electrónico
     */
    public function enviarCodigoQR(string $email, string $nombreAprendiz, string $qrData, string $qrImageBase64 = null): array
    {
        try {
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico no es válido'
                ];
            }

            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Configurar destinatario
            $this->mailer->addAddress($email, $nombreAprendiz);

            // Asunto
            $this->mailer->Subject = 'Tu código QR de asistencia - SENAttend';

            // Si hay imagen, agregarla como adjunto embebido (más compatible con clientes de correo)
            $cid = null;
            if ($qrImageBase64 && !empty(trim($qrImageBase64))) {
                try {
                    // Decodificar la imagen
                    $imageData = base64_decode($qrImageBase64, true);
                    if ($imageData !== false && !empty($imageData)) {
                        // Crear un CID único para la imagen
                        $cid = 'qr_code_' . uniqid();
                        
                        // Crear archivo temporal para la imagen
                        $tempFile = sys_get_temp_dir() . '/' . $cid . '.png';
                        file_put_contents($tempFile, $imageData);
                        
                        // Agregar como imagen embebida
                        $this->mailer->addEmbeddedImage($tempFile, $cid, 'qr_code.png', 'base64', 'image/png');
                        
                        error_log("QR image attached as embedded image with CID: " . $cid . ", file size: " . strlen($imageData));
                        
                        // Limpiar archivo temporal después de enviar (se hará en el finally)
                        register_shutdown_function(function() use ($tempFile) {
                            if (file_exists($tempFile)) {
                                @unlink($tempFile);
                            }
                        });
                    } else {
                        error_log("Failed to decode QR image base64");
                    }
                } catch (Exception $e) {
                    error_log("Error attaching QR image: " . $e->getMessage());
                }
            }

            // Cuerpo del mensaje
            $htmlBody = $this->generateQREmailBody($nombreAprendiz, $qrData, $qrImageBase64, $cid);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $this->generateQREmailPlainText($nombreAprendiz, $qrData);

            // Enviar
            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Código QR enviado exitosamente a ' . $email
            ];
        } catch (Exception $e) {
            error_log("Error sending QR email: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $this->mailer->ErrorInfo
            ];
        }
    }

    /**
     * Genera el cuerpo HTML del correo con el código QR
     */
    private function generateQREmailBody(string $nombreAprendiz, string $qrData, ?string $qrImageBase64, ?string $cid = null): string
    {
        $qrImageTag = '';
        if ($cid) {
            // Usar imagen embebida (más compatible con clientes de correo)
            $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <img src="cid:' . htmlspecialchars($cid) . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                    </td>
                </tr>
            </table>';
        } elseif ($qrImageBase64 && !empty(trim($qrImageBase64))) {
            // Fallback: usar data URI si no hay CID
            $qrImageBase64 = trim($qrImageBase64);
            
            // Decodificar para verificar si es SVG
            $decoded = @base64_decode($qrImageBase64, true);
            if ($decoded !== false && strpos($decoded, '<svg') !== false) {
                // Es SVG
                $svgEncoded = base64_encode($decoded);
                $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                    <tr>
                        <td align="center" style="padding: 0;">
                            <img src="data:image/svg+xml;base64,' . $svgEncoded . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                        </td>
                    </tr>
                </table>';
            } else {
                // Es PNG
                if (strlen($qrImageBase64) > 100) {
                    $qrImageTag = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 auto;">
                        <tr>
                            <td align="center" style="padding: 0;">
                                <img src="data:image/png;base64,' . $qrImageBase64 . '" alt="Código QR" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 8px; display: block; box-sizing: border-box;">
                            </td>
                        </tr>
                    </table>';
                } else {
                    error_log("QR base64 too short, might be invalid");
                    $qrImageTag = '<div style="background: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border: 2px dashed #ccc;">
                        <p style="margin: 0; font-family: monospace; font-size: 14px; word-break: break-all;">' . htmlspecialchars($qrData) . '</p>
                        <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Escanea este código con una app de QR</p>
                    </div>';
                }
            }
        } else {
            // Si no hay imagen, mostrar el código QR como texto
            $qrImageTag = '<div style="background: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border: 2px dashed #ccc;">
                <p style="margin: 0; font-family: monospace; font-size: 14px; word-break: break-all;">' . htmlspecialchars($qrData) . '</p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Escanea este código con una app de QR o genera el QR desde el sistema</p>
            </div>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                /* Reset para clientes de correo */
                body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
                table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
                img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
                
                body { 
                    margin: 0 !important; 
                    padding: 0 !important; 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background-color: #f4f4f4;
                    width: 100% !important;
                }
                
                .wrapper {
                    width: 100% !important;
                    table-layout: fixed;
                    background-color: #f4f4f4;
                    padding: 0;
                    margin: 0;
                }
                
                .container { 
                    max-width: 600px; 
                    width: 100% !important;
                    margin: 0 auto; 
                    padding: 0;
                    background-color: #ffffff;
                }
                
                .header { 
                    background-color: #39A900; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .header h1 {
                    margin: 0;
                    padding: 0;
                    font-size: 24px;
                }
                
                .header p {
                    margin: 10px 0 0 0;
                    padding: 0;
                    font-size: 14px;
                }
                
                .content { 
                    padding: 20px; 
                    background-color: #f9f9f9; 
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .content h2 {
                    margin: 0 0 15px 0;
                    padding: 0;
                    font-size: 20px;
                }
                
                .content p {
                    margin: 0 0 15px 0;
                    padding: 0;
                    font-size: 14px;
                }
                
                .qr-container { 
                    text-align: center; 
                    margin: 15px auto; 
                    width: 100%;
                    max-width: 300px;
                    box-sizing: border-box;
                }
                
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    font-size: 12px; 
                    color: #666;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .footer p {
                    margin: 5px 0;
                    padding: 0;
                }
                
                .warning { 
                    background-color: #fff3cd; 
                    border-left: 4px solid #ffc107; 
                    padding: 15px; 
                    margin: 20px 0;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .warning ul {
                    margin: 10px 0 0 20px;
                    padding: 0;
                }
                
                .warning li {
                    margin: 5px 0;
                    padding: 0;
                }
                
                /* Media queries para móviles */
                @media only screen and (max-width: 600px) {
                    .container {
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    
                    .content {
                        padding: 15px !important;
                    }
                    
                    .header {
                        padding: 15px !important;
                    }
                    
                    .header h1 {
                        font-size: 20px !important;
                    }
                    
                    .qr-container {
                        max-width: 100% !important;
                        margin: 15px 0 !important;
                    }
                    
                    .footer {
                        padding: 15px !important;
                    }
                }
            </style>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; width: 100%;">
            <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0; padding: 0; background-color: #f4f4f4;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff;">
                            <tr>
                                <td class="header" style="background-color: #39A900; color: white; padding: 20px; text-align: center; width: 100%; box-sizing: border-box;">
                                    <h1 style="margin: 0; padding: 0; font-size: 24px;">SENAttend</h1>
                                    <p style="margin: 10px 0 0 0; padding: 0; font-size: 14px;">Sistema de Asistencia SENA</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="content" style="padding: 20px; background-color: #f9f9f9; width: 100%; box-sizing: border-box;">
                                    <h2 style="margin: 0 0 15px 0; padding: 0; font-size: 20px;">Hola ' . htmlspecialchars($nombreAprendiz) . ',</h2>
                                    <p style="margin: 0 0 15px 0; padding: 0; font-size: 14px;">Se ha generado tu código QR de asistencia. Este código tiene una validez de <strong>3 minutos</strong> desde su generación.</p>
                                    
                                    <div class="qr-container" style="text-align: center; margin: 15px auto; width: 100%; max-width: 300px; box-sizing: border-box;">
                                        ' . $qrImageTag . '
                                    </div>

                                    <div class="warning" style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; width: 100%; box-sizing: border-box;">
                                        <strong>⚠️ Importante:</strong>
                                        <ul style="margin: 10px 0 0 20px; padding: 0;">
                                            <li style="margin: 5px 0; padding: 0;">Este código QR expira en <strong>3 minutos</strong></li>
                                            <li style="margin: 5px 0; padding: 0;">Una vez escaneado, el código se invalidará</li>
                                            <li style="margin: 5px 0; padding: 0;">No compartas este código con otras personas</li>
                                        </ul>
                                    </div>

                                    <p style="margin: 0 0 15px 0; padding: 0; font-size: 14px;">Presenta este código QR a tu instructor para registrar tu asistencia.</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="footer" style="text-align: center; padding: 20px; font-size: 12px; color: #666; width: 100%; box-sizing: border-box;">
                                    <p style="margin: 5px 0; padding: 0;">Este es un correo automático, por favor no respondas.</p>
                                    <p style="margin: 5px 0; padding: 0;">&copy; ' . date('Y') . ' SENA - Servicio Nacional de Aprendizaje</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Genera la versión en texto plano del correo
     */
    private function generateQREmailPlainText(string $nombreAprendiz, string $qrData): string
    {
        return "Hola {$nombreAprendiz},\n\n" .
               "Se ha generado tu código QR de asistencia.\n\n" .
               "Datos del código: {$qrData}\n\n" .
               "IMPORTANTE: Este código expira en 3 minutos.\n\n" .
               "Presenta este código QR a tu instructor para registrar tu asistencia.\n\n" .
               "Este es un correo automático, por favor no respondas.\n\n" .
               "© " . date('Y') . " SENA - Servicio Nacional de Aprendizaje";
    }

    /**
     * Envía un correo de prueba
     */
    public function enviarCorreoPrueba(string $email): array
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Correo de prueba - SENAttend';
            $this->mailer->Body = '<h1>Correo de prueba</h1><p>Si recibes este correo, la configuración de email está funcionando correctamente.</p>';
            $this->mailer->AltBody = 'Correo de prueba - Si recibes este correo, la configuración de email está funcionando correctamente.';

            $this->mailer->send();

            return [
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente'
            ];
        } catch (Exception $e) {
            error_log("Error sending test email: " . $this->mailer->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar el correo de prueba: ' . $this->mailer->ErrorInfo
            ];
        }
    }
}

