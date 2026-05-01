<?php
/**
 * Generador de artículos
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Article_Generator {

    /**
     * Cliente Gemini
     */
    private $gemini;

    /**
     * Optimizador SEO
     */
    private $seo_optimizer;

    /**
     * Formateador de contenido
     */
    private $formatter;

    /**
     * Integración RankMath
     */
    private $rankmath;

    /**
     * Generador de imágenes
     */
    private $image_generator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->gemini = new SAG_Gemini_Client();
        $this->seo_optimizer = new SAG_SEO_Optimizer();
        $this->formatter = new SAG_Content_Formatter();
        $this->rankmath = new SAG_RankMath_Integration();
        $this->image_generator = new SAG_Image_Generator();
    }

    /**
     * Generar artículo completo
     */
    public function generate($params) {
        $keyword = $params['keyword'] ?? '';
        
        if (empty($keyword)) {
            return new WP_Error('empty_keyword', __('La keyword principal es requerida.', 'seo-article-generator'));
        }

        // 1. Generar contenido del artículo
        $article_result = $this->gemini->generate_seo_article($params);
        
        if (is_wp_error($article_result)) {
            return $article_result;
        }

        $raw_content = $article_result['text'];

        // 2. Extraer meta descripción del contenido
        $meta_description = $this->extract_meta_description($raw_content);
        $content_without_meta = $this->remove_meta_from_content($raw_content);

        // 3. Formatear y limpiar contenido
        $formatted_content = $this->formatter->format($content_without_meta);

        // 4. Extraer título H1
        $title = $this->extract_title($formatted_content);
        $content_without_title = $this->remove_title_from_content($formatted_content);

        // 5. Generar meta datos adicionales si no fueron extraídos
        $meta_result = null;
        if (empty($meta_description)) {
            $meta_result = $this->gemini->generate_meta($content_without_title, $keyword);
            if (!is_wp_error($meta_result)) {
                $meta_description = $meta_result['meta_description'] ?? '';
            }
        }

        // 5.1. Generar slug SEO-friendly para URL amigable
        $slug = $this->generate_seo_slug($keyword, $meta_result, $title);

        // 6. Optimizar para SEO
        $optimized = $this->seo_optimizer->optimize($content_without_title, $keyword);

        // 7. Contar palabras
        $word_count = str_word_count(wp_strip_all_tags($optimized['content']));

        // 8. Generar imagen destacada si se solicitó
        $featured_image_id = null;
        $image_error = null;
        if (!empty($params['generate_image'])) {
            $image_result = $this->image_generator->generate_featured_image($optimized['content'], $keyword);
            if (!is_wp_error($image_result)) {
                $featured_image_id = $image_result['attachment_id'];

                // Incrementar contador
                $api_handler = new SAG_API_Handler();
                $api_handler->increment_usage('image');
            } else {
                // Registrar error pero continuar con el artículo
                $image_error = $image_result->get_error_message();
                error_log('SAG: Error generando imagen destacada: ' . $image_error);
            }
        }

        // 9. Preparar extracto destacado desde meta descripción
        $excerpt = $this->prepare_excerpt($meta_description, $optimized['content']);

        // 10. Obtener configuración de publicación desde parámetros o settings
        $post_status = $params['post_status'] ?? get_option('sag_default_status', 'draft');
        $post_author = $params['author'] ?? get_option('sag_default_author', get_current_user_id());
        $post_category = $params['category'] ?? get_option('sag_default_category', 1);

        // 11. Crear el post
        $post_data = [
            'post_title' => $title ?: $keyword,
            'post_name' => $slug, // URL amigable SEO desde keyword
            'post_content' => $optimized['content'],
            'post_excerpt' => $excerpt, // Extracto destacado desde meta descripción
            'post_status' => $post_status,
            'post_author' => $post_author,
            'post_type' => 'post',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // 12. Asignar categoría (post_category está deprecado, usar wp_set_post_categories)
        if (!empty($post_category)) {
            wp_set_post_categories($post_id, [$post_category], false);
        }

        // 13. Asignar imagen destacada
        if ($featured_image_id) {
            set_post_thumbnail($post_id, $featured_image_id);
        }

        // 14. Configurar RankMath SEO
        if ($this->rankmath->is_active()) {
            $this->rankmath->set_seo_data($post_id, [
                'focus_keyword' => $keyword,
                'meta_title' => $this->seo_optimizer->generate_meta_title($title, $keyword),
                'meta_description' => $meta_description,
            ]);
        }

        // 15. Guardar en base de datos del plugin
        $article_id = SAG_Database::insert_article([
            'post_id' => $post_id,
            'keyword_main' => $keyword,
            'keywords_secondary' => $optimized['secondary_keywords'] ?? [],
            'article_type' => $params['type'] ?? 'guide',
            'word_count' => $word_count,
            'seo_score' => $optimized['seo_score'] ?? 0,
            'status' => $post_status,
            'meta_data' => [
                'tone' => $params['tone'] ?? 'professional',
                'length' => $params['length'] ?? 'medium',
                'include_toc' => $params['include_toc'] ?? false,
                'include_faq' => $params['include_faq'] ?? false,
            ],
        ]);

        // 14. Incrementar contador de artículos
        $api_handler = new SAG_API_Handler();
        $api_handler->increment_usage('article');

        $result = [
            'success' => true,
            'post_id' => $post_id,
            'article_id' => $article_id,
            'title' => $title,
            'word_count' => $word_count,
            'seo_score' => $optimized['seo_score'] ?? 0,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'preview_url' => get_preview_post_link($post_id),
            'featured_image' => $featured_image_id ? wp_get_attachment_url($featured_image_id) : null,
        ];

        // Agregar advertencia si hubo error en la imagen pero el artículo se creó correctamente
        if ($image_error) {
            $result['image_warning'] = $image_error;
        }

        return $result;
    }

    /**
     * Extraer meta descripción del contenido
     */
    private function extract_meta_description($content) {
        if (preg_match('/\[META:\s*(.+?)\]/', $content, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Eliminar meta del contenido
     */
    private function remove_meta_from_content($content) {
        return preg_replace('/\[META:\s*.+?\]\s*/', '', $content);
    }

    /**
     * Extraer título H1
     */
    private function extract_title($content) {
        if (preg_match('/<h1[^>]*>(.+?)<\/h1>/i', $content, $matches)) {
            return wp_strip_all_tags($matches[1]);
        }
        return '';
    }

    /**
     * Eliminar H1 del contenido
     */
    private function remove_title_from_content($content) {
        return preg_replace('/<h1[^>]*>.+?<\/h1>\s*/i', '', $content, 1);
    }

    /**
     * Regenerar artículo existente
     */
    public function regenerate($post_id, $params) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'seo-article-generator'));
        }

        // Generar nuevo contenido
        $result = $this->generate($params);
        
        if (is_wp_error($result)) {
            return $result;
        }

        // Actualizar post existente en lugar de crear uno nuevo
        $new_post = get_post($result['post_id']);
        
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $new_post->post_content,
            'post_title' => $new_post->post_title,
        ]);

        // Eliminar el post temporal
        wp_delete_post($result['post_id'], true);

        return [
            'success' => true,
            'post_id' => $post_id,
            'message' => __('Artículo regenerado exitosamente.', 'seo-article-generator'),
        ];
    }

    /**
     * Generar slug SEO-friendly para URL amigable
     *
     * @param string $keyword Keyword principal
     * @param array|null $meta_result Resultado de generación de meta (puede contener slug)
     * @param string $title Título del artículo
     * @return string Slug único y optimizado
     */
    private function generate_seo_slug($keyword, $meta_result, $title) {
        $slug = '';

        // Prioridad 1: Usar slug generado por Gemini si existe
        if (!empty($meta_result['slug'])) {
            $slug = $meta_result['slug'];
        }
        // Prioridad 2: Generar desde título si existe
        elseif (!empty($title)) {
            $slug = sanitize_title($title);
        }
        // Prioridad 3: Generar desde keyword
        else {
            $slug = sanitize_title($keyword);
        }

        // Limpiar y optimizar el slug
        $slug = $this->optimize_slug($slug);

        // Verificar unicidad y agregar sufijo si es necesario
        $slug = wp_unique_post_slug($slug, 0, 'draft', 'post', 0);

        return $slug;
    }

    /**
     * Optimizar slug para SEO
     *
     * @param string $slug Slug a optimizar
     * @return string Slug optimizado
     */
    private function optimize_slug($slug) {
        // Convertir a minúsculas
        $slug = strtolower($slug);

        // Eliminar caracteres no permitidos (solo letras, números y guiones)
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        // Eliminar guiones múltiples consecutivos
        $slug = preg_replace('/-+/', '-', $slug);

        // Eliminar guiones al inicio y final
        $slug = trim($slug, '-');

        // Limitar longitud (WordPress recomienda máximo 200 caracteres)
        // Para SEO, es mejor mantenerlo entre 50-70 caracteres
        $max_length = apply_filters('sag_slug_max_length', 70);
        if (strlen($slug) > $max_length) {
            // Cortar en el último guión antes del límite
            $slug = substr($slug, 0, $max_length);
            $last_dash = strrpos($slug, '-');
            if ($last_dash !== false) {
                $slug = substr($slug, 0, $last_dash);
            }
        }

        return $slug;
    }

    /**
     * Preparar extracto destacado desde meta descripción
     *
     * @param string $meta_description Meta descripción
     * @param string $content Contenido del artículo
     * @return string Extracto preparado
     */
    private function prepare_excerpt($meta_description, $content) {
        $excerpt = '';

        // Usar meta descripción si existe
        if (!empty($meta_description)) {
            $excerpt = $meta_description;
        }
        // Fallback: generar desde contenido
        else {
            $excerpt = wp_strip_all_tags($content);
        }

        // Limitar a longitud apropiada para excerpt
        // WordPress por defecto usa 55 palabras
        $excerpt_length = apply_filters('sag_excerpt_length', 55);
        $excerpt = wp_trim_words($excerpt, $excerpt_length, '...');

        return $excerpt;
    }
}
