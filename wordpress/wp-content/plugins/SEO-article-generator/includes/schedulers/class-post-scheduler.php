<?php
/**
 * Programador de posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Post_Scheduler {

    /**
     * Programar publicación de post
     */
    public function schedule($params) {
        $post_id = $params['post_id'] ?? 0;
        $date = $params['date'] ?? '';
        $time = $params['time'] ?? '';

        if (!$post_id) {
            return new WP_Error('missing_post_id', __('ID de post requerido.', 'seo-article-generator'));
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'seo-article-generator'));
        }

        // Construir fecha y hora de programación
        if (empty($date)) {
            // Usar fecha sugerida automáticamente
            $scheduled_time = $this->get_optimal_publish_time();
        } else {
            $time = $time ?: get_option('sag_preferred_time', '10:00');
            $scheduled_time = $date . ' ' . $time . ':00';
        }

        // Validar que sea en el futuro
        $scheduled_timestamp = strtotime($scheduled_time);
        if ($scheduled_timestamp <= current_time('timestamp')) {
            return new WP_Error('past_date', __('La fecha de programación debe ser en el futuro.', 'seo-article-generator'));
        }

        // Actualizar post con estado programado
        $result = wp_update_post([
            'ID' => $post_id,
            'post_status' => 'future',
            'post_date' => $scheduled_time,
            'post_date_gmt' => get_gmt_from_date($scheduled_time),
        ], true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Actualizar registro en nuestra base de datos
        $article = $this->get_article_by_post_id($post_id);
        if ($article) {
            SAG_Database::update_article($article->id, [
                'status' => 'scheduled',
            ]);
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'scheduled_for' => $scheduled_time,
            'message' => sprintf(
                __('Artículo programado para %s', 'seo-article-generator'),
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $scheduled_timestamp)
            ),
        ];
    }

    /**
     * Obtener tiempo óptimo de publicación
     */
    public function get_optimal_publish_time() {
        // Obtener hora preferida
        $preferred_time = get_option('sag_preferred_time', '10:00');
        $frequency = get_option('sag_publish_frequency', 'daily');

        // Buscar próximo slot disponible
        $next_slot = $this->get_next_available_slot($frequency);
        
        return $next_slot . ' ' . $preferred_time . ':00';
    }

    /**
     * Obtener próximo slot disponible
     */
    private function get_next_available_slot($frequency) {
        global $wpdb;

        // Obtener posts programados
        $scheduled = $wpdb->get_col("
            SELECT DATE(post_date)
            FROM {$wpdb->posts}
            WHERE post_status = 'future'
            AND post_type = 'post'
            ORDER BY post_date ASC
        ");

        $today = current_time('Y-m-d');
        $candidate = date('Y-m-d', strtotime('+1 day'));

        // Definir intervalo según frecuencia
        $intervals = [
            'daily' => 1,
            'twice_daily' => 0.5,
            'weekly' => 7,
            'biweekly' => 14,
        ];
        $interval = $intervals[$frequency] ?? 1;

        // Buscar fecha sin conflictos
        $max_attempts = 30;
        for ($i = 0; $i < $max_attempts; $i++) {
            if (!in_array($candidate, $scheduled)) {
                return $candidate;
            }
            $candidate = date('Y-m-d', strtotime($candidate . ' +' . ceil($interval) . ' days'));
        }

        return $candidate;
    }

    /**
     * Publicar inmediatamente
     */
    public function publish_now($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'seo-article-generator'));
        }

        $result = wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
        ], true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Actualizar registro en nuestra base de datos
        $article = $this->get_article_by_post_id($post_id);
        if ($article) {
            SAG_Database::update_article($article->id, [
                'status' => 'published',
                'published_at' => current_time('mysql'),
            ]);
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'url' => get_permalink($post_id),
            'message' => __('Artículo publicado exitosamente.', 'seo-article-generator'),
        ];
    }

    /**
     * Cancelar programación
     */
    public function unschedule($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'future') {
            return new WP_Error('not_scheduled', __('El post no está programado.', 'seo-article-generator'));
        }

        $result = wp_update_post([
            'ID' => $post_id,
            'post_status' => 'draft',
        ], true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Actualizar registro
        $article = $this->get_article_by_post_id($post_id);
        if ($article) {
            SAG_Database::update_article($article->id, [
                'status' => 'draft',
            ]);
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'message' => __('Programación cancelada. El artículo es ahora un borrador.', 'seo-article-generator'),
        ];
    }

    /**
     * Reprogramar
     */
    public function reschedule($post_id, $new_date, $new_time = null) {
        return $this->schedule([
            'post_id' => $post_id,
            'date' => $new_date,
            'time' => $new_time,
        ]);
    }

    /**
     * Obtener posts programados
     */
    public function get_scheduled_posts($limit = 20) {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'future',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        $scheduled = [];
        foreach ($posts as $post) {
            $scheduled[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'scheduled_date' => $post->post_date,
                'scheduled_timestamp' => strtotime($post->post_date),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'preview_url' => get_preview_post_link($post->ID),
            ];
        }

        return $scheduled;
    }

    /**
     * Obtener calendario de publicaciones
     */
    public function get_calendar($month = null, $year = null) {
        if (!$month) $month = date('n');
        if (!$year) $year = date('Y');

        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        global $wpdb;

        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_date, post_status
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status IN ('publish', 'future')
            AND post_date >= %s
            AND post_date <= %s
            ORDER BY post_date ASC
        ", $start_date . ' 00:00:00', $end_date . ' 23:59:59'));

        $calendar = [];
        foreach ($posts as $post) {
            $day = date('j', strtotime($post->post_date));
            
            if (!isset($calendar[$day])) {
                $calendar[$day] = [];
            }

            $calendar[$day][] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'time' => date('H:i', strtotime($post->post_date)),
                'status' => $post->post_status,
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'days' => $calendar,
            'total_posts' => count($posts),
        ];
    }

    /**
     * Analizar mejor momento para publicar
     */
    public function analyze_best_times() {
        global $wpdb;

        // Analizar posts publicados en los últimos 6 meses
        $posts = $wpdb->get_results("
            SELECT 
                DAYOFWEEK(post_date) as day_of_week,
                HOUR(post_date) as hour,
                COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DAYOFWEEK(post_date), HOUR(post_date)
            ORDER BY count DESC
            LIMIT 10
        ");

        $days = [
            1 => 'Domingo',
            2 => 'Lunes',
            3 => 'Martes',
            4 => 'Miércoles',
            5 => 'Jueves',
            6 => 'Viernes',
            7 => 'Sábado',
        ];

        $best_times = [];
        foreach ($posts as $row) {
            $best_times[] = [
                'day' => $days[$row->day_of_week],
                'hour' => sprintf('%02d:00', $row->hour),
                'posts_published' => $row->count,
            ];
        }

        return $best_times;
    }

    /**
     * Obtener artículo por post ID
     */
    private function get_article_by_post_id($post_id) {
        global $wpdb;
        $table = SAG_Database::get_table('articles');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d ORDER BY id DESC LIMIT 1",
            $post_id
        ));
    }
}
