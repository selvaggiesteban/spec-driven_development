<?php
/**
 * Vista: Configuración
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar conexión API
$api_handler = new SAG_API_Handler();
$api_status = $api_handler->test_connection();
$models = $api_handler->get_available_models();
$rankmath_active = class_exists('RankMath');

// Opciones
$languages = SAG_Settings_Page::get_language_options();
$frequencies = SAG_Settings_Page::get_frequency_options();
$statuses = SAG_Settings_Page::get_status_options();
$categories = get_categories(['hide_empty' => false]);
$authors = get_users(['role__in' => ['administrator', 'editor', 'author']]);
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-admin-generic"></span>
        <?php _e('Configuración', 'seo-article-generator'); ?>
    </h1>

    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Configuración guardada correctamente.', 'seo-article-generator'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php" class="sag-settings-form">
        <?php settings_fields('sag_settings'); ?>

        <div class="sag-settings-grid">
            <!-- API Settings -->
            <div class="sag-form-card">
                <h3>
                    <span class="dashicons dashicons-rest-api"></span>
                    <?php _e('Configuración de API', 'seo-article-generator'); ?>
                </h3>

                <div class="sag-api-status-box <?php echo $api_status['status'] === 'success' ? 'success' : 'error'; ?>">
                    <span class="dashicons <?php echo $api_status['status'] === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <?php echo esc_html($api_status['message']); ?>
                </div>

                <div class="sag-field">
                    <label for="sag_api_key"><?php _e('API Key de Gemini', 'seo-article-generator'); ?></label>
                    <input type="password" id="sag_api_key" name="sag_api_key" 
                           value="<?php echo esc_attr(get_option('sag_api_key')); ?>" 
                           class="regular-text" placeholder="AI...">
                    <p class="description">
                        <?php _e('Obtén tu API Key desde', 'seo-article-generator'); ?> 
                        <a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>
                    </p>
                </div>

                <div class="sag-field">
                    <label for="sag_model_text"><?php _e('Modelo para Texto', 'seo-article-generator'); ?></label>
                    <select id="sag_model_text" name="sag_model_text">
                        <?php foreach ($models['text'] as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>" <?php selected(get_option('sag_model_text', 'gemini-2.5-flash'), $id); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sag-field">
                    <label for="sag_model_image"><?php _e('Modelo para Imágenes', 'seo-article-generator'); ?></label>
                    <select id="sag_model_image" name="sag_model_image">
                        <?php foreach ($models['image'] as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>" <?php selected(get_option('sag_model_image', 'gemini-2.5-flash-image'), $id); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" id="test-api-btn" class="button">
                    <?php _e('Probar Conexión', 'seo-article-generator'); ?>
                </button>
            </div>

            <!-- General Settings -->
            <div class="sag-form-card">
                <h3>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Ajustes Generales', 'seo-article-generator'); ?>
                </h3>

                <div class="sag-field">
                    <label for="sag_default_language"><?php _e('Idioma por Defecto', 'seo-article-generator'); ?></label>
                    <select id="sag_default_language" name="sag_default_language">
                        <?php foreach ($languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected(get_option('sag_default_language', 'es'), $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sag-field">
                    <label for="sag_default_author"><?php _e('Autor por Defecto', 'seo-article-generator'); ?></label>
                    <select id="sag_default_author" name="sag_default_author">
                        <?php foreach ($authors as $author): ?>
                            <option value="<?php echo esc_attr($author->ID); ?>" <?php selected(get_option('sag_default_author'), $author->ID); ?>>
                                <?php echo esc_html($author->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sag-field">
                    <label for="sag_default_category"><?php _e('Categoría por Defecto', 'seo-article-generator'); ?></label>
                    <select id="sag_default_category" name="sag_default_category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(get_option('sag_default_category'), $cat->term_id); ?>>
                                <?php echo esc_html($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sag-field">
                    <label for="sag_default_status"><?php _e('Estado por Defecto', 'seo-article-generator'); ?></label>
                    <select id="sag_default_status" name="sag_default_status">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option('sag_default_status', 'draft'), $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="sag-form-card">
                <h3>
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Ajustes SEO', 'seo-article-generator'); ?>
                </h3>

                <div class="sag-field">
                    <label for="sag_meta_title_max"><?php _e('Longitud Máxima Meta Título', 'seo-article-generator'); ?></label>
                    <input type="number" id="sag_meta_title_max" name="sag_meta_title_max" 
                           value="<?php echo esc_attr(get_option('sag_meta_title_max', 60)); ?>" 
                           min="30" max="70">
                </div>

                <div class="sag-field">
                    <label for="sag_meta_desc_max"><?php _e('Longitud Máxima Meta Descripción', 'seo-article-generator'); ?></label>
                    <input type="number" id="sag_meta_desc_max" name="sag_meta_desc_max" 
                           value="<?php echo esc_attr(get_option('sag_meta_desc_max', 160)); ?>" 
                           min="100" max="180">
                </div>

                <div class="sag-field">
                    <label for="sag_keyword_density"><?php _e('Densidad de Keyword Objetivo (%)', 'seo-article-generator'); ?></label>
                    <input type="number" id="sag_keyword_density" name="sag_keyword_density" 
                           value="<?php echo esc_attr(get_option('sag_keyword_density', 1.5)); ?>" 
                           min="0.5" max="3" step="0.1">
                </div>

                <div class="sag-field">
                    <label for="sag_max_internal_links"><?php _e('Máx. Enlaces Internos por Artículo', 'seo-article-generator'); ?></label>
                    <input type="number" id="sag_max_internal_links" name="sag_max_internal_links" 
                           value="<?php echo esc_attr(get_option('sag_max_internal_links', 5)); ?>" 
                           min="1" max="15">
                </div>

                <div class="sag-field">
                    <label for="sag_max_external_links"><?php _e('Máx. Enlaces Externos por Artículo', 'seo-article-generator'); ?></label>
                    <input type="number" id="sag_max_external_links" name="sag_max_external_links" 
                           value="<?php echo esc_attr(get_option('sag_max_external_links', 3)); ?>" 
                           min="0" max="10">
                </div>
            </div>

            <!-- Publishing Settings -->
            <div class="sag-form-card">
                <h3>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Ajustes de Publicación', 'seo-article-generator'); ?>
                </h3>

                <div class="sag-field">
                    <label for="sag_publish_frequency"><?php _e('Frecuencia de Publicación', 'seo-article-generator'); ?></label>
                    <select id="sag_publish_frequency" name="sag_publish_frequency">
                        <?php foreach ($frequencies as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option('sag_publish_frequency', 'daily'), $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sag-field">
                    <label for="sag_preferred_time"><?php _e('Hora Preferida de Publicación', 'seo-article-generator'); ?></label>
                    <input type="time" id="sag_preferred_time" name="sag_preferred_time" 
                           value="<?php echo esc_attr(get_option('sag_preferred_time', '10:00')); ?>">
                </div>
            </div>

            <!-- Integration Settings -->
            <div class="sag-form-card">
                <h3>
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Integraciones', 'seo-article-generator'); ?>
                </h3>

                <div class="sag-field">
                    <label class="sag-checkbox">
                        <input type="checkbox" name="sag_rankmath_sync" value="1" 
                               <?php checked(get_option('sag_rankmath_sync', true)); ?>
                               <?php disabled(!$rankmath_active); ?>>
                        <span><?php _e('Sincronizar con RankMath SEO', 'seo-article-generator'); ?></span>
                    </label>
                    <?php if (!$rankmath_active): ?>
                        <p class="description sag-warning">
                            <?php _e('RankMath no está instalado o activado.', 'seo-article-generator'); ?>
                        </p>
                    <?php else: ?>
                        <p class="description">
                            <?php _e('Sincroniza automáticamente focus keywords, meta títulos y descripciones con RankMath.', 'seo-article-generator'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php submit_button(__('Guardar Cambios', 'seo-article-generator')); ?>
    </form>
</div>
