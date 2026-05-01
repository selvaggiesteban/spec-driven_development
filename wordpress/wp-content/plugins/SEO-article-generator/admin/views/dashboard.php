<?php
/**
 * Vista: Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadísticas
$stats = SAG_Database::get_dashboard_stats();
$api_handler = new SAG_API_Handler();
$api_status = $api_handler->test_connection();
$usage = $api_handler->get_usage_stats();

// Artículos recientes
$recent_articles = SAG_Database::get_recent_articles(5);

// Posts programados
$scheduler = new SAG_Post_Scheduler();
$scheduled = $scheduler->get_scheduled_posts(5);
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-edit-page"></span>
        <?php _e('SEO Article Generator Pro', 'seo-article-generator'); ?>
    </h1>

    <!-- Estado de la API -->
    <div class="sag-api-status <?php echo $api_status['status'] === 'success' ? 'sag-status-ok' : 'sag-status-error'; ?>">
        <span class="dashicons <?php echo $api_status['status'] === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
        <?php echo esc_html($api_status['message']); ?>
        <?php if ($api_status['status'] !== 'success'): ?>
            <a href="<?php echo admin_url('admin.php?page=sag-settings'); ?>"><?php _e('Configurar API', 'seo-article-generator'); ?></a>
        <?php endif; ?>
    </div>

    <!-- Estadísticas principales -->
    <div class="sag-stats-grid">
        <div class="sag-stat-card">
            <div class="sag-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <div class="sag-stat-content">
                <h3><?php echo esc_html($stats['total_generated']); ?></h3>
                <p><?php _e('Artículos Generados', 'seo-article-generator'); ?></p>
            </div>
        </div>

        <div class="sag-stat-card">
            <div class="sag-stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="sag-stat-content">
                <h3><?php echo esc_html($stats['published']); ?></h3>
                <p><?php _e('Publicados', 'seo-article-generator'); ?></p>
            </div>
        </div>

        <div class="sag-stat-card">
            <div class="sag-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-edit"></span>
            </div>
            <div class="sag-stat-content">
                <h3><?php echo esc_html($stats['drafts']); ?></h3>
                <p><?php _e('Borradores', 'seo-article-generator'); ?></p>
            </div>
        </div>

        <div class="sag-stat-card">
            <div class="sag-stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="sag-stat-content">
                <h3><?php echo esc_html($stats['scheduled']); ?></h3>
                <p><?php _e('Programados', 'seo-article-generator'); ?></p>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="sag-quick-actions">
        <h2><?php _e('Acciones Rápidas', 'seo-article-generator'); ?></h2>
        <div class="sag-actions-grid">
            <a href="<?php echo admin_url('admin.php?page=sag-generator'); ?>" class="sag-action-btn sag-btn-primary">
                <span class="dashicons dashicons-welcome-write-blog"></span>
                <?php _e('Generar Artículo', 'seo-article-generator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=sag-analyzer'); ?>" class="sag-action-btn sag-btn-secondary">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Analizar Contenido', 'seo-article-generator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=sag-linker'); ?>" class="sag-action-btn sag-btn-secondary">
                <span class="dashicons dashicons-admin-links"></span>
                <?php _e('Gestionar Enlaces', 'seo-article-generator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=sag-scheduler'); ?>" class="sag-action-btn sag-btn-secondary">
                <span class="dashicons dashicons-calendar"></span>
                <?php _e('Programación', 'seo-article-generator'); ?>
            </a>
        </div>
    </div>

    <div class="sag-dashboard-columns">
        <!-- Artículos recientes -->
        <div class="sag-dashboard-card">
            <h3><?php _e('Artículos Recientes', 'seo-article-generator'); ?></h3>
            <?php if (!empty($recent_articles)): ?>
                <table class="sag-table">
                    <thead>
                        <tr>
                            <th><?php _e('Keyword', 'seo-article-generator'); ?></th>
                            <th><?php _e('Tipo', 'seo-article-generator'); ?></th>
                            <th><?php _e('Palabras', 'seo-article-generator'); ?></th>
                            <th><?php _e('SEO', 'seo-article-generator'); ?></th>
                            <th><?php _e('Estado', 'seo-article-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_articles as $article): ?>
                            <tr>
                                <td>
                                    <?php if ($article->post_id): ?>
                                        <a href="<?php echo get_edit_post_link($article->post_id); ?>">
                                            <?php echo esc_html($article->keyword_main); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($article->keyword_main); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($article->article_type)); ?></td>
                                <td><?php echo esc_html(number_format_i18n($article->word_count)); ?></td>
                                <td>
                                    <span class="sag-score sag-score-<?php echo $article->seo_score >= 70 ? 'good' : ($article->seo_score >= 50 ? 'ok' : 'bad'); ?>">
                                        <?php echo esc_html($article->seo_score); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="sag-status sag-status-<?php echo esc_attr($article->status); ?>">
                                        <?php echo esc_html(ucfirst($article->status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sag-empty"><?php _e('No hay artículos generados aún.', 'seo-article-generator'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Próximas publicaciones -->
        <div class="sag-dashboard-card">
            <h3><?php _e('Próximas Publicaciones', 'seo-article-generator'); ?></h3>
            <?php if (!empty($scheduled)): ?>
                <ul class="sag-scheduled-list">
                    <?php foreach ($scheduled as $post): ?>
                        <li>
                            <span class="sag-scheduled-date">
                                <?php echo date_i18n('d M, H:i', $post['scheduled_timestamp']); ?>
                            </span>
                            <a href="<?php echo esc_url($post['edit_url']); ?>">
                                <?php echo esc_html($post['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="sag-empty"><?php _e('No hay publicaciones programadas.', 'seo-article-generator'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Uso de la API -->
    <div class="sag-dashboard-card sag-usage-card">
        <h3><?php _e('Uso de la API', 'seo-article-generator'); ?></h3>
        <div class="sag-usage-stats">
            <div class="sag-usage-item">
                <span class="sag-usage-value"><?php echo esc_html($usage['articles_generated']); ?></span>
                <span class="sag-usage-label"><?php _e('Artículos', 'seo-article-generator'); ?></span>
            </div>
            <div class="sag-usage-item">
                <span class="sag-usage-value"><?php echo esc_html($usage['images_generated']); ?></span>
                <span class="sag-usage-label"><?php _e('Imágenes', 'seo-article-generator'); ?></span>
            </div>
            <div class="sag-usage-item">
                <span class="sag-usage-value"><?php echo $usage['last_request'] ? date_i18n('d/m/Y H:i', strtotime($usage['last_request'])) : '--'; ?></span>
                <span class="sag-usage-label"><?php _e('Última petición', 'seo-article-generator'); ?></span>
            </div>
        </div>
    </div>
</div>
