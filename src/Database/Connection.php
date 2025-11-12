<?php

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO Singleton con conexión persistente
 */
class Connection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Constructor privado para evitar instanciación directa
     */
    private function __construct()
    {
    }

    /**
     * Evitar clonación del objeto
     */
    private function __clone()
    {
    }

    /**
     * Evitar deserialización
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }

    /**
     * Obtiene la instancia única de la conexión PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }

        return self::$instance;
    }

    /**
     * Establece la conexión a la base de datos
     */
    private static function connect(): void
    {
        try {
            // Cargar configuración si no está cargada
            if (empty(self::$config)) {
                $configFile = __DIR__ . '/../../config/config.php';
                if (!file_exists($configFile)) {
                    throw new RuntimeException("Config file not found");
                }
                $fullConfig = require $configFile;
                self::$config = $fullConfig['database'];
            }

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['name'],
                self::$config['charset']
            );

            self::$instance = new PDO(
                $dsn,
                self::$config['user'],
                self::$config['pass'],
                self::$config['options']
            );

            // Log de conexión exitosa en modo desarrollo
            if (defined('APP_ENV') && APP_ENV === 'local') {
                self::log('Database connection established successfully');
            }
        } catch (PDOException $e) {
            self::log('Connection failed: ' . $e->getMessage(), 'ERROR');
            throw new RuntimeException(
                'Database connection failed. Please check your configuration.',
                0,
                $e
            );
        }
    }

    /**
     * Helper para preparar y ejecutar consultas
     */
    public static function prepare(string $sql): \PDOStatement
    {
        try {
            return self::getInstance()->prepare($sql);
        } catch (PDOException $e) {
            self::log('Prepare failed: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Helper para ejecutar consultas directas
     */
    public static function query(string $sql): \PDOStatement
    {
        try {
            return self::getInstance()->query($sql);
        } catch (PDOException $e) {
            self::log('Query failed: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Obtiene el último ID insertado
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Inicia una transacción
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Revierte una transacción
     */
    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Logging básico para desarrollo
     */
    private static function log(string $message, string $level = 'INFO'): void
    {
        if (defined('APP_ENV') && APP_ENV === 'local') {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

            // En desarrollo, también mostrar en consola de errores
            error_log($logMessage);

            // Guardar en archivo de log si existe el directorio
            $logDir = __DIR__ . '/../../logs';
            if (is_dir($logDir) && is_writable($logDir)) {
                file_put_contents(
                    $logDir . '/database.log',
                    $logMessage,
                    FILE_APPEND
                );
            }
        }
    }

    /**
     * Cierra la conexión (útil para testing)
     */
    public static function disconnect(): void
    {
        self::$instance = null;
    }
}

