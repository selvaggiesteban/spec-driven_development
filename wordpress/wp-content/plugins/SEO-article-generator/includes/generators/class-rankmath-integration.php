<?php
/**
 * Integración con RankMath
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_RankMath_Integration {

    /**
     * Verificar si RankMath está activo
     */
    public function is_active() {
        return class_exists('RankMath') || defined('RANK_MATH_VERSION');
    }

    /**
     * Configurar datos SEO para un post
     */
    public function set_seo_data($post_id, $data) {
        if (!$this->is_active()) {
            return false;
        }

        // Focus keyword
        if (!empty($data['focus_keyword'])) {
            update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($data['focus_keyword']));
        }

        // Meta título
        if (!empty($data['meta_title'])) {
            update_post_meta($post_id, 'rank_math_title', sanitize_text_field($data['meta_title']));
        }

        // Meta descripción
        if (!empty($data['meta_description'])) {
            update_post_meta($post_id, 'rank_math_description', sanitize_text_field($data['meta_description']));
        }

        // Robots meta
        if (!empty($data['robots'])) {
            update_post_meta($post_id, 'rank_math_robots', $data['robots']);
        }

        // Canonical URL
        if (!empty($data['canonical_url'])) {
            update_post_meta($post_id, 'rank_math_canonical_url', esc_url($data['canonical_url']));
        }

        // Schema type
        if (!empty($data['schema_type'])) {
            $this->set_schema_type($post_id, $data['schema_type']);
        }

        // FAQ Schema
        if (!empty($data['faq_schema'])) {
            $this->set_faq_schema($post_id, $data['faq_schema']);
        }

        return true;
    }

    /**
     * Obtener datos SEO de un post
     */
    public function get_seo_data($post_id) {
        if (!$this->is_active()) {
            return [];
        }

        return [
            'focus_keyword' => get_post_meta($post_id, 'rank_math_focus_keyword', true),
            'meta_title' => get_post_meta($post_id, 'rank_math_title', true),
            'meta_description' => get_post_meta($post_id, 'rank_math_description', true),
            'seo_score' => $this->get_seo_score($post_id),
            'robots' => get_post_meta($post_id, 'rank_math_robots', true),
        ];
    }

    /**
     * Obtener SEO score de RankMath
     */
    public function get_seo_score($post_id) {
        if (!$this->is_active()) {
            return 0;
        }

        $score = get_post_meta($post_id, 'rank_math_seo_score', true);
        return intval($score);
    }

    /**
     * Configurar tipo de schema
     */
    private function set_schema_type($post_id, $type) {
        $valid_types = ['Article', 'BlogPosting', 'NewsArticle', 'HowTo', 'FAQ'];
        
        if (!in_array($type, $valid_types)) {
            $type = 'Article';
        }

        update_post_meta($post_id, 'rank_math_rich_snippet', strtolower($type));
    }

    /**
     * Configurar FAQ Schema
     */
    public function set_faq_schema($post_id, $faqs) {
        if (!$this->is_active() || empty($faqs)) {
            return false;
        }

        $schema_faqs = [];
        foreach ($faqs as $faq) {
            if (!empty($faq['question']) && !empty($faq['answer'])) {
                $schema_faqs[] = [
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => wp_kses_post($faq['answer']),
                ];
            }
        }

        if (!empty($schema_faqs)) {
            update_post_meta($post_id, 'rank_math_rich_snippet', 'faq');
            update_post_meta($post_id, 'rank_math_snippet_faq_question', $schema_faqs);
        }

        return true;
    }

    /**
     * Sincronizar score SEO del plugin con RankMath
     */
    public function sync_seo_score($post_id, $plugin_score) {
        if (!$this->is_active()) {
            return;
        }

        // Obtener score de RankMath
        $rankmath_score = $this->get_seo_score($post_id);

        // Registrar diferencia para análisis
        update_post_meta($post_id, '_sag_seo_score', $plugin_score);
        update_post_meta($post_id, '_sag_rankmath_score', $rankmath_score);
    }

    /**
     * Obtener sugerencias de enlaces de RankMath (si está disponible)
     */
    public function get_link_suggestions($post_id) {
        if (!$this->is_active()) {
            return [];
        }

        // RankMath Pro tiene funcionalidad de link suggestions
        // Esta es una implementación base que puede expandirse
        $suggestions = get_post_meta($post_id, 'rank_math_internal_links_suggestions', true);
        
        return is_array($suggestions) ? $suggestions : [];
    }

    /**
     * Obtener keywords pillar de RankMath
     */
    public function get_pillar_content() {
        if (!$this->is_active()) {
            return [];
        }

        global $wpdb;

        // Buscar posts marcados como pillar content
        $results = $wpdb->get_results(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = 'rank_math_pillar_content' 
             AND meta_value = 'on'",
            ARRAY_A
        );

        $pillar_posts = [];
        foreach ($results as $row) {
            $post = get_post($row['post_id']);
            if ($post && $post->post_status === 'publish') {
                $pillar_posts[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'focus_keyword' => get_post_meta($post->ID, 'rank_math_focus_keyword', true),
                ];
            }
        }

        return $pillar_posts;
    }

    /**
     * Verificar si sincronización con RankMath está habilitada
     */
    public function is_sync_enabled() {
        return get_option('sag_rankmath_sync', true) && $this->is_active();
    }

    /**
     * Obtener análisis de competencia de keyword (si RankMath lo ofrece)
     */
    public function get_keyword_analysis($keyword) {
        if (!$this->is_active()) {
            return null;
        }

        // Esta funcionalidad depende de RankMath Pro
        // Devolvemos null si no está disponible
        return null;
    }
}
