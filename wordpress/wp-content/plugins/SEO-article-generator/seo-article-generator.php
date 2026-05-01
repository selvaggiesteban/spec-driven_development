<?php
/**
 * Plugin Name: SEO Article Generator Pro
 * Plugin URI: https://github.com/selvaggiesteban/SEO-article-generator
 * Description: Genera artículos SEO especializados con IA (Gemini), análisis de contenido, enlazado inteligente y programación de publicaciones. Integración con RankMath.
 * Version: 1.0.0
 * Author: Esteban Selvaggi
 * Author URI: https://selvaggiesteban.dev
 * Text Domain: seo-article-generator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SAG_VERSION', '1.0.0');
define('SAG_PLUGIN_FILE', __FILE__);
define('SAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
final class SEO_Article_Generator {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única (Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Core
        require_once SAG_PLUGIN_DIR . 'includes/class-activator.php';
        require_once SAG_PLUGIN_DIR . 'includes/class-deactivator.php';
        require_once SAG_PLUGIN_DIR . 'includes/class-database.php';
        require_once SAG_PLUGIN_DIR . 'includes/class-crypto-helper.php';
        
        // API
        require_once SAG_PLUGIN_DIR . 'includes/api/class-gemini-client.php';
        require_once SAG_PLUGIN_DIR . 'includes/api/class-image-generator.php';
        require_once SAG_PLUGIN_DIR . 'includes/api/class-api-handler.php';
        
        // Generators
        require_once SAG_PLUGIN_DIR . 'includes/generators/class-article-generator.php';
        require_once SAG_PLUGIN_DIR . 'includes/generators/class-seo-optimizer.php';
        require_once SAG_PLUGIN_DIR . 'includes/generators/class-content-formatter.php';
        require_once SAG_PLUGIN_DIR . 'includes/generators/class-rankmath-integration.php';
        
        // Analyzers
        require_once SAG_PLUGIN_DIR . 'includes/analyzers/class-content-analyzer.php';
        require_once SAG_PLUGIN_DIR . 'includes/analyzers/class-keyword-analyzer.php';
        require_once SAG_PLUGIN_DIR . 'includes/analyzers/class-link-analyzer.php';
        
        // Linkers
        require_once SAG_PLUGIN_DIR . 'includes/linkers/class-internal-linker.php';
        require_once SAG_PLUGIN_DIR . 'includes/linkers/class-external-linker.php';
        
        // Schedulers
        require_once SAG_PLUGIN_DIR . 'includes/schedulers/class-post-scheduler.php';
        require_once SAG_PLUGIN_DIR . 'includes/schedulers/class-queue-manager.php';
        
        // Admin
        require_once SAG_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once SAG_PLUGIN_DIR . 'admin/class-settings-page.php';
    }

    /**
     * Configurar localización
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'seo-article-generator',
                false,
                dirname(SAG_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Definir hooks de administración
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }

        $admin_menu = new SAG_Admin_Menu();
        add_action('admin_menu', [$admin_menu, 'register_menu']);
        add_action('admin_enqueue_scripts', [$admin_menu, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_sag_generate_article', [$this, 'ajax_generate_article']);
        add_action('wp_ajax_sag_analyze_content', [$this, 'ajax_analyze_content']);
        add_action('wp_ajax_sag_get_link_suggestions', [$this, 'ajax_get_link_suggestions']);
        add_action('wp_ajax_sag_schedule_post', [$this, 'ajax_schedule_post']);
        add_action('wp_ajax_sag_generate_image', [$this, 'ajax_generate_image']);
        add_action('wp_ajax_sag_test_api', [$this, 'ajax_test_api']);
        add_action('wp_ajax_sag_publish_now', [$this, 'ajax_publish_now']);
        add_action('wp_ajax_sag_unschedule', [$this, 'ajax_unschedule']);
        add_action('wp_ajax_sag_apply_link', [$this, 'ajax_apply_link']);
        add_action('wp_ajax_sag_ignore_link', [$this, 'ajax_ignore_link']);
    }

    /**
     * Definir hooks públicos
     */
    private function define_public_hooks() {
        // Cron para publicación programada
        add_action('sag_process_scheduled_posts', [$this, 'process_scheduled_posts']);
        
        if (!wp_next_scheduled('sag_process_scheduled_posts')) {
            wp_schedule_event(time(), 'hourly', 'sag_process_scheduled_posts');
        }
    }

    /**
     * AJAX: Generar artículo
     */
    public function ajax_generate_article() {
        check_ajax_referer('sag_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'seo-article-generator')]);
        }

        $generator = new SAG_Article_Generator();
        $result = $generator->generate([
            'keyword' => sanitize_text_field($_POST['keyword'] ?? ''),
            'type' => sanitize_text_field($_POST['article_type'] ?? 'guide'),
            'length' => sanitize_text_field($_POST['length'] ?? 'medium'),
            'tone' => sanitize_text_field($_POST['tone'] ?? 'professional'),
            'include_toc' => isset($_POST['include_toc']),
            'include_faq' => isset($_POST['include_faq']),
            'generate_image' => isset($_POST['generate_image']),
            'category' => intval($_POST['category'] ?? 0),
            'author' => intval($_POST['author'] ?? 0),
            'post_status' => sanitize_text_field($_POST['post_status'] ?? 'draft'),
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Analizar contenido
     */
    public function ajax_analyze_content() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'seo-article-generator')]);
        }

        try {
            $analyzer = new SAG_Content_Analyzer();
            $result = $analyzer->analyze_site();

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            wp_send_json_success($result);
        } catch (Exception $e) {
            error_log('SAG: Error en análisis de contenido: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Error al analizar el sitio: %s', 'seo-article-generator'),
                    $e->getMessage()
                )
            ]);
        }
    }

    /**
     * AJAX: Obtener sugerencias de enlaces
     */
    public function ajax_get_link_suggestions() {
        check_ajax_referer('sag_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'seo-article-generator')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $linker = new SAG_Internal_Linker();
        $suggestions = $linker->get_suggestions($post_id);

        wp_send_json_success($suggestions);
    }

    /**
     * AJAX: Programar publicación
     */
    public function ajax_schedule_post() {
        check_ajax_referer('sag_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'seo-article-generator')]);
        }

        $scheduler = new SAG_Post_Scheduler();
        $result = $scheduler->schedule([
            'post_id' => intval($_POST['post_id'] ?? 0),
            'date' => sanitize_text_field($_POST['schedule_date'] ?? ''),
            'time' => sanitize_text_field($_POST['schedule_time'] ?? ''),
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Generar imagen con IA
     */
    public function ajax_generate_image() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('No tienes permisos para subir archivos.', 'seo-article-generator')]);
        }

        $image_gen = new SAG_Image_Generator();
        $result = $image_gen->generate([
            'prompt' => sanitize_text_field($_POST['prompt'] ?? ''),
            'style' => sanitize_text_field($_POST['style'] ?? 'photographic'),
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Probar conexión con API
     */
    public function ajax_test_api() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'seo-article-generator')]);
        }

        $api_handler = new SAG_API_Handler();
        $result = $api_handler->test_connection();

        if ($result['status'] === 'success') {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX: Publicar ahora
     */
    public function ajax_publish_now() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para publicar posts.', 'seo-article-generator')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('ID de post requerido.', 'seo-article-generator')]);
        }

        $scheduler = new SAG_Post_Scheduler();
        $result = $scheduler->publish_now($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Cancelar programación
     */
    public function ajax_unschedule() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para gestionar publicaciones.', 'seo-article-generator')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => __('ID de post requerido.', 'seo-article-generator')]);
        }

        $scheduler = new SAG_Post_Scheduler();
        $result = $scheduler->unschedule($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Aplicar sugerencia de enlace
     */
    public function ajax_apply_link() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para editar posts.', 'seo-article-generator')]);
        }

        $suggestion_id = intval($_POST['suggestion_id'] ?? 0);
        if (!$suggestion_id) {
            wp_send_json_error(['message' => __('ID de sugerencia requerido.', 'seo-article-generator')]);
        }

        $linker = new SAG_Internal_Linker();
        $result = $linker->apply_suggestion($suggestion_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Ignorar sugerencia de enlace
     */
    public function ajax_ignore_link() {
        check_ajax_referer('sag_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para editar posts.', 'seo-article-generator')]);
        }

        $suggestion_id = intval($_POST['suggestion_id'] ?? 0);
        if (!$suggestion_id) {
            wp_send_json_error(['message' => __('ID de sugerencia requerido.', 'seo-article-generator')]);
        }

        $linker = new SAG_Internal_Linker();
        $result = $linker->ignore_suggestion($suggestion_id);

        if ($result !== false) {
            wp_send_json_success(['message' => __('Sugerencia ignorada.', 'seo-article-generator')]);
        } else {
            wp_send_json_error(['message' => __('Error al ignorar sugerencia.', 'seo-article-generator')]);
        }
    }

    /**
     * Procesar posts programados (Cron)
     */
    public function process_scheduled_posts() {
        $queue = new SAG_Queue_Manager();
        $queue->process_pending();
    }
}

/**
 * Activación del plugin
 */
register_activation_hook(__FILE__, function() {
    require_once SAG_PLUGIN_DIR . 'includes/class-activator.php';
    SAG_Activator::activate();
});

/**
 * Desactivación del plugin
 */
register_deactivation_hook(__FILE__, function() {
    require_once SAG_PLUGIN_DIR . 'includes/class-deactivator.php';
    SAG_Deactivator::deactivate();
});

/**
 * Inicializar el plugin
 */
function seo_article_generator() {
    return SEO_Article_Generator::get_instance();
}

// Arrancar el plugin
add_action('plugins_loaded', 'seo_article_generator');
