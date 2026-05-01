<?php
/**
 * Uninstall SEO Article Generator
 * 
 * Este archivo se ejecuta cuando el plugin es eliminado.
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones
$options = [
    'sag_api_key',
    'sag_model_text',
    'sag_model_image',
    'sag_default_language',
    'sag_default_author',
    'sag_default_category',
    'sag_meta_title_max',
    'sag_meta_desc_max',
    'sag_keyword_density',
    'sag_max_internal_links',
    'sag_max_external_links',
    'sag_default_status',
    'sag_publish_frequency',
    'sag_preferred_time',
    'sag_rankmath_sync',
    'sag_db_version',
    'sag_total_articles_generated',
    'sag_total_images_generated',
    'sag_last_api_request',
];

foreach ($options as $option) {
    delete_option($option);
}

// Eliminar tablas personalizadas
global $wpdb;

$tables = [
    $wpdb->prefix . 'sag_articles',
    $wpdb->prefix . 'sag_content_analysis',
    $wpdb->prefix . 'sag_link_suggestions',
    $wpdb->prefix . 'sag_publication_queue',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Eliminar capacidades personalizadas
$roles = ['administrator', 'editor'];
$caps = ['sag_generate_articles', 'sag_analyze_content', 'sag_manage_settings'];

foreach ($roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Limpiar cron jobs
wp_clear_scheduled_hook('sag_process_scheduled_posts');
