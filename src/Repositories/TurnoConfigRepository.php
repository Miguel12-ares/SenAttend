<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * Repository para gestión de configuración de turnos
 * Maneja operaciones CRUD sobre la tabla configuracion_turnos
 * 
 * @author Sistema de Configuración Dinámica
 * @version 1.0
 */
class TurnoConfigRepository
{
    /**
     * Obtiene todos los turnos activos ordenados por hora de inicio
     * 
     * @return array Lista de turnos con su configuración
     */
    public function findAllActive(): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    id,
                    nombre_turno,
                    hora_inicio,
                    hora_fin,
                    hora_limite_llegada,
                    activo,
                    created_at,
                    updated_at
                FROM configuracion_turnos
                WHERE activo = TRUE
                ORDER BY hora_inicio ASC'
            );
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::findAllActive: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los turnos (activos e inactivos)
     * 
     * @return array Lista completa de turnos
     */
    public function findAll(): array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    id,
                    nombre_turno,
                    hora_inicio,
                    hora_fin,
                    hora_limite_llegada,
                    activo,
                    created_at,
                    updated_at
                FROM configuracion_turnos
                ORDER BY hora_inicio ASC'
            );
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene configuración de un turno específico por nombre
     * 
     * @param string $nombreTurno Nombre del turno (Mañana, Tarde, Noche)
     * @return array|null Configuración del turno o null si no existe
     */
    public function findByNombre(string $nombreTurno): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    id,
                    nombre_turno,
                    hora_inicio,
                    hora_fin,
                    hora_limite_llegada,
                    activo,
                    created_at,
                    updated_at
                FROM configuracion_turnos
                WHERE nombre_turno = :nombre_turno
                LIMIT 1'
            );
            
            $stmt->execute(['nombre_turno' => $nombreTurno]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::findByNombre: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene el turno correspondiente a una hora específica
     * 
     * @param string $hora Hora en formato H:i:s
     * @return array|null Configuración del turno o null si no hay turno activo
     */
    public function findByHora(string $hora): ?array
    {
        try {
            $stmt = Connection::prepare(
                'SELECT 
                    id,
                    nombre_turno,
                    hora_inicio,
                    hora_fin,
                    hora_limite_llegada,
                    activo,
                    created_at,
                    updated_at
                FROM configuracion_turnos
                WHERE activo = TRUE
                  AND :hora >= hora_inicio
                  AND :hora < hora_fin
                ORDER BY hora_inicio ASC
                LIMIT 1'
            );
            
            $stmt->execute(['hora' => $hora]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::findByHora: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza la configuración de un turno
     * 
     * @param string $nombreTurno Nombre del turno a actualizar
     * @param array $data Datos a actualizar (hora_inicio, hora_fin, hora_limite_llegada)
     * @return bool True si se actualizó correctamente
     */
    public function update(string $nombreTurno, array $data): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE configuracion_turnos
                SET 
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    hora_limite_llegada = :hora_limite_llegada,
                    updated_at = CURRENT_TIMESTAMP
                WHERE nombre_turno = :nombre_turno'
            );
            
            return $stmt->execute([
                'nombre_turno' => $nombreTurno,
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'hora_limite_llegada' => $data['hora_limite_llegada']
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza múltiples turnos en una transacción
     * 
     * @param array $turnos Array de turnos con su configuración
     * @return bool True si todos se actualizaron correctamente
     */
    public function updateMultiple(array $turnos): bool
    {
        try {
            Connection::beginTransaction();
            
            $stmt = Connection::prepare(
                'UPDATE configuracion_turnos
                SET 
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    hora_limite_llegada = :hora_limite_llegada,
                    updated_at = CURRENT_TIMESTAMP
                WHERE nombre_turno = :nombre_turno'
            );
            
            foreach ($turnos as $turno) {
                $success = $stmt->execute([
                    'nombre_turno' => $turno['nombre_turno'],
                    'hora_inicio' => $turno['hora_inicio'],
                    'hora_fin' => $turno['hora_fin'],
                    'hora_limite_llegada' => $turno['hora_limite_llegada']
                ]);
                
                if (!$success) {
                    Connection::rollBack();
                    return false;
                }
            }
            
            Connection::commit();
            return true;
            
        } catch (PDOException $e) {
            Connection::rollBack();
            error_log("Error en TurnoConfigRepository::updateMultiple: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activa o desactiva un turno
     * 
     * @param string $nombreTurno Nombre del turno
     * @param bool $activo Estado deseado
     * @return bool True si se actualizó correctamente
     */
    public function setActivo(string $nombreTurno, bool $activo): bool
    {
        try {
            $stmt = Connection::prepare(
                'UPDATE configuracion_turnos
                SET activo = :activo,
                    updated_at = CURRENT_TIMESTAMP
                WHERE nombre_turno = :nombre_turno'
            );
            
            return $stmt->execute([
                'nombre_turno' => $nombreTurno,
                'activo' => $activo ? 1 : 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en TurnoConfigRepository::setActivo: " . $e->getMessage());
            return false;
        }
    }
}
