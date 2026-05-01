<?php
/**
 * Helper para encriptación de datos sensibles
 *
 * Utiliza OpenSSL para encriptar/desencriptar información sensible
 * como API keys usando salt único de WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Crypto_Helper {

    /**
     * Método de encriptación
     */
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Obtener clave de encriptación basada en salt de WordPress
     */
    private static function get_encryption_key() {
        // Usar salt de WordPress como base para la clave
        // Esto es único por instalación y seguro
        $key = wp_salt('auth');

        // Derivar clave de 256 bits
        return hash('sha256', $key, true);
    }

    /**
     * Encriptar texto
     *
     * @param string $plain_text Texto a encriptar
     * @return string|false Texto encriptado en base64 o false si falla
     */
    public static function encrypt($plain_text) {
        if (empty($plain_text)) {
            return '';
        }

        // Verificar que OpenSSL esté disponible
        if (!function_exists('openssl_encrypt')) {
            // Fallback: guardar sin encriptar pero registrar warning
            error_log('SAG Warning: OpenSSL no disponible, guardando sin encriptar');
            return $plain_text;
        }

        $key = self::get_encryption_key();

        // Generar IV aleatorio
        $iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        // Encriptar
        $encrypted = openssl_encrypt(
            $plain_text,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            error_log('SAG Error: Fallo al encriptar datos');
            return false;
        }

        // Combinar IV + datos encriptados y codificar en base64
        // El IV no es secreto, solo debe ser único
        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencriptar texto
     *
     * @param string $encrypted_text Texto encriptado en base64
     * @return string|false Texto desencriptado o false si falla
     */
    public static function decrypt($encrypted_text) {
        if (empty($encrypted_text)) {
            return '';
        }

        // Verificar que OpenSSL esté disponible
        if (!function_exists('openssl_decrypt')) {
            // Si no hay OpenSSL, asumir que está sin encriptar
            return $encrypted_text;
        }

        $key = self::get_encryption_key();

        // Decodificar desde base64
        $data = base64_decode($encrypted_text, true);

        if ($data === false) {
            // Podría ser dato sin encriptar (legacy)
            return $encrypted_text;
        }

        // Extraer IV
        $iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);

        if (strlen($data) < $iv_length) {
            // Dato corrupto o sin encriptar
            return $encrypted_text;
        }

        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        // Desencriptar
        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            error_log('SAG Error: Fallo al desencriptar datos');
            // Devolver dato original por si es legacy sin encriptar
            return $encrypted_text;
        }

        return $decrypted;
    }

    /**
     * Verificar si OpenSSL está disponible
     *
     * @return bool
     */
    public static function is_encryption_available() {
        return function_exists('openssl_encrypt') &&
               function_exists('openssl_decrypt') &&
               function_exists('openssl_random_pseudo_bytes');
    }

    /**
     * Obtener información de encriptación
     *
     * @return array
     */
    public static function get_encryption_info() {
        return [
            'available' => self::is_encryption_available(),
            'method' => self::CIPHER_METHOD,
            'key_length' => 256,
            'using_wp_salt' => true,
        ];
    }
}
