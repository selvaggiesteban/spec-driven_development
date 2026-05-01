<?php
/**
 * Activador del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Activator {

    /**
     * Ejecutar al activar el plugin
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_capabilities();
        
        // Limpiar cache de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crear tablas de base de datos
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de artículos generados
        $table_articles = $wpdb->prefix . 'sag_articles';
        $sql_articles = "CREATE TABLE $table_articles (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED DEFAULT NULL,
            keyword_main varchar(255) NOT NULL,
            keywords_secondary longtext,
            article_type varchar(50) NOT NULL DEFAULT 'guide',
            word_count int(11) NOT NULL DEFAULT 0,
            seo_score int(11) DEFAULT 0,
            generated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            published_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            meta_data longtext,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY generated_at (generated_at)
        ) $charset_collate;";
        dbDelta($sql_articles);

        // Tabla de análisis de contenido
        $table_analysis = $wpdb->prefix . 'sag_content_analysis';
        $sql_analysis = "CREATE TABLE $table_analysis (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            keywords longtext,
            internal_links_count int(11) DEFAULT 0,
            external_links_count int(11) DEFAULT 0,
            word_count int(11) DEFAULT 0,
            readability_score float DEFAULT 0,
            last_analyzed datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            suggestions longtext,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY last_analyzed (last_analyzed)
        ) $charset_collate;";
        dbDelta($sql_analysis);

        // Tabla de sugerencias de enlaces
        $table_links = $wpdb->prefix . 'sag_link_suggestions';
        $sql_links = "CREATE TABLE $table_links (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) UNSIGNED NOT NULL,
            target_post_id bigint(20) UNSIGNED NOT NULL,
            anchor_text varchar(255) NOT NULL,
            context text,
            relevance_score float DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY target_post_id (target_post_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_links);

        // Tabla de cola de publicación
        $table_queue = $wpdb->prefix . 'sag_publication_queue';
        $sql_queue = "CREATE TABLE $table_queue (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            article_id bigint(20) UNSIGNED NOT NULL,
            scheduled_for datetime NOT NULL,
            priority int(11) DEFAULT 10,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error_message text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY article_id (article_id),
            KEY scheduled_for (scheduled_for),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_queue);

        // Guardar versión de la base de datos
        update_option('sag_db_version', SAG_VERSION);
    }

    /**
     * Configurar opciones por defecto
     */
    private static function set_default_options() {
        $default_options = [
            'sag_api_key' => '',
            'sag_model_text' => 'gemini-2.5-flash',
            'sag_model_image' => 'gemini-2.5-flash-image',
            'sag_default_language' => 'es',
            'sag_default_author' => 1,
            'sag_default_category' => 1,
            'sag_meta_title_max' => 60,
            'sag_meta_desc_max' => 160,
            'sag_keyword_density' => 1.5,
            'sag_max_internal_links' => 5,
            'sag_max_external_links' => 3,
            'sag_default_status' => 'draft',
            'sag_publish_frequency' => 'daily',
            'sag_preferred_time' => '10:00',
            'sag_rankmath_sync' => true,
        ];

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Crear capacidades personalizadas
     */
    private static function create_capabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('sag_generate_articles');
            $admin->add_cap('sag_analyze_content');
            $admin->add_cap('sag_manage_settings');
        }

        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('sag_generate_articles');
            $editor->add_cap('sag_analyze_content');
        }
    }
}
