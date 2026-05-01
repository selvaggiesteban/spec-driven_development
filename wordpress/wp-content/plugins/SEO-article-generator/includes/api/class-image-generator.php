<?php
/**
 * Generador de imágenes con Gemini
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Image_Generator {

    /**
     * Endpoint base de la API
     */
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * API Key
     */
    private $api_key;

    /**
     * Modelo de imagen
     */
    private $image_model;

    /**
     * Constructor
     */
    public function __construct() {
        // Obtener y desencriptar API key
        $encrypted_key = get_option('sag_api_key', '');
        $this->api_key = !empty($encrypted_key) ? SAG_Crypto_Helper::decrypt($encrypted_key) : '';

        // Modelo por defecto: imagen-3.0-generate-001 (modelo de generación de imágenes)
        $this->image_model = get_option('sag_model_image', 'imagen-3.0-generate-001');
    }

    /**
     * Verificar si la API está configurada
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Generar imagen
     */
    public function generate($params) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', __('La API Key de Gemini no está configurada.', 'seo-article-generator'));
        }

        $prompt = $params['prompt'] ?? '';
        $style = $params['style'] ?? 'photographic';

        if (empty($prompt)) {
            return new WP_Error('empty_prompt', __('El prompt no puede estar vacío.', 'seo-article-generator'));
        }

        // Construir prompt mejorado según el estilo
        $enhanced_prompt = $this->enhance_prompt($prompt, $style);

        $url = $this->api_base . $this->image_model . ':generateContent?key=' . $this->api_key;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $enhanced_prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? __('Error desconocido en la API.', 'seo-article-generator');
            return new WP_Error('api_error', $error_message);
        }

        // Extraer imagen de la respuesta
        $image_data = $this->extract_image_from_response($body);

        if (is_wp_error($image_data)) {
            return $image_data;
        }

        // Guardar imagen en la biblioteca de medios
        // Pasar keyword desde params si existe
        $keyword = $params['keyword'] ?? '';
        $attachment_id = $this->save_to_media_library($image_data, $prompt, $keyword);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return [
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'prompt' => $prompt,
            'style' => $style,
        ];
    }

    /**
     * Mejorar prompt según estilo
     */
    private function enhance_prompt($prompt, $style) {
        $style_modifiers = [
            'photographic' => 'Create a professional, high-quality photograph. Photorealistic, sharp focus, good lighting. ',
            'illustration' => 'Create a modern digital illustration. Clean lines, vibrant colors, professional design. ',
            'minimal' => 'Create a minimalist, clean design. Simple shapes, limited color palette, elegant. ',
            'artistic' => 'Create an artistic interpretation. Creative, expressive, unique visual style. ',
            'infographic' => 'Create an infographic-style image. Data visualization, icons, clean layout. ',
            'corporate' => 'Create a professional corporate image. Business-appropriate, clean, trustworthy. ',
        ];

        $modifier = $style_modifiers[$style] ?? $style_modifiers['photographic'];
        
        return $modifier . "Image description: " . $prompt . ". High resolution, suitable for blog featured image.";
    }

    /**
     * Extraer imagen de la respuesta de Gemini
     */
    private function extract_image_from_response($response) {
        if (!isset($response['candidates'][0]['content']['parts'])) {
            return new WP_Error('no_image', __('La API no devolvió una imagen.', 'seo-article-generator'));
        }

        foreach ($response['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['inlineData'])) {
                return [
                    'data' => $part['inlineData']['data'],
                    'mime_type' => $part['inlineData']['mimeType'],
                ];
            }
        }

        return new WP_Error('no_image', __('No se encontró imagen en la respuesta.', 'seo-article-generator'));
    }

    /**
     * Guardar imagen en la biblioteca de medios
     */
    private function save_to_media_library($image_data, $prompt, $keyword = '') {
        $upload_dir = wp_upload_dir();

        // Determinar extensión según mime type
        $extensions = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];
        $extension = $extensions[$image_data['mime_type']] ?? 'png';

        // Generar nombre de archivo SEO-friendly desde keyword
        $filename = $this->generate_seo_filename($keyword, $prompt, $extension);
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Decodificar imagen
        $image_content = base64_decode($image_data['data']);

        if ($image_content === false) {
            return new WP_Error('decode_error', __('Error al decodificar la imagen.', 'seo-article-generator'));
        }

        // VALIDACIÓN DE SEGURIDAD: Verificar tamaño de la imagen
        $image_size = strlen($image_content);
        $max_size = apply_filters('sag_max_image_size', 5 * 1024 * 1024); // 5MB por defecto

        if ($image_size > $max_size) {
            return new WP_Error(
                'image_too_large',
                sprintf(
                    __('La imagen generada es demasiado grande (%s). Máximo permitido: %s', 'seo-article-generator'),
                    size_format($image_size),
                    size_format($max_size)
                )
            );
        }

        // VALIDACIÓN DE SEGURIDAD: Verificar tipo MIME válido
        $allowed_mimes = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($image_data['mime_type'], $allowed_mimes, true)) {
            return new WP_Error(
                'invalid_mime_type',
                sprintf(
                    __('Tipo de imagen no permitido: %s', 'seo-article-generator'),
                    $image_data['mime_type']
                )
            );
        }

        // Guardar imagen temporalmente
        $saved = file_put_contents($file_path, $image_content);

        if ($saved === false) {
            return new WP_Error('save_error', __('Error al guardar la imagen.', 'seo-article-generator'));
        }

        // VALIDACIÓN DE SEGURIDAD: Verificar dimensiones de la imagen
        $image_info = @getimagesize($file_path);

        if ($image_info === false) {
            // Eliminar archivo corrupto
            @unlink($file_path);
            return new WP_Error('invalid_image', __('El archivo no es una imagen válida.', 'seo-article-generator'));
        }

        list($width, $height) = $image_info;
        $max_width = apply_filters('sag_max_image_width', 4000);
        $max_height = apply_filters('sag_max_image_height', 4000);

        if ($width > $max_width || $height > $max_height) {
            // Eliminar archivo que excede dimensiones
            @unlink($file_path);
            return new WP_Error(
                'image_dimensions_exceeded',
                sprintf(
                    __('Las dimensiones de la imagen (%dx%d) exceden el máximo permitido (%dx%d).', 'seo-article-generator'),
                    $width,
                    $height,
                    $max_width,
                    $max_height
                )
            );
        }

        // Generar metadata SEO optimizada para la imagen
        $image_meta = $this->generate_image_metadata($keyword, $prompt);

        // Crear attachment en WordPress con metadata SEO
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $image_data['mime_type'],
            'post_title' => $image_meta['title'], // Título SEO optimizado
            'post_content' => $image_meta['description'], // Descripción para SEO
            'post_excerpt' => $image_meta['caption'], // Caption/leyenda de imagen
            'post_status' => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generar metadatos del attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Guardar alt text SEO optimizado
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $image_meta['alt_text']);

        return $attachment_id;
    }

    /**
     * Generar imagen destacada para artículo
     */
    public function generate_featured_image($article_content, $keyword) {
        // Crear prompt basado en el contenido
        $prompt = $this->create_featured_image_prompt($article_content, $keyword);

        return $this->generate([
            'prompt' => $prompt,
            'style' => 'photographic',
            'keyword' => $keyword, // Pasar keyword para metadata SEO
        ]);
    }

    /**
     * Crear prompt para imagen destacada
     */
    private function create_featured_image_prompt($content, $keyword) {
        // Obtener las primeras 500 palabras del contenido
        $content_preview = wp_trim_words(wp_strip_all_tags($content), 100, '');
        
        $gemini = new SAG_Gemini_Client();
        $result = $gemini->generate_content(
            "Based on this article content about '{$keyword}', suggest a single, specific image description for a featured blog image. The description should be visual, concrete, and suitable for AI image generation. Keep it under 100 words.\n\nContent:\n{$content_preview}",
            ['temperature' => 0.5, 'max_tokens' => 200]
        );

        if (is_wp_error($result)) {
            // Fallback: usar keyword directamente
            return "Professional blog featured image for article about: " . $keyword;
        }

        return trim($result['text']);
    }

    /**
     * Generar nombre de archivo SEO-friendly
     *
     * @param string $keyword Keyword principal
     * @param string $prompt Prompt de generación
     * @param string $extension Extensión del archivo
     * @return string Nombre de archivo optimizado
     */
    private function generate_seo_filename($keyword, $prompt, $extension) {
        $filename = '';

        // Prioridad 1: Usar keyword si existe
        if (!empty($keyword)) {
            $filename = sanitize_file_name($keyword);
        }
        // Prioridad 2: Usar prompt
        elseif (!empty($prompt)) {
            // Tomar primeras palabras del prompt
            $filename = sanitize_file_name(wp_trim_words($prompt, 5, ''));
        }
        // Fallback
        else {
            $filename = 'sag-generated-image';
        }

        // Limpiar nombre de archivo
        $filename = strtolower($filename);
        $filename = preg_replace('/[^a-z0-9-]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');

        // Limitar longitud
        $filename = substr($filename, 0, 50);

        // Agregar timestamp para unicidad
        $filename .= '-' . time();

        // Aplicar filtro para personalización
        $filename = apply_filters('sag_image_filename', $filename, $keyword, $prompt);

        return $filename . '.' . $extension;
    }

    /**
     * Generar metadata SEO optimizada para imagen
     *
     * @param string $keyword Keyword principal
     * @param string $prompt Prompt de generación
     * @return array Metadata optimizada (title, alt_text, description, caption)
     */
    private function generate_image_metadata($keyword, $prompt) {
        $metadata = [];

        // Título de la imagen
        if (!empty($keyword)) {
            $metadata['title'] = ucfirst($keyword);
        } else {
            $metadata['title'] = ucfirst(wp_trim_words($prompt, 8, ''));
        }

        // Alt text SEO optimizado
        if (!empty($keyword)) {
            $metadata['alt_text'] = sprintf(
                __('%s - Imagen destacada', 'seo-article-generator'),
                ucfirst($keyword)
            );
        } else {
            $metadata['alt_text'] = wp_trim_words($prompt, 15, '');
        }

        // Limitar alt text a 125 caracteres (buena práctica SEO)
        $metadata['alt_text'] = sanitize_text_field(substr($metadata['alt_text'], 0, 125));

        // Descripción de la imagen
        if (!empty($keyword)) {
            $metadata['description'] = sprintf(
                __('Imagen destacada para el artículo sobre %s. Generada automáticamente con IA para optimización SEO.', 'seo-article-generator'),
                $keyword
            );
        } else {
            $metadata['description'] = __('Imagen destacada generada automáticamente con IA.', 'seo-article-generator');
        }

        // Caption/leyenda
        if (!empty($keyword)) {
            $metadata['caption'] = sprintf(
                __('Imagen relacionada con %s', 'seo-article-generator'),
                $keyword
            );
        } else {
            $metadata['caption'] = '';
        }

        // Aplicar filtros para personalización
        $metadata = apply_filters('sag_image_metadata', $metadata, $keyword, $prompt);

        return $metadata;
    }
}
