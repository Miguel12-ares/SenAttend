<?php

namespace App\GestionEquipos\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

class EquipoRepository
{
    public function findBySerial(string $serial): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM equipos WHERE numero_serial = :serial LIMIT 1'
            );
            $stmt->execute(['serial' => $serial]);
            $equipo = $stmt->fetch();
            return $equipo ?: null;
        } catch (PDOException $e) {
            error_log("Error finding equipo by serial: " . $e->getMessage());
            return null;
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT * FROM equipos WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $equipo = $stmt->fetch();
            return $equipo ?: null;
        } catch (PDOException $e) {
            error_log("Error finding equipo by ID: " . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $stmt = Connection::prepare(
                'INSERT INTO equipos (numero_serial, marca, imagen, activo) 
                 VALUES (:numero_serial, :marca, :imagen, :activo)'
            );

            $stmt->execute([
                'numero_serial' => $data['numero_serial'],
                'marca' => $data['marca'],
                'imagen' => $data['imagen'] ?? null,
                'activo' => $data['activo'] ?? true,
            ]);

            return (int) Connection::lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating equipo: " . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];

            if (isset($data['numero_serial'])) {
                $fields[] = 'numero_serial = :numero_serial';
                $params['numero_serial'] = $data['numero_serial'];
            }
            if (isset($data['marca'])) {
                $fields[] = 'marca = :marca';
                $params['marca'] = $data['marca'];
            }
            if (array_key_exists('imagen', $data)) {
                $fields[] = 'imagen = :imagen';
                $params['imagen'] = $data['imagen'];
            }
            if (isset($data['activo'])) {
                $fields[] = 'activo = :activo';
                $params['activo'] = (bool)$data['activo'];
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE equipos SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = Connection::prepare($sql);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating equipo: " . $e->getMessage());
            return false;
        }
    }
}


