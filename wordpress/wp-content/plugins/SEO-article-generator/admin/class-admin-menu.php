<?php
/**
 * Menú de administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Admin_Menu {

    /**
     * Registrar menú
     */
    public function register_menu() {
        // Menú principal
        add_menu_page(
            __('SEO Article Generator', 'seo-article-generator'),
            __('SEO Generator', 'seo-article-generator'),
            'edit_posts',
            'seo-article-generator',
            [$this, 'render_dashboard'],
            'dashicons-edit-page',
            30
        );

        // Submenús
        add_submenu_page(
            'seo-article-generator',
            __('Dashboard', 'seo-article-generator'),
            __('Dashboard', 'seo-article-generator'),
            'edit_posts',
            'seo-article-generator',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'seo-article-generator',
            __('Generar Artículo', 'seo-article-generator'),
            __('Generar Artículo', 'seo-article-generator'),
            'edit_posts',
            'sag-generator',
            [$this, 'render_generator']
        );

        add_submenu_page(
            'seo-article-generator',
            __('Analizador', 'seo-article-generator'),
            __('Analizador', 'seo-article-generator'),
            'edit_posts',
            'sag-analyzer',
            [$this, 'render_analyzer']
        );

        add_submenu_page(
            'seo-article-generator',
            __('Enlaces', 'seo-article-generator'),
            __('Enlaces', 'seo-article-generator'),
            'edit_posts',
            'sag-linker',
            [$this, 'render_linker']
        );

        add_submenu_page(
            'seo-article-generator',
            __('Programación', 'seo-article-generator'),
            __('Programación', 'seo-article-generator'),
            'edit_posts',
            'sag-scheduler',
            [$this, 'render_scheduler']
        );

        add_submenu_page(
            'seo-article-generator',
            __('Configuración', 'seo-article-generator'),
            __('Configuración', 'seo-article-generator'),
            'manage_options',
            'sag-settings',
            [$this, 'render_settings']
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        // Solo en páginas del plugin
        if (strpos($hook, 'seo-article-generator') === false && strpos($hook, 'sag-') === false) {
            return;
        }

        wp_enqueue_style(
            'sag-admin-styles',
            SAG_PLUGIN_URL . 'admin/css/admin-styles.css',
            [],
            SAG_VERSION
        );

        wp_enqueue_script(
            'sag-admin-scripts',
            SAG_PLUGIN_URL . 'admin/js/admin-scripts.js',
            ['jquery'],
            SAG_VERSION,
            true
        );

        wp_localize_script('sag-admin-scripts', 'sagAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sag_nonce'),
            'strings' => [
                'generating' => __('Generando artículo...', 'seo-article-generator'),
                'analyzing' => __('Analizando contenido...', 'seo-article-generator'),
                'success' => __('¡Operación completada!', 'seo-article-generator'),
                'error' => __('Ha ocurrido un error.', 'seo-article-generator'),
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar?', 'seo-article-generator'),
            ],
        ]);
    }

    /**
     * Renderizar Dashboard
     */
    public function render_dashboard() {
        include SAG_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Renderizar Generador
     */
    public function render_generator() {
        include SAG_PLUGIN_DIR . 'admin/views/generator.php';
    }

    /**
     * Renderizar Analizador
     */
    public function render_analyzer() {
        include SAG_PLUGIN_DIR . 'admin/views/analyzer.php';
    }

    /**
     * Renderizar Linker
     */
    public function render_linker() {
        include SAG_PLUGIN_DIR . 'admin/views/linker.php';
    }

    /**
     * Renderizar Scheduler
     */
    public function render_scheduler() {
        include SAG_PLUGIN_DIR . 'admin/views/scheduler.php';
    }

    /**
     * Renderizar Settings
     */
    public function render_settings() {
        include SAG_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
