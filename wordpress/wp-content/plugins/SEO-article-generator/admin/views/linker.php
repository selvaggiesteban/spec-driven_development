<?php
/**
 * Vista: Gestor de Enlaces
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener posts para selector
$posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 100,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-admin-links"></span>
        <?php _e('Gestor de Enlaces', 'seo-article-generator'); ?>
    </h1>

    <div class="sag-linker-tabs">
        <button class="sag-tab active" data-tab="internal"><?php _e('Enlaces Internos', 'seo-article-generator'); ?></button>
        <button class="sag-tab" data-tab="suggestions"><?php _e('Sugerencias Pendientes', 'seo-article-generator'); ?></button>
    </div>

    <!-- Tab Enlaces Internos -->
    <div class="sag-tab-content" id="tab-internal">
        <div class="sag-form-card">
            <h3><?php _e('Analizar Post para Sugerencias de Enlaces', 'seo-article-generator'); ?></h3>
            
            <div class="sag-field">
                <label for="post-select"><?php _e('Selecciona un Post', 'seo-article-generator'); ?></label>
                <select id="post-select" class="sag-select-large">
                    <option value=""><?php _e('-- Seleccionar Post --', 'seo-article-generator'); ?></option>
                    <?php foreach ($posts as $post): ?>
                        <option value="<?php echo esc_attr($post->ID); ?>">
                            <?php echo esc_html($post->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" id="analyze-links-btn" class="button button-primary" disabled>
                <span class="dashicons dashicons-search"></span>
                <?php _e('Obtener Sugerencias', 'seo-article-generator'); ?>
            </button>
        </div>

        <!-- Resultados de sugerencias -->
        <div id="link-suggestions-results" style="display: none;">
            <h3><?php _e('Sugerencias de Enlaces Internos', 'seo-article-generator'); ?></h3>
            
            <div class="sag-suggestions-actions">
                <button type="button" id="apply-all-links" class="button button-primary">
                    <?php _e('Aplicar Todos', 'seo-article-generator'); ?>
                </button>
            </div>

            <table class="sag-table" id="suggestions-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-suggestions"></th>
                        <th><?php _e('Anchor Text', 'seo-article-generator'); ?></th>
                        <th><?php _e('Enlazar a', 'seo-article-generator'); ?></th>
                        <th><?php _e('Contexto', 'seo-article-generator'); ?></th>
                        <th><?php _e('Acciones', 'seo-article-generator'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Sugerencias Pendientes -->
    <div class="sag-tab-content" id="tab-suggestions" style="display: none;">
        <h3><?php _e('Todas las Sugerencias Pendientes', 'seo-article-generator'); ?></h3>
        <p class="description"><?php _e('Sugerencias de enlaces que aún no han sido aplicadas.', 'seo-article-generator'); ?></p>
        
        <div id="pending-suggestions-list">
            <div class="sag-loader-container">
                <div class="sag-loader"></div>
                <p><?php _e('Cargando sugerencias...', 'seo-article-generator'); ?></p>
            </div>
        </div>
    </div>
</div>
