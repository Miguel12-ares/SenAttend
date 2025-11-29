<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Database/Connection.php';

use App\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Verificando roles en la base de datos...\n\n";
    
    // Contar usuarios por rol
    $stmt = $db->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Distribución de roles:\n";
    foreach ($roles as $row) {
        echo "- {$row['rol']}: {$row['total']}\n";
    }
    
    // Verificar si queda algún coordinador
    $stmt = $db->query("SELECT id, nombre, email, rol FROM usuarios WHERE rol = 'coordinador'");
    $coordinadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($coordinadores) > 0) {
        echo "\n⚠️ ADVERTENCIA: Aún existen usuarios con rol 'coordinador':\n";
        foreach ($coordinadores as $c) {
            echo "- ID: {$c['id']}, Nombre: {$c['nombre']}, Rol: {$c['rol']}\n";
        }
        echo "\nSe recomienda ejecutar la migración SQL para actualizarlos a 'administrativo'.\n";
    } else {
        echo "\n✅ No se encontraron usuarios con rol 'coordinador'.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
