<?php
/**
 * Gestión de base de datos
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Database {

    /**
     * Obtener nombre de tabla con prefijo
     */
    public static function get_table($name) {
        global $wpdb;
        return $wpdb->prefix . 'sag_' . $name;
    }

    /**
     * Insertar artículo generado
     */
    public static function insert_article($data) {
        global $wpdb;
        
        $wpdb->insert(
            self::get_table('articles'),
            [
                'post_id' => $data['post_id'] ?? null,
                'keyword_main' => $data['keyword_main'],
                'keywords_secondary' => wp_json_encode($data['keywords_secondary'] ?? []),
                'article_type' => $data['article_type'] ?? 'guide',
                'word_count' => $data['word_count'] ?? 0,
                'seo_score' => $data['seo_score'] ?? 0,
                'status' => $data['status'] ?? 'draft',
                'meta_data' => wp_json_encode($data['meta_data'] ?? []),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Actualizar artículo
     */
    public static function update_article($id, $data) {
        global $wpdb;

        $update_data = [];
        $format = [];

        if (isset($data['post_id'])) {
            $update_data['post_id'] = $data['post_id'];
            $format[] = '%d';
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }
        if (isset($data['published_at'])) {
            $update_data['published_at'] = $data['published_at'];
            $format[] = '%s';
        }
        if (isset($data['seo_score'])) {
            $update_data['seo_score'] = $data['seo_score'];
            $format[] = '%d';
        }

        return $wpdb->update(
            self::get_table('articles'),
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );
    }

    /**
     * Obtener artículo por ID
     */
    public static function get_article($id) {
        global $wpdb;
        $table = self::get_table('articles');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Obtener artículos recientes
     */
    public static function get_recent_articles($limit = 10) {
        global $wpdb;
        $table = self::get_table('articles');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY generated_at DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Guardar análisis de contenido
     */
    public static function save_analysis($post_id, $data) {
        global $wpdb;
        $table = self::get_table('content_analysis');

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE post_id = %d",
            $post_id
        ));

        $record = [
            'post_id' => $post_id,
            'keywords' => wp_json_encode($data['keywords'] ?? []),
            'internal_links_count' => $data['internal_links_count'] ?? 0,
            'external_links_count' => $data['external_links_count'] ?? 0,
            'word_count' => $data['word_count'] ?? 0,
            'readability_score' => $data['readability_score'] ?? 0,
            'last_analyzed' => current_time('mysql'),
            'suggestions' => wp_json_encode($data['suggestions'] ?? []),
        ];

        if ($existing) {
            $wpdb->update($table, $record, ['post_id' => $post_id]);
        } else {
            $wpdb->insert($table, $record);
        }

        return true;
    }

    /**
     * Obtener análisis de un post
     */
    public static function get_analysis($post_id) {
        global $wpdb;
        $table = self::get_table('content_analysis');
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d",
            $post_id
        ));
    }

    /**
     * Guardar sugerencia de enlace
     */
    public static function save_link_suggestion($data) {
        global $wpdb;
        
        return $wpdb->insert(
            self::get_table('link_suggestions'),
            [
                'source_post_id' => $data['source_post_id'],
                'target_post_id' => $data['target_post_id'],
                'anchor_text' => $data['anchor_text'],
                'context' => $data['context'] ?? '',
                'relevance_score' => $data['relevance_score'] ?? 0,
                'status' => 'pending',
            ]
        );
    }

    /**
     * Obtener sugerencias pendientes
     */
    public static function get_pending_suggestions($post_id = null) {
        global $wpdb;
        $table = self::get_table('link_suggestions');

        if ($post_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE source_post_id = %d AND status = 'pending' ORDER BY relevance_score DESC",
                $post_id
            ));
        }

        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'pending' ORDER BY relevance_score DESC LIMIT 50"
        );
    }

    /**
     * Añadir a cola de publicación
     */
    public static function add_to_queue($article_id, $scheduled_for, $priority = 10) {
        global $wpdb;
        
        return $wpdb->insert(
            self::get_table('publication_queue'),
            [
                'article_id' => $article_id,
                'scheduled_for' => $scheduled_for,
                'priority' => $priority,
                'status' => 'pending',
            ]
        );
    }

    /**
     * Obtener items pendientes de la cola
     */
    public static function get_pending_queue_items() {
        global $wpdb;
        $table = self::get_table('publication_queue');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'pending' 
             AND scheduled_for <= %s 
             ORDER BY priority ASC, scheduled_for ASC",
            current_time('mysql')
        ));
    }

    /**
     * Actualizar estado de item en cola
     */
    public static function update_queue_item($id, $status, $error = null) {
        global $wpdb;
        
        $data = [
            'status' => $status,
            'processed_at' => current_time('mysql'),
        ];
        
        if ($error) {
            $data['error_message'] = $error;
        }

        return $wpdb->update(
            self::get_table('publication_queue'),
            $data,
            ['id' => $id]
        );
    }

    /**
     * Estadísticas del dashboard
     */
    public static function get_dashboard_stats() {
        global $wpdb;
        
        $articles_table = self::get_table('articles');
        $queue_table = self::get_table('publication_queue');

        return [
            'total_generated' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $articles_table"),
            'published' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $articles_table WHERE status = 'published'"),
            'drafts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $articles_table WHERE status = 'draft'"),
            'scheduled' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue_table WHERE status = 'pending'"),
            'avg_seo_score' => (float) $wpdb->get_var("SELECT AVG(seo_score) FROM $articles_table WHERE seo_score > 0"),
        ];
    }
}
