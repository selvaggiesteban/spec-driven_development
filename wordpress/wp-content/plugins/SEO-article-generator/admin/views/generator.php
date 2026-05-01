<?php
/**
 * Vista: Generador de Artículos
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = get_categories(['hide_empty' => false]);
$authors = get_users(['role__in' => ['administrator', 'editor', 'author']]);
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-welcome-write-blog"></span>
        <?php _e('Generar Artículo SEO', 'seo-article-generator'); ?>
    </h1>

    <form id="sag-generator-form" class="sag-form">
        <?php wp_nonce_field('sag_nonce', 'sag_nonce'); ?>

        <div class="sag-form-grid">
            <!-- Columna principal -->
            <div class="sag-form-main">
                <div class="sag-form-card">
                    <h3><?php _e('Información Principal', 'seo-article-generator'); ?></h3>
                    
                    <div class="sag-field">
                        <label for="keyword"><?php _e('Keyword Principal', 'seo-article-generator'); ?> <span class="required">*</span></label>
                        <input type="text" id="keyword" name="keyword" placeholder="<?php esc_attr_e('Ej: Cómo hacer SEO en 2024', 'seo-article-generator'); ?>" required>
                        <p class="description"><?php _e('La keyword principal sobre la que versará el artículo.', 'seo-article-generator'); ?></p>
                    </div>

                    <div class="sag-field">
                        <label><?php _e('Tipo de Artículo', 'seo-article-generator'); ?></label>
                        <div class="sag-radio-grid">
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="guide" checked>
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-book"></span>
                                    <span><?php _e('Guía Completa', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="tutorial">
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-welcome-learn-more"></span>
                                    <span><?php _e('Tutorial', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="listicle">
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <span><?php _e('Lista', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="comparison">
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-image-flip-horizontal"></span>
                                    <span><?php _e('Comparativa', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="case_study">
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <span><?php _e('Caso de Estudio', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                            <label class="sag-radio-card">
                                <input type="radio" name="article_type" value="news">
                                <span class="sag-radio-content">
                                    <span class="dashicons dashicons-megaphone"></span>
                                    <span><?php _e('Noticia', 'seo-article-generator'); ?></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="sag-field">
                        <label><?php _e('Longitud del Artículo', 'seo-article-generator'); ?></label>
                        <div class="sag-radio-inline">
                            <label>
                                <input type="radio" name="length" value="short">
                                <?php _e('Corto (500-800)', 'seo-article-generator'); ?>
                            </label>
                            <label>
                                <input type="radio" name="length" value="medium" checked>
                                <?php _e('Medio (1000-1500)', 'seo-article-generator'); ?>
                            </label>
                            <label>
                                <input type="radio" name="length" value="long">
                                <?php _e('Largo (2000-3000)', 'seo-article-generator'); ?>
                            </label>
                            <label>
                                <input type="radio" name="length" value="epic">
                                <?php _e('Épico (3000+)', 'seo-article-generator'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="sag-field">
                        <label><?php _e('Tono de Escritura', 'seo-article-generator'); ?></label>
                        <select name="tone">
                            <option value="professional"><?php _e('Profesional', 'seo-article-generator'); ?></option>
                            <option value="conversational"><?php _e('Conversacional', 'seo-article-generator'); ?></option>
                            <option value="technical"><?php _e('Técnico', 'seo-article-generator'); ?></option>
                            <option value="persuasive"><?php _e('Persuasivo', 'seo-article-generator'); ?></option>
                            <option value="educational"><?php _e('Educativo', 'seo-article-generator'); ?></option>
                            <option value="informal"><?php _e('Informal', 'seo-article-generator'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="sag-form-card">
                    <h3><?php _e('Opciones Avanzadas', 'seo-article-generator'); ?></h3>
                    
                    <div class="sag-checkbox-group">
                        <label class="sag-checkbox">
                            <input type="checkbox" name="include_toc" value="1" checked>
                            <span><?php _e('Incluir Tabla de Contenidos', 'seo-article-generator'); ?></span>
                        </label>
                        <label class="sag-checkbox">
                            <input type="checkbox" name="include_faq" value="1" checked>
                            <span><?php _e('Incluir Sección FAQ (Schema)', 'seo-article-generator'); ?></span>
                        </label>
                        <label class="sag-checkbox">
                            <input type="checkbox" name="generate_image" value="1">
                            <span><?php _e('Generar Imagen Destacada con IA', 'seo-article-generator'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sag-form-sidebar">
                <div class="sag-form-card">
                    <h3><?php _e('Publicación', 'seo-article-generator'); ?></h3>
                    
                    <div class="sag-field">
                        <label for="category"><?php _e('Categoría', 'seo-article-generator'); ?></label>
                        <select id="category" name="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>" 
                                    <?php selected($cat->term_id, get_option('sag_default_category')); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sag-field">
                        <label for="author"><?php _e('Autor', 'seo-article-generator'); ?></label>
                        <select id="author" name="author">
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo esc_attr($author->ID); ?>"
                                    <?php selected($author->ID, get_option('sag_default_author')); ?>>
                                    <?php echo esc_html($author->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sag-field">
                        <label><?php _e('Publicar como', 'seo-article-generator'); ?></label>
                        <select name="post_status">
                            <option value="draft"><?php _e('Borrador', 'seo-article-generator'); ?></option>
                            <option value="publish"><?php _e('Publicar inmediatamente', 'seo-article-generator'); ?></option>
                            <option value="schedule"><?php _e('Programar', 'seo-article-generator'); ?></option>
                        </select>
                    </div>

                    <div class="sag-field sag-schedule-fields" style="display: none;">
                        <label><?php _e('Fecha de Publicación', 'seo-article-generator'); ?></label>
                        <input type="date" name="schedule_date" min="<?php echo date('Y-m-d'); ?>">
                        <input type="time" name="schedule_time" value="10:00">
                    </div>
                </div>

                <div class="sag-form-card sag-submit-card">
                    <button type="submit" class="button button-primary button-hero sag-generate-btn">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Generar Artículo', 'seo-article-generator'); ?>
                    </button>
                    <p class="sag-submit-note"><?php _e('La generación puede tardar 30-60 segundos.', 'seo-article-generator'); ?></p>
                </div>
            </div>
        </div>
    </form>

    <!-- Modal de progreso -->
    <div id="sag-progress-modal" class="sag-modal" style="display: none;">
        <div class="sag-modal-content">
            <div class="sag-loader"></div>
            <h3><?php _e('Generando artículo...', 'seo-article-generator'); ?></h3>
            <p id="sag-progress-message"><?php _e('Conectando con Gemini API...', 'seo-article-generator'); ?></p>
        </div>
    </div>

    <!-- Modal de resultado -->
    <div id="sag-result-modal" class="sag-modal" style="display: none;">
        <div class="sag-modal-content sag-result-content">
            <span class="sag-modal-close">&times;</span>
            <div class="sag-result-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <h3><?php _e('¡Artículo generado exitosamente!', 'seo-article-generator'); ?></h3>
            </div>
            <div class="sag-result-details">
                <div class="sag-result-stat">
                    <span class="label"><?php _e('Palabras', 'seo-article-generator'); ?></span>
                    <span class="value" id="result-words">0</span>
                </div>
                <div class="sag-result-stat">
                    <span class="label"><?php _e('Score SEO', 'seo-article-generator'); ?></span>
                    <span class="value" id="result-seo">0%</span>
                </div>
            </div>
            <div class="sag-result-actions">
                <a href="#" id="result-edit-link" class="button button-primary"><?php _e('Editar Artículo', 'seo-article-generator'); ?></a>
                <a href="#" id="result-preview-link" class="button" target="_blank"><?php _e('Vista Previa', 'seo-article-generator'); ?></a>
                <button type="button" class="button sag-new-article"><?php _e('Generar Otro', 'seo-article-generator'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Mostrar/ocultar campos de programación
    $('select[name="post_status"]').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('.sag-schedule-fields').slideDown();
        } else {
            $('.sag-schedule-fields').slideUp();
        }
    });
});
</script>
