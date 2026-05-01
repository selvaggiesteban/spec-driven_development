<?php
/**
 * Vista: Programación
 */

if (!defined('ABSPATH')) {
    exit;
}

$scheduler = new SAG_Post_Scheduler();
$queue_manager = new SAG_Queue_Manager();
$scheduled_posts = $scheduler->get_scheduled_posts(20);
$queue_stats = $queue_manager->get_stats();
$calendar = $scheduler->get_calendar();
?>

<div class="wrap sag-wrap">
    <h1 class="sag-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php _e('Programación de Publicaciones', 'seo-article-generator'); ?>
    </h1>

    <!-- Estadísticas de cola -->
    <div class="sag-queue-stats">
        <div class="sag-stat-mini">
            <span class="value"><?php echo esc_html($queue_stats['pending']); ?></span>
            <span class="label"><?php _e('Pendientes', 'seo-article-generator'); ?></span>
        </div>
        <div class="sag-stat-mini">
            <span class="value"><?php echo esc_html($queue_stats['completed']); ?></span>
            <span class="label"><?php _e('Completados', 'seo-article-generator'); ?></span>
        </div>
        <div class="sag-stat-mini">
            <span class="value"><?php echo $queue_stats['next_scheduled'] ? date_i18n('d M H:i', strtotime($queue_stats['next_scheduled'])) : '--'; ?></span>
            <span class="label"><?php _e('Próximo', 'seo-article-generator'); ?></span>
        </div>
    </div>

    <div class="sag-scheduler-tabs">
        <button class="sag-tab active" data-tab="calendar"><?php _e('Calendario', 'seo-article-generator'); ?></button>
        <button class="sag-tab" data-tab="list"><?php _e('Lista', 'seo-article-generator'); ?></button>
        <button class="sag-tab" data-tab="queue"><?php _e('Cola', 'seo-article-generator'); ?></button>
    </div>

    <!-- Tab Calendario -->
    <div class="sag-tab-content" id="tab-calendar">
        <div class="sag-calendar-header">
            <button type="button" class="sag-calendar-nav" data-direction="prev">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <h3 class="sag-calendar-month">
                <?php echo date_i18n('F Y', mktime(0, 0, 0, $calendar['month'], 1, $calendar['year'])); ?>
            </h3>
            <button type="button" class="sag-calendar-nav" data-direction="next">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>

        <div class="sag-calendar">
            <div class="sag-calendar-weekdays">
                <div><?php _e('Lun', 'seo-article-generator'); ?></div>
                <div><?php _e('Mar', 'seo-article-generator'); ?></div>
                <div><?php _e('Mié', 'seo-article-generator'); ?></div>
                <div><?php _e('Jue', 'seo-article-generator'); ?></div>
                <div><?php _e('Vie', 'seo-article-generator'); ?></div>
                <div><?php _e('Sáb', 'seo-article-generator'); ?></div>
                <div><?php _e('Dom', 'seo-article-generator'); ?></div>
            </div>
            <div class="sag-calendar-days" id="calendar-days">
                <?php
                $first_day = date('N', mktime(0, 0, 0, $calendar['month'], 1, $calendar['year']));
                $days_in_month = date('t', mktime(0, 0, 0, $calendar['month'], 1, $calendar['year']));
                $today = date('j');
                
                // Días vacíos del inicio
                for ($i = 1; $i < $first_day; $i++) {
                    echo '<div class="sag-calendar-day sag-day-empty"></div>';
                }
                
                // Días del mes
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $is_today = ($day == $today && $calendar['month'] == date('n') && $calendar['year'] == date('Y'));
                    $has_posts = isset($calendar['days'][$day]);
                    $class = 'sag-calendar-day';
                    if ($is_today) $class .= ' sag-day-today';
                    if ($has_posts) $class .= ' sag-day-has-posts';
                    
                    echo '<div class="' . $class . '" data-day="' . $day . '">';
                    echo '<span class="day-number">' . $day . '</span>';
                    
                    if ($has_posts) {
                        echo '<div class="day-posts">';
                        foreach ($calendar['days'][$day] as $post) {
                            $status_class = $post['status'] === 'publish' ? 'published' : 'scheduled';
                            echo '<div class="day-post ' . $status_class . '" title="' . esc_attr($post['title']) . '">';
                            echo '<span class="post-time">' . esc_html($post['time']) . '</span>';
                            echo '<span class="post-title">' . esc_html(wp_trim_words($post['title'], 3)) . '</span>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Tab Lista -->
    <div class="sag-tab-content" id="tab-list" style="display: none;">
        <h3><?php _e('Posts Programados', 'seo-article-generator'); ?></h3>
        
        <?php if (!empty($scheduled_posts)): ?>
            <table class="sag-table">
                <thead>
                    <tr>
                        <th><?php _e('Título', 'seo-article-generator'); ?></th>
                        <th><?php _e('Fecha Programada', 'seo-article-generator'); ?></th>
                        <th><?php _e('Acciones', 'seo-article-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scheduled_posts as $post): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($post['edit_url']); ?>">
                                    <?php echo esc_html($post['title']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo date_i18n('d M Y, H:i', $post['scheduled_timestamp']); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($post['preview_url']); ?>" class="button button-small" target="_blank">
                                    <?php _e('Preview', 'seo-article-generator'); ?>
                                </a>
                                <button type="button" class="button button-small sag-publish-now" data-post-id="<?php echo $post['id']; ?>">
                                    <?php _e('Publicar Ahora', 'seo-article-generator'); ?>
                                </button>
                                <button type="button" class="button button-small sag-unschedule" data-post-id="<?php echo $post['id']; ?>">
                                    <?php _e('Cancelar', 'seo-article-generator'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="sag-empty"><?php _e('No hay posts programados.', 'seo-article-generator'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Tab Cola -->
    <div class="sag-tab-content" id="tab-queue" style="display: none;">
        <h3><?php _e('Cola de Publicación', 'seo-article-generator'); ?></h3>
        <p class="description"><?php _e('Artículos generados esperando ser publicados.', 'seo-article-generator'); ?></p>
        
        <div id="queue-list">
            <!-- Se carga via AJAX -->
        </div>
    </div>
</div>
