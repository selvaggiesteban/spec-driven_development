<?php
/**
 * Página de configuración
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Registrar ajustes
     */
    public function register_settings() {
        // API Settings
        register_setting('sag_settings', 'sag_api_key', [
            'sanitize_callback' => [$this, 'sanitize_and_encrypt_api_key'],
        ]);
        register_setting('sag_settings', 'sag_model_text', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('sag_settings', 'sag_model_image', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // General Settings
        register_setting('sag_settings', 'sag_default_language', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('sag_settings', 'sag_default_author', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('sag_settings', 'sag_default_category', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('sag_settings', 'sag_default_status', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // SEO Settings
        register_setting('sag_settings', 'sag_meta_title_max', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('sag_settings', 'sag_meta_desc_max', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('sag_settings', 'sag_keyword_density', [
            'sanitize_callback' => 'floatval',
        ]);
        register_setting('sag_settings', 'sag_max_internal_links', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('sag_settings', 'sag_max_external_links', [
            'sanitize_callback' => 'intval',
        ]);

        // Publishing Settings
        register_setting('sag_settings', 'sag_publish_frequency', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('sag_settings', 'sag_preferred_time', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Integration Settings
        register_setting('sag_settings', 'sag_rankmath_sync', [
            'sanitize_callback' => function($value) {
                return (bool) $value;
            },
        ]);

        // Link Settings - Enlaces internos
        register_setting('sag_settings', 'sag_internal_links_rel', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'follow',
        ]);

        // Link Settings - Enlaces externos
        register_setting('sag_settings', 'sag_external_links_rel', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'auto',
        ]);

        // Link Settings - Enlaces patrocinados
        register_setting('sag_settings', 'sag_external_sponsored_enabled', [
            'sanitize_callback' => function($value) {
                return (bool) $value;
            },
            'default' => false,
        ]);

        // Link Settings - Target blank
        register_setting('sag_settings', 'sag_external_target_blank', [
            'sanitize_callback' => function($value) {
                return (bool) $value;
            },
            'default' => true,
        ]);

        // Link Checker Settings - Máximo de enlaces a verificar
        register_setting('sag_settings', 'sag_max_links_to_check', [
            'sanitize_callback' => function($value) {
                $value = intval($value);
                // Límite absoluto: mínimo 1, máximo 100
                return max(1, min($value, 100));
            },
            'default' => 50,
        ]);
    }

    /**
     * Sanitizar y encriptar API key
     *
     * @param string $value Valor de la API key
     * @return string API key encriptada
     */
    public function sanitize_and_encrypt_api_key($value) {
        // Sanitizar primero
        $sanitized = sanitize_text_field($value);

        // Si está vacío, devolver vacío
        if (empty($sanitized)) {
            return '';
        }

        // Encriptar antes de guardar
        $encrypted = SAG_Crypto_Helper::encrypt($sanitized);

        // Si la encriptación falla, guardar sanitizado pero sin encriptar
        // y registrar warning
        if ($encrypted === false) {
            error_log('SAG Warning: No se pudo encriptar API key, guardando sin encriptar');
            return $sanitized;
        }

        return $encrypted;
    }

    /**
     * Obtener opciones de idiomas
     */
    public static function get_language_options() {
        return [
            'es' => 'Español',
            'en' => 'English',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'ru' => 'Русский',
            'ja' => '日本語',
            'zh' => '中文',
            'ko' => '한국어',
        ];
    }

    /**
     * Obtener opciones de frecuencia
     */
    public static function get_frequency_options() {
        return [
            'daily' => __('Diario', 'seo-article-generator'),
            'twice_daily' => __('Dos veces al día', 'seo-article-generator'),
            'weekly' => __('Semanal', 'seo-article-generator'),
            'biweekly' => __('Quincenal', 'seo-article-generator'),
        ];
    }

    /**
     * Obtener opciones de estado por defecto
     */
    public static function get_status_options() {
        return [
            'draft' => __('Borrador', 'seo-article-generator'),
            'pending' => __('Pendiente de revisión', 'seo-article-generator'),
            'publish' => __('Publicado', 'seo-article-generator'),
        ];
    }
}

// Inicializar
new SAG_Settings_Page();
