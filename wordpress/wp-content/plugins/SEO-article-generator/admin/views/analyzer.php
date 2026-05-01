<?php
/**
 * Vista: Analizador de Contenido
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php _e('Analizador de Contenido', 'seo-article-generator'); ?>
    </h1>

    <div class="sag-analyzer-actions">
        <button type="button" id="sag-analyze-site" class="button button-primary button-hero">
            <span class="dashicons dashicons-search"></span>
            <?php _e('Analizar Todo el Sitio', 'seo-article-generator'); ?>
        </button>
        <p class="description"><?php _e('Analiza todos los posts publicados para detectar oportunidades SEO.', 'seo-article-generator'); ?></p>
    </div>

    <div id="sag-analysis-results" style="display: none;">
        <!-- Resumen -->
        <div class="sag-analysis-summary">
            <div class="sag-summary-card">
                <span class="dashicons dashicons-admin-page"></span>
                <div>
                    <h4 id="analyzed-posts">0</h4>
                    <p><?php _e('Posts Analizados', 'seo-article-generator'); ?></p>
                </div>
            </div>
            <div class="sag-summary-card">
                <span class="dashicons dashicons-tag"></span>
                <div>
                    <h4 id="total-keywords">0</h4>
                    <p><?php _e('Keywords Detectadas', 'seo-article-generator'); ?></p>
                </div>
            </div>
            <div class="sag-summary-card">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <h4 id="orphan-pages">0</h4>
                    <p><?php _e('Páginas Huérfanas', 'seo-article-generator'); ?></p>
                </div>
            </div>
            <div class="sag-summary-card">
                <span class="dashicons dashicons-lightbulb"></span>
                <div>
                    <h4 id="topic-suggestions">0</h4>
                    <p><?php _e('Sugerencias de Temas', 'seo-article-generator'); ?></p>
                </div>
            </div>
        </div>

        <!-- Tabs de resultados -->
        <div class="sag-tabs">
            <button class="sag-tab active" data-tab="keywords"><?php _e('Keywords', 'seo-article-generator'); ?></button>
            <button class="sag-tab" data-tab="gaps"><?php _e('Gaps de Contenido', 'seo-article-generator'); ?></button>
            <button class="sag-tab" data-tab="suggestions"><?php _e('Sugerencias', 'seo-article-generator'); ?></button>
            <button class="sag-tab" data-tab="links"><?php _e('Estructura de Enlaces', 'seo-article-generator'); ?></button>
        </div>

        <!-- Contenido de tabs -->
        <div class="sag-tab-content" id="tab-keywords">
            <h3><?php _e('Mapa de Keywords', 'seo-article-generator'); ?></h3>
            <p class="description"><?php _e('Keywords más utilizadas en tu contenido actual.', 'seo-article-generator'); ?></p>
            <div id="keywords-cloud" class="sag-keywords-cloud"></div>
            <table class="sag-table" id="keywords-table">
                <thead>
                    <tr>
                        <th><?php _e('Keyword', 'seo-article-generator'); ?></th>
                        <th><?php _e('Frecuencia', 'seo-article-generator'); ?></th>
                        <th><?php _e('Acción', 'seo-article-generator'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="sag-tab-content" id="tab-gaps" style="display: none;">
            <h3><?php _e('Gaps de Contenido Detectados', 'seo-article-generator'); ?></h3>
            <p class="description"><?php _e('Tipos de contenido que podrían complementar tu blog.', 'seo-article-generator'); ?></p>
            <ul id="gaps-list" class="sag-gaps-list"></ul>
        </div>

        <div class="sag-tab-content" id="tab-suggestions" style="display: none;">
            <h3><?php _e('Temas Sugeridos para Nuevos Artículos', 'seo-article-generator'); ?></h3>
            <p class="description"><?php _e('Basados en el análisis de tu contenido actual.', 'seo-article-generator'); ?></p>
            <div id="suggestions-list" class="sag-suggestions-grid"></div>
        </div>

        <div class="sag-tab-content" id="tab-links" style="display: none;">
            <h3><?php _e('Estructura de Enlaces Internos', 'seo-article-generator'); ?></h3>
            
            <div class="sag-links-stats">
                <div class="sag-link-stat">
                    <span class="value" id="avg-internal-links">0</span>
                    <span class="label"><?php _e('Media de enlaces por post', 'seo-article-generator'); ?></span>
                </div>
            </div>

            <h4><?php _e('Páginas Huérfanas (sin enlaces entrantes)', 'seo-article-generator'); ?></h4>
            <div id="orphan-list" class="sag-orphan-list"></div>

            <h4><?php _e('Páginas Más Enlazadas', 'seo-article-generator'); ?></h4>
            <table class="sag-table" id="most-linked-table">
                <thead>
                    <tr>
                        <th><?php _e('Título', 'seo-article-generator'); ?></th>
                        <th><?php _e('Enlaces Entrantes', 'seo-article-generator'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Loader -->
    <div id="sag-analyzer-loader" class="sag-loader-container" style="display: none;">
        <div class="sag-loader"></div>
        <p><?php _e('Analizando contenido... Esto puede tardar unos minutos.', 'seo-article-generator'); ?></p>
    </div>
</div>
