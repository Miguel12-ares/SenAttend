<?php
/**
 * Script de Verificación del Módulo QR
 * Verifica que todos los archivos y configuraciones estén en su lugar
 */

echo "=== VERIFICACIÓN DEL MÓDULO QR ===\n\n";

$errores = [];
$advertencias = [];
$exitos = [];

// Verificar archivos del controlador
echo "1. Verificando archivos del backend...\n";
$archivoControlador = __DIR__ . '/src/Controllers/QRController.php';
if (file_exists($archivoControlador)) {
    $exitos[] = "✓ QRController.php existe";
    // Verificar contenido
    $contenido = file_get_contents($archivoControlador);
    if (strpos($contenido, 'class QRController') !== false) {
        $exitos[] = "✓ QRController está correctamente definido";
    } else {
        $errores[] = "✗ QRController no tiene la clase correcta";
    }
} else {
    $errores[] = "✗ QRController.php no existe";
}

// Verificar vistas
echo "\n2. Verificando vistas...\n";
$vistaGenerar = __DIR__ . '/views/qr/generar.php';
$vistaEscanear = __DIR__ . '/views/qr/escanear.php';

if (file_exists($vistaGenerar)) {
    $exitos[] = "✓ Vista generar.php existe";
} else {
    $errores[] = "✗ Vista generar.php no existe";
}

if (file_exists($vistaEscanear)) {
    $exitos[] = "✓ Vista escanear.php existe";
} else {
    $errores[] = "✗ Vista escanear.php no existe";
}

// Verificar CSS
echo "\n3. Verificando estilos...\n";
$archivoCSS = __DIR__ . '/public/css/qr.css';
if (file_exists($archivoCSS)) {
    $exitos[] = "✓ qr.css existe";
    $tamano = filesize($archivoCSS);
    if ($tamano > 1000) {
        $exitos[] = "✓ qr.css tiene contenido (" . round($tamano/1024, 2) . " KB)";
    } else {
        $advertencias[] = "⚠ qr.css parece estar vacío o incompleto";
    }
} else {
    $errores[] = "✗ qr.css no existe";
}

// Verificar rutas en el router
echo "\n4. Verificando rutas en el router...\n";
$archivoRouter = __DIR__ . '/public/index.php';
if (file_exists($archivoRouter)) {
    $contenidoRouter = file_get_contents($archivoRouter);
    
    if (strpos($contenidoRouter, "'/qr/generar'") !== false) {
        $exitos[] = "✓ Ruta /qr/generar registrada";
    } else {
        $errores[] = "✗ Ruta /qr/generar NO registrada";
    }
    
    if (strpos($contenidoRouter, "'/qr/escanear'") !== false) {
        $exitos[] = "✓ Ruta /qr/escanear registrada";
    } else {
        $errores[] = "✗ Ruta /qr/escanear NO registrada";
    }
    
    if (strpos($contenidoRouter, "'/api/qr/buscar'") !== false) {
        $exitos[] = "✓ API /api/qr/buscar registrada";
    } else {
        $errores[] = "✗ API /api/qr/buscar NO registrada";
    }
    
    if (strpos($contenidoRouter, "'/api/qr/procesar'") !== false) {
        $exitos[] = "✓ API /api/qr/procesar registrada";
    } else {
        $errores[] = "✗ API /api/qr/procesar NO registrada";
    }
    
    if (strpos($contenidoRouter, 'QRController::class') !== false) {
        $exitos[] = "✓ QRController importado en router";
    } else {
        $errores[] = "✗ QRController NO importado en router";
    }
} else {
    $errores[] = "✗ Router (index.php) no existe";
}

// Verificar dashboard
echo "\n5. Verificando dashboard...\n";
$archivoDashboard = __DIR__ . '/views/dashboard/index.php';
if (file_exists($archivoDashboard)) {
    $contenidoDashboard = file_get_contents($archivoDashboard);
    
    if (strpos($contenidoDashboard, '/qr/generar') !== false) {
        $exitos[] = "✓ Enlace a Generar QR en dashboard";
    } else {
        $advertencias[] = "⚠ Enlace a Generar QR NO está en dashboard";
    }
    
    if (strpos($contenidoDashboard, '/qr/escanear') !== false) {
        $exitos[] = "✓ Enlace a Escanear QR en dashboard";
    } else {
        $advertencias[] = "⚠ Enlace a Escanear QR NO está en dashboard";
    }
} else {
    $advertencias[] = "⚠ Dashboard no encontrado (no crítico)";
}

// Verificar documentación
echo "\n6. Verificando documentación...\n";
$archivosDocs = [
    'MODULO_QR.md',
    'QR_GUIA_RAPIDA.md',
    'RESUMEN_MODULO_QR.md'
];

foreach ($archivosDocs as $doc) {
    $rutaDoc = __DIR__ . '/docs/' . $doc;
    if (file_exists($rutaDoc)) {
        $exitos[] = "✓ Documentación {$doc} existe";
    } else {
        $advertencias[] = "⚠ Documentación {$doc} no existe";
    }
}

// Verificar dependencias PHP
echo "\n7. Verificando clases necesarias...\n";
if (class_exists('App\Services\AsistenciaService')) {
    $exitos[] = "✓ AsistenciaService disponible";
} else {
    $errores[] = "✗ AsistenciaService NO disponible";
}

if (class_exists('App\Services\AuthService')) {
    $exitos[] = "✓ AuthService disponible";
} else {
    $errores[] = "✗ AuthService NO disponible";
}

if (class_exists('App\Repositories\AprendizRepository')) {
    $exitos[] = "✓ AprendizRepository disponible";
} else {
    $errores[] = "✗ AprendizRepository NO disponible";
}

if (class_exists('App\Repositories\FichaRepository')) {
    $exitos[] = "✓ FichaRepository disponible";
} else {
    $errores[] = "✗ FichaRepository NO disponible";
}

// Resumen
echo "\n\n=== RESUMEN DE VERIFICACIÓN ===\n\n";

echo "ÉXITOS (" . count($exitos) . "):\n";
foreach ($exitos as $exito) {
    echo "  $exito\n";
}

if (!empty($advertencias)) {
    echo "\nADVERTENCIAS (" . count($advertencias) . "):\n";
    foreach ($advertencias as $advertencia) {
        echo "  $advertencia\n";
    }
}

if (!empty($errores)) {
    echo "\nERRORES CRÍTICOS (" . count($errores) . "):\n";
    foreach ($errores as $error) {
        echo "  $error\n";
    }
    echo "\n⚠️  HAY ERRORES QUE DEBEN CORREGIRSE\n\n";
} else {
    echo "\n✅ TODOS LOS COMPONENTES ESTÁN EN SU LUGAR\n";
    echo "✅ EL MÓDULO QR ESTÁ LISTO PARA USAR\n\n";
}

// Instrucciones finales
echo "\n=== PRÓXIMOS PASOS ===\n\n";
echo "1. Asegúrate de que XAMPP esté corriendo\n";
echo "2. Accede a http://localhost/qr/generar para probar\n";
echo "3. Accede a http://localhost/qr/escanear para probar (como instructor)\n";
echo "4. Revisa la documentación en /docs/MODULO_QR.md\n";
echo "5. Lee la guía rápida en /docs/QR_GUIA_RAPIDA.md\n\n";

echo "=== FIN DE VERIFICACIÓN ===\n";

// Retornar código de salida
exit(empty($errores) ? 0 : 1);

