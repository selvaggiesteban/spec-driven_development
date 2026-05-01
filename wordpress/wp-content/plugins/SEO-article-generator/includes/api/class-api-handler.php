<?php
/**
 * Manejador unificado de API
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_API_Handler {

    /**
     * Cliente Gemini
     */
    private $gemini;

    /**
     * Generador de imágenes
     */
    private $image_generator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->gemini = new SAG_Gemini_Client();
        $this->image_generator = new SAG_Image_Generator();
    }

    /**
     * Verificar conexión con la API
     */
    public function test_connection() {
        if (!$this->gemini->is_configured()) {
            return [
                'status' => 'error',
                'message' => __('API Key no configurada.', 'seo-article-generator'),
            ];
        }

        // Hacer una petición simple de prueba
        $result = $this->gemini->generate_content('Say "API connection successful" in Spanish.', [
            'max_tokens' => 50,
            'temperature' => 0.1,
        ]);

        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'status' => 'success',
            'message' => __('Conexión exitosa con Gemini API.', 'seo-article-generator'),
            'model' => $result['model'],
        ];
    }

    /**
     * Obtener cliente Gemini
     */
    public function get_gemini() {
        return $this->gemini;
    }

    /**
     * Obtener generador de imágenes
     */
    public function get_image_generator() {
        return $this->image_generator;
    }

    /**
     * Obtener modelos disponibles
     */
    public function get_available_models() {
        return [
            'text' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash (Recomendado)',
                'gemini-2.5-pro' => 'Gemini 2.5 Pro (Mayor calidad)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            ],
            'image' => [
                'imagen-3.0-generate-001' => 'Imagen 3.0 (Recomendado)',
                'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash Experimental (Multimodal)',
            ],
        ];
    }

    /**
     * Obtener estadísticas de uso (si la API lo proporciona)
     */
    public function get_usage_stats() {
        // Por ahora, estadísticas locales
        return [
            'articles_generated' => (int) get_option('sag_total_articles_generated', 0),
            'images_generated' => (int) get_option('sag_total_images_generated', 0),
            'last_request' => get_option('sag_last_api_request', ''),
        ];
    }

    /**
     * Incrementar contador de uso
     */
    public function increment_usage($type) {
        if ($type === 'article') {
            $count = (int) get_option('sag_total_articles_generated', 0);
            update_option('sag_total_articles_generated', $count + 1);
        } elseif ($type === 'image') {
            $count = (int) get_option('sag_total_images_generated', 0);
            update_option('sag_total_images_generated', $count + 1);
        }
        
        update_option('sag_last_api_request', current_time('mysql'));
    }
}
