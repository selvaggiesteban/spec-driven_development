<?php
/**
 * Desactivador del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Deactivator {

    /**
     * Ejecutar al desactivar el plugin
     */
    public static function deactivate() {
        // Limpiar cron jobs
        wp_clear_scheduled_hook('sag_process_scheduled_posts');
        
        // Limpiar cache de rewrite rules
        flush_rewrite_rules();
    }
}
