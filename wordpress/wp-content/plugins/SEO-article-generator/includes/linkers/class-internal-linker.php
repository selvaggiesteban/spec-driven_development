<?php
/**
 * Gestor de enlaces internos
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Internal_Linker {

    /**
     * Cliente Gemini
     */
    private $gemini;

    /**
     * Analizador de enlaces
     */
    private $link_analyzer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->gemini = new SAG_Gemini_Client();
        $this->link_analyzer = new SAG_Link_Analyzer();
    }

    /**
     * Obtener sugerencias de enlaces para un post
     */
    public function get_suggestions($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return [];
        }

        // Verificar si hay sugerencias en caché
        $cached = SAG_Database::get_pending_suggestions($post_id);
        if (!empty($cached)) {
            return $this->format_suggestions($cached);
        }

        // Generar nuevas sugerencias
        return $this->generate_suggestions($post);
    }

    /**
     * Generar sugerencias de enlaces
     */
    private function generate_suggestions($post) {
        $content = $post->post_content;
        $post_id = $post->ID;

        // Obtener posts existentes para enlazar
        $available_posts = $this->get_linkable_posts($post_id);

        if (empty($available_posts)) {
            return [];
        }

        // Si la API está configurada, usar IA
        if ($this->gemini->is_configured()) {
            $ai_suggestions = $this->gemini->suggest_internal_links($content, $available_posts);
            
            if (!is_wp_error($ai_suggestions) && !empty($ai_suggestions)) {
                return $this->process_ai_suggestions($post_id, $ai_suggestions, $content);
            }
        }

        // Fallback: sugerencias basadas en keywords
        return $this->generate_keyword_based_suggestions($post, $available_posts);
    }

    /**
     * Obtener posts disponibles para enlazar
     */
    private function get_linkable_posts($exclude_id) {
        // Obtener enlaces actuales para excluirlos
        $current_links = $this->link_analyzer->analyze_post_links($exclude_id);
        $linked_ids = array_filter(array_column($current_links['internal'], 'post_id'));
        $linked_ids[] = $exclude_id;

        return get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'post__not_in' => $linked_ids,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    /**
     * Procesar sugerencias de IA
     */
    private function process_ai_suggestions($source_id, $suggestions, $content) {
        $processed = [];
        $max_links = (int) get_option('sag_max_internal_links', 5);

        foreach (array_slice($suggestions, 0, $max_links) as $suggestion) {
            $target_id = $suggestion['target_post_id'] ?? 0;
            $anchor = $suggestion['anchor_text'] ?? '';
            
            if (!$target_id || !$anchor) {
                continue;
            }

            // Verificar que el anchor existe en el contenido
            if (stripos($content, $anchor) === false) {
                continue;
            }

            // Verificar que el post target existe
            $target_post = get_post($target_id);
            if (!$target_post || $target_post->post_status !== 'publish') {
                continue;
            }

            // Guardar sugerencia
            SAG_Database::save_link_suggestion([
                'source_post_id' => $source_id,
                'target_post_id' => $target_id,
                'anchor_text' => $anchor,
                'context' => $this->get_context($content, $anchor),
                'relevance_score' => 0.8,
            ]);

            $processed[] = [
                'anchor_text' => $anchor,
                'target_id' => $target_id,
                'target_title' => $target_post->post_title,
                'target_url' => get_permalink($target_id),
                'context' => $this->get_context($content, $anchor),
                'reason' => $suggestion['relevance_reason'] ?? '',
            ];
        }

        return $processed;
    }

    /**
     * Generar sugerencias basadas en keywords
     */
    private function generate_keyword_based_suggestions($post, $available_posts) {
        $suggestions = [];
        $content = mb_strtolower($post->post_content);
        $max_links = (int) get_option('sag_max_internal_links', 5);

        foreach ($available_posts as $target) {
            // Usar título como posible anchor
            $title_words = explode(' ', mb_strtolower($target->post_title));
            
            // Buscar coincidencias de 2+ palabras del título en el contenido
            for ($i = 0; $i < count($title_words) - 1; $i++) {
                $phrase = $title_words[$i] . ' ' . $title_words[$i + 1];
                
                if (mb_strlen($phrase) > 5 && mb_stripos($content, $phrase) !== false) {
                    // Encontrar la frase exacta con mayúsculas originales
                    $original_phrase = $this->find_original_case($post->post_content, $phrase);
                    
                    if ($original_phrase) {
                        $suggestions[] = [
                            'anchor_text' => $original_phrase,
                            'target_id' => $target->ID,
                            'target_title' => $target->post_title,
                            'target_url' => get_permalink($target->ID),
                            'context' => $this->get_context($post->post_content, $original_phrase),
                            'reason' => __('Coincide con el título del artículo', 'seo-article-generator'),
                        ];
                        break;
                    }
                }
            }

            if (count($suggestions) >= $max_links) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Encontrar frase con mayúsculas originales
     */
    private function find_original_case($content, $phrase) {
        $text = wp_strip_all_tags($content);
        $pos = mb_stripos($text, $phrase);
        
        if ($pos !== false) {
            return mb_substr($text, $pos, mb_strlen($phrase));
        }
        
        return null;
    }

    /**
     * Obtener contexto alrededor del anchor
     */
    private function get_context($content, $anchor, $chars = 100) {
        $text = wp_strip_all_tags($content);
        $pos = mb_stripos($text, $anchor);
        
        if ($pos === false) {
            return '';
        }

        $start = max(0, $pos - $chars);
        $length = mb_strlen($anchor) + ($chars * 2);
        
        $context = mb_substr($text, $start, $length);
        
        // Añadir elipsis si es necesario
        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($start + $length < mb_strlen($text)) {
            $context .= '...';
        }

        return $context;
    }

    /**
     * Aplicar sugerencia de enlace
     */
    public function apply_suggestion($suggestion_id) {
        global $wpdb;
        
        $table = SAG_Database::get_table('link_suggestions');
        $suggestion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND status = 'pending'",
            $suggestion_id
        ));

        if (!$suggestion) {
            return new WP_Error('not_found', __('Sugerencia no encontrada.', 'seo-article-generator'));
        }

        $post = get_post($suggestion->source_post_id);
        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'seo-article-generator'));
        }

        $target_url = get_permalink($suggestion->target_post_id);
        $anchor = $suggestion->anchor_text;

        // Obtener configuración de rel para enlaces internos
        $rel_setting = get_option('sag_internal_links_rel', 'follow');
        $rel_attr = $rel_setting === 'nofollow' ? ' rel="nofollow"' : '';

        // Reemplazar en el contenido (solo primera ocurrencia que no esté ya enlazada)
        $content = $post->post_content;

        // Verificar que el anchor no esté ya enlazado
        if (preg_match('/<a[^>]*>' . preg_quote($anchor, '/') . '<\/a>/i', $content)) {
            return new WP_Error('already_linked', __('Este texto ya está enlazado.', 'seo-article-generator'));
        }

        // Reemplazar solo la primera ocurrencia fuera de tags HTML
        // El método replace_first_occurrence ahora construye el link con el texto original
        $new_content = $this->replace_first_occurrence($content, $anchor, $target_url, $rel_attr);

        if ($new_content === $content) {
            return new WP_Error('not_replaced', __('No se pudo aplicar el enlace.', 'seo-article-generator'));
        }

        // Actualizar post
        wp_update_post([
            'ID' => $post->ID,
            'post_content' => $new_content,
        ]);

        // Marcar sugerencia como aplicada
        $wpdb->update(
            $table,
            [
                'status' => 'applied',
                'applied_at' => current_time('mysql'),
            ],
            ['id' => $suggestion_id]
        );

        return [
            'success' => true,
            'message' => __('Enlace aplicado correctamente.', 'seo-article-generator'),
            'post_id' => $post->ID,
        ];
    }

    /**
     * Reemplazar primera ocurrencia (fuera de HTML tags)
     */
    private function replace_first_occurrence($content, $search, $target_url, $rel_attr = '') {
        // Dividir contenido en partes HTML y texto
        $parts = preg_split('/(<[^>]+>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $replaced = false;

        foreach ($parts as $i => $part) {
            // Solo procesar partes que no son HTML tags
            if (!$replaced && strpos($part, '<') !== 0) {
                $pos = mb_stripos($part, $search);
                if ($pos !== false) {
                    // Encontrar la coincidencia exacta con mayúsculas originales del contenido
                    $original_text = mb_substr($part, $pos, mb_strlen($search));

                    // Construir link con el texto original encontrado (preserva mayúsculas/minúsculas)
                    $link = '<a href="' . esc_url($target_url) . '"' . $rel_attr . '>' . esc_html($original_text) . '</a>';

                    // Reemplazar en el contenido
                    $parts[$i] = mb_substr($part, 0, $pos) . $link . mb_substr($part, $pos + mb_strlen($search));
                    $replaced = true;
                }
            }
        }

        return implode('', $parts);
    }

    /**
     * Ignorar sugerencia
     */
    public function ignore_suggestion($suggestion_id) {
        global $wpdb;
        
        return $wpdb->update(
            SAG_Database::get_table('link_suggestions'),
            ['status' => 'ignored'],
            ['id' => $suggestion_id]
        );
    }

    /**
     * Aplicar múltiples sugerencias
     */
    public function apply_all_suggestions($post_id) {
        $suggestions = SAG_Database::get_pending_suggestions($post_id);
        $results = ['applied' => 0, 'failed' => 0];

        foreach ($suggestions as $suggestion) {
            $result = $this->apply_suggestion($suggestion->id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
            } else {
                $results['applied']++;
            }
        }

        return $results;
    }

    /**
     * Formatear sugerencias de BD
     */
    private function format_suggestions($suggestions) {
        $formatted = [];
        
        foreach ($suggestions as $s) {
            $target = get_post($s->target_post_id);
            if ($target) {
                $formatted[] = [
                    'id' => $s->id,
                    'anchor_text' => $s->anchor_text,
                    'target_id' => $s->target_post_id,
                    'target_title' => $target->post_title,
                    'target_url' => get_permalink($s->target_post_id),
                    'context' => $s->context,
                    'relevance_score' => $s->relevance_score,
                ];
            }
        }

        return $formatted;
    }
}
