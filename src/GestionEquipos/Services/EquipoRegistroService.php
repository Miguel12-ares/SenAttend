<?php

namespace App\GestionEquipos\Services;

use App\Database\Connection;
use App\GestionEquipos\Repositories\EquipoRepository;
use App\GestionEquipos\Repositories\AprendizEquipoRepository;
use App\GestionEquipos\Repositories\QrEquipoRepository;

class EquipoRegistroService
{
    private EquipoRepository $equipoRepository;
    private AprendizEquipoRepository $aprendizEquipoRepository;
    private QrEquipoRepository $qrEquipoRepository;
    private QREncryptionService $encryptionService;

    public function __construct(
        EquipoRepository $equipoRepository,
        AprendizEquipoRepository $aprendizEquipoRepository,
        QrEquipoRepository $qrEquipoRepository,
        ?QREncryptionService $encryptionService = null
    ) {
        $this->equipoRepository = $equipoRepository;
        $this->aprendizEquipoRepository = $aprendizEquipoRepository;
        $this->qrEquipoRepository = $qrEquipoRepository;
        $this->encryptionService = $encryptionService ?? new QREncryptionService();
    }

    public function validarDatos(array $data): array
    {
        $errors = [];

        $serial = trim($data['numero_serial'] ?? '');
        $marca = trim($data['marca'] ?? '');

        if ($serial === '') {
            $errors[] = 'El número de serie es obligatorio';
        } elseif (strlen($serial) < 3) {
            $errors[] = 'El número de serie debe tener al menos 3 caracteres';
        }

        if ($marca === '') {
            $errors[] = 'La marca es obligatoria';
        } elseif (strlen($marca) < 2) {
            $errors[] = 'La marca debe tener al menos 2 caracteres';
        }

        // Validar unicidad de serial
        if ($serial !== '' && $this->equipoRepository->findBySerial($serial)) {
            $errors[] = "Ya existe un equipo con el número de serie {$serial}";
        }

        return $errors;
    }

    /**
     * Registra un equipo para un aprendiz:
     * - Crea equipo
     * - Crea relación aprendiz_equipo
     * - Crea registro básico en qr_equipos (token + payload JSON)
     */
    public function registrarEquipoParaAprendiz(int $aprendizId, array $data): array
    {
        $errors = $this->validarDatos($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            Connection::beginTransaction();

            $equipoId = $this->equipoRepository->create([
                'numero_serial' => trim($data['numero_serial']),
                'marca' => trim($data['marca']),
                'imagen' => $data['imagen'] ?? null,
                'activo' => true,
            ]);

            $this->aprendizEquipoRepository->createRelacion($aprendizId, $equipoId, 'activo');

            // Crear QR básico (token + payload cifrado, la generación de imagen se hará en otro servicio)
            $token = bin2hex(random_bytes(16));
            $payload = [
                'equipo_id' => $equipoId,
                'aprendiz_id' => $aprendizId,
                'numero_serial' => trim($data['numero_serial']),
                'marca' => trim($data['marca']),
            ];

            // Cifrar los datos del payload antes de guardarlos
            $encryptedData = $this->encryptionService->encrypt($payload);

            $this->qrEquipoRepository->create([
                'id_equipo' => $equipoId,
                'id_aprendiz' => $aprendizId,
                'token' => $token,
                'qr_data' => $encryptedData, // Datos cifrados
                // Por ahora sin expiración fija (se puede ajustar luego)
                'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'activo' => true,
            ]);

            Connection::commit();

            return [
                'success' => true,
                'message' => 'Equipo registrado correctamente',
                'data' => [
                    'equipo_id' => $equipoId,
                    'token' => $token,
                ],
            ];
        } catch (\Throwable $e) {
            Connection::rollBack();
            error_log('EquipoRegistroService::registrarEquipoParaAprendiz error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors' => ['Ocurrió un error al registrar el equipo'],
            ];
        }
    }
}


