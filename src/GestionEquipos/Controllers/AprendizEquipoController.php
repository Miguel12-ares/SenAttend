<?php

namespace App\GestionEquipos\Controllers;

use App\GestionEquipos\Services\EquipoRegistroService;
use App\GestionEquipos\Services\EquipoQRService;
use App\Services\AprendizAuthService;
use App\Session\SessionManager;
use App\Support\Response;

class AprendizEquipoController
{
    private AprendizAuthService $aprendizAuthService;
    private EquipoRegistroService $equipoRegistroService;
    private EquipoQRService $equipoQRService;
    private SessionManager $session;

    public function __construct(
        AprendizAuthService $aprendizAuthService,
        EquipoRegistroService $equipoRegistroService,
        EquipoQRService $equipoQRService,
        SessionManager $session
    ) {
        $this->aprendizAuthService = $aprendizAuthService;
        $this->equipoRegistroService = $equipoRegistroService;
        $this->equipoQRService = $equipoQRService;
        $this->session = $session;
    }

    /**
     * Formulario para registrar un nuevo equipo
     * GET /aprendiz/equipos/crear
     */
    public function create(): void
    {
        $aprendiz = $this->aprendizAuthService->getCurrentAprendiz();

        if (!$aprendiz) {
            Response::redirect('/aprendiz/login');
        }

        $this->session->start();
        $error = $this->session->getFlash('aprendiz_error');
        $message = $this->session->getFlash('aprendiz_message');
        $old = $this->session->get('aprendiz_old', []);
        $this->session->remove('aprendiz_old');

        require __DIR__ . '/../../../views/aprendiz/equipos/create.php';
    }

    /**
     * Procesa el registro de un nuevo equipo
     * POST /aprendiz/equipos
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('/aprendiz/panel');
        }

        $aprendiz = $this->aprendizAuthService->getCurrentAprendiz();
        if (!$aprendiz) {
            Response::redirect('/aprendiz/login');
        }

        $numeroSerial = trim(filter_input(INPUT_POST, 'numero_serial', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $marca = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        // Manejo de imagen (opcional)
        $imagenPath = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/equipos/';

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $tmpName = $_FILES['imagen']['tmp_name'];
            $originalName = $_FILES['imagen']['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($extension, $allowed, true)) {
                $safeName = 'equipo_' . $aprendiz['id'] . '_' . time() . '.' . $extension;
                $destPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $imagenPath = 'uploads/equipos/' . $safeName;
                }
            }
        }

        $data = [
            'numero_serial' => $numeroSerial,
            'marca' => $marca,
            'imagen' => $imagenPath,
        ];

        $result = $this->equipoRegistroService->registrarEquipoParaAprendiz((int)$aprendiz['id'], $data);

        $this->session->start();

        if ($result['success']) {
            $this->session->flash('aprendiz_message', $result['message'] ?? 'Equipo registrado correctamente');
            Response::redirect('/aprendiz/panel');
        } else {
            $this->session->flash('aprendiz_error', implode('<br>', $result['errors'] ?? []));
            $this->session->set('aprendiz_old', $data);
            Response::redirect('/aprendiz/equipos/crear');
        }
    }

    /**
     * Muestra el QR del equipo para el aprendiz actual
     * GET /aprendiz/equipos/{id}/qr
     */
    public function showQR(int $equipoId): void
    {
        $aprendiz = $this->aprendizAuthService->getCurrentAprendiz();
        if (!$aprendiz) {
            Response::redirect('/aprendiz/login');
        }

        $result = $this->equipoQRService->obtenerQRBase64ParaEquipo($equipoId, (int)$aprendiz['id']);

        if (!$result['success']) {
            $this->session->start();
            $this->session->flash('aprendiz_error', $result['message'] ?? 'No fue posible obtener el QR.');
            Response::redirect('/aprendiz/panel');
        }

        $qrInfo = $result['data'];

        require __DIR__ . '/../../../views/aprendiz/equipos/qr.php';
    }
}


