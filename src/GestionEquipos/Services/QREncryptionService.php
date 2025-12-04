<?php

namespace App\GestionEquipos\Services;

use Exception;

/**
 * Servicio para cifrar y descifrar datos del QR de equipos
 * Usa AES-256-GCM para cifrado seguro
 */
class QREncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'aes-256-gcm';
    private int $tagLength = 16;

    public function __construct(?string $encryptionKey = null)
    {
        // Obtener la clave desde configuración o usar una por defecto (solo para desarrollo)
        $this->encryptionKey = $encryptionKey ?? $this->getDefaultKey();
        
        // Validar que la clave tenga el tamaño correcto (32 bytes para AES-256)
        if (strlen($this->encryptionKey) !== 32) {
            throw new Exception('La clave de cifrado debe tener exactamente 32 bytes (256 bits)');
        }
    }

    /**
     * Obtiene la clave de cifrado desde variables de entorno o genera una por defecto
     */
    private function getDefaultKey(): string
    {
        // Intentar obtener desde variable de entorno
        $key = getenv('QR_ENCRYPTION_KEY');
        
        if ($key && strlen($key) === 32) {
            return $key;
        }

        // Si no existe, usar una clave por defecto (debe cambiarse en producción)
        // En producción, esta clave debe estar en el archivo .env
        $defaultKey = 'SENA2024QRENCRYPTIONKEY32BYTE'; // 32 bytes
        
        error_log('ADVERTENCIA: Usando clave de cifrado por defecto. Debe configurar QR_ENCRYPTION_KEY en .env');
        
        return $defaultKey;
    }

    /**
     * Cifra los datos del QR
     * 
     * @param array $data Datos a cifrar
     * @return string Datos cifrados en formato base64
     * @throws Exception Si el cifrado falla
     */
    public function encrypt(array $data): string
    {
        try {
            // Convertir datos a JSON
            $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            // Generar IV (Initialization Vector) aleatorio
            $ivLength = openssl_cipher_iv_length($this->cipher);
            if ($ivLength === false) {
                throw new Exception('No se pudo obtener la longitud del IV para el cifrado');
            }
            
            $iv = openssl_random_pseudo_bytes($ivLength);
            if ($iv === false) {
                throw new Exception('No se pudo generar el IV para el cifrado');
            }

            // Cifrar los datos
            $tag = '';
            $encrypted = openssl_encrypt(
                $plaintext,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag, // Se pasa por referencia, se llenará con el tag de autenticación
                '', // AAD (Additional Authenticated Data) - vacío
                $this->tagLength
            );

            if ($encrypted === false || empty($tag)) {
                throw new Exception('Error al cifrar los datos');
            }

            // Combinar IV + tag + datos cifrados y codificar en base64
            $combined = $iv . $tag . $encrypted;
            return base64_encode($combined);
        } catch (Exception $e) {
            error_log('QREncryptionService::encrypt error: ' . $e->getMessage());
            throw new Exception('Error al cifrar los datos del QR: ' . $e->getMessage());
        }
    }

    /**
     * Descifra los datos del QR
     * 
     * @param string $encryptedData Datos cifrados en formato base64
     * @return array Datos descifrados
     * @throws Exception Si el descifrado falla
     */
    public function decrypt(string $encryptedData): array
    {
        try {
            // Decodificar desde base64
            $combined = base64_decode($encryptedData, true);
            if ($combined === false) {
                throw new Exception('Datos cifrados inválidos (no es base64 válido)');
            }

            // Obtener longitudes
            $ivLength = openssl_cipher_iv_length($this->cipher);
            if ($ivLength === false) {
                throw new Exception('No se pudo obtener la longitud del IV para el descifrado');
            }

            // Extraer IV, tag y datos cifrados
            $iv = substr($combined, 0, $ivLength);
            $tag = substr($combined, $ivLength, $this->tagLength);
            $encrypted = substr($combined, $ivLength + $this->tagLength);

            if (strlen($iv) !== $ivLength || strlen($tag) !== $this->tagLength) {
                throw new Exception('Datos cifrados corruptos o incompletos');
            }

            // Descifrar los datos
            $plaintext = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new Exception('Error al descifrar los datos. Verifique que la clave de cifrado sea correcta.');
            }

            // Convertir JSON a array
            $data = json_decode($plaintext, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            error_log('QREncryptionService::decrypt error: ' . $e->getMessage());
            throw new Exception('Error al descifrar los datos del QR: ' . $e->getMessage());
        }
    }

    /**
     * Verifica si una cadena está cifrada (intenta descifrarla sin lanzar excepción)
     * 
     * @param string $data Datos a verificar
     * @return bool True si parece estar cifrado, false si no
     */
    public function isEncrypted(string $data): bool
    {
        try {
            // Intentar decodificar base64
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                return false;
            }

            // Verificar que tenga el tamaño mínimo esperado (IV + tag + al menos algunos bytes)
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $minLength = $ivLength + $this->tagLength + 1;
            
            return strlen($decoded) >= $minLength;
        } catch (Exception $e) {
            return false;
        }
    }
}

