<?php

namespace App\GestionEquipos\Services;

use App\GestionEquipos\Repositories\QrEquipoRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;

class EquipoQRService
{
    private QrEquipoRepository $qrEquipoRepository;

    public function __construct(QrEquipoRepository $qrEquipoRepository)
    {
        $this->qrEquipoRepository = $qrEquipoRepository;
    }

    /**
     * Obtiene el registro de QR activo para un equipo+aprendiz y genera la imagen base64.
     */
    public function obtenerQRBase64ParaEquipo(int $equipoId, int $aprendizId): array
    {
        $qr = $this->qrEquipoRepository->findActiveByEquipoAndAprendiz($equipoId, $aprendizId);

        if (!$qr) {
            return [
                'success' => false,
                'message' => 'No se encontró un QR activo para este equipo.',
            ];
        }

        $base64 = $this->generarImagenQR($qr['qr_data']);

        if (!$base64) {
            return [
                'success' => false,
                'message' => 'No fue posible generar la imagen del QR.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'token' => $qr['token'],
                'qr_data' => $qr['qr_data'],
                'fecha_generacion' => $qr['fecha_generacion'],
                'fecha_expiracion' => $qr['fecha_expiracion'],
                'image_base64' => $base64,
            ],
        ];
    }

    private function generarImagenQR(string $qrData): ?string
    {
        try {
            // Intentar PNG (requiere GD)
            if (extension_loaded('gd')) {
                $builder = new Builder(
                    writer: new PngWriter(),
                    data: $qrData,
                    encoding: new Encoding('UTF-8'),
                    errorCorrectionLevel: ErrorCorrectionLevel::High,
                    size: 300,
                    margin: 10
                );

                $result = $builder->build();
                $imageString = $result->getString();

                if (empty($imageString)) {
                    return null;
                }

                return 'data:image/png;base64,' . base64_encode($imageString);
            }

            // Fallback a SVG si no hay GD
            $builder = new Builder(
                writer: new SvgWriter(),
                data: $qrData,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            );

            $result = $builder->build();
            $svgContent = $result->getString();
            return 'data:image/svg+xml;base64,' . base64_encode($svgContent);
        } catch (Exception $e) {
            error_log('EquipoQRService::generarImagenQR error: ' . $e->getMessage());
            return null;
        }
    }
}


