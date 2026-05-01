<?php
/**
 * Gestor de cola de publicación
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Queue_Manager {

    /**
     * Añadir a la cola
     */
    public function add($article_id, $scheduled_for = null, $priority = 10) {
        if (!$scheduled_for) {
            $scheduler = new SAG_Post_Scheduler();
            $scheduled_for = $scheduler->get_optimal_publish_time();
        }

        return SAG_Database::add_to_queue($article_id, $scheduled_for, $priority);
    }

    /**
     * Procesar items pendientes
     */
    public function process_pending() {
        $items = SAG_Database::get_pending_queue_items();
        $total = count($items);

        error_log(sprintf('[SAG Queue] Iniciando procesamiento de cola. %d item(s) pendiente(s)', $total));

        if ($total === 0) {
            error_log('[SAG Queue] No hay items pendientes para procesar');
            return 0;
        }

        $processed = 0;
        $failed = 0;

        foreach ($items as $item) {
            $result = $this->process_item($item);

            if ($result) {
                $processed++;
            } else {
                $failed++;
            }
        }

        error_log(sprintf('[SAG Queue] Procesamiento completado. Exitosos: %d, Fallidos: %d, Total: %d', $processed, $failed, $total));

        return $processed;
    }

    /**
     * Procesar un item de la cola
     */
    private function process_item($item) {
        error_log(sprintf('[SAG Queue] Procesando item #%d (article_id: %d)', $item->id, $item->article_id));

        $article = SAG_Database::get_article($item->article_id);

        if (!$article || !$article->post_id) {
            $error_msg = 'Artículo no encontrado en la base de datos';
            error_log(sprintf('[SAG Queue] ERROR: Item #%d - %s', $item->id, $error_msg));
            SAG_Database::update_queue_item($item->id, 'failed', $error_msg);
            return false;
        }

        $post = get_post($article->post_id);

        if (!$post) {
            $error_msg = sprintf('Post #%d no encontrado en WordPress', $article->post_id);
            error_log(sprintf('[SAG Queue] ERROR: Item #%d - %s', $item->id, $error_msg));
            SAG_Database::update_queue_item($item->id, 'failed', $error_msg);
            return false;
        }

        // Verificar que el post está en estado correcto para publicar
        if (!in_array($post->post_status, ['draft', 'pending', 'future'])) {
            $error_msg = sprintf('Post #%d tiene estado inválido: %s', $article->post_id, $post->post_status);
            error_log(sprintf('[SAG Queue] WARNING: Item #%d - %s', $item->id, $error_msg));
            SAG_Database::update_queue_item($item->id, 'failed', $error_msg);
            return false;
        }

        error_log(sprintf('[SAG Queue] Publicando post #%d ("%s")', $article->post_id, $post->post_title));

        // Publicar el post
        $scheduler = new SAG_Post_Scheduler();
        $result = $scheduler->publish_now($article->post_id);

        if (is_wp_error($result)) {
            $error_msg = $result->get_error_message();
            error_log(sprintf('[SAG Queue] ERROR: Item #%d - Falló publicación: %s', $item->id, $error_msg));
            SAG_Database::update_queue_item($item->id, 'failed', $error_msg);
            return false;
        }

        SAG_Database::update_queue_item($item->id, 'completed');
        error_log(sprintf('[SAG Queue] SUCCESS: Item #%d completado. Post #%d publicado exitosamente', $item->id, $article->post_id));

        // Enviar notificación si está configurado
        $this->send_notification($article, $post);

        return true;
    }

    /**
     * Enviar notificación de publicación
     */
    private function send_notification($article, $post) {
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(
            __('[%s] Artículo publicado: %s', 'seo-article-generator'),
            get_bloginfo('name'),
            $post->post_title
        );

        $message = sprintf(
            __("El siguiente artículo ha sido publicado automáticamente:\n\nTítulo: %s\nURL: %s\nKeyword: %s\n\n--\nSEO Article Generator", 'seo-article-generator'),
            $post->post_title,
            get_permalink($post->ID),
            $article->keyword_main
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Obtener cola pendiente
     */
    public function get_pending() {
        global $wpdb;
        $table = SAG_Database::get_table('publication_queue');
        $articles_table = SAG_Database::get_table('articles');

        return $wpdb->get_results("
            SELECT 
                q.*,
                a.keyword_main,
                a.post_id,
                p.post_title
            FROM $table q
            JOIN $articles_table a ON q.article_id = a.id
            LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE q.status = 'pending'
            ORDER BY q.priority ASC, q.scheduled_for ASC
        ");
    }

    /**
     * Cambiar prioridad
     */
    public function update_priority($queue_id, $new_priority) {
        global $wpdb;
        
        return $wpdb->update(
            SAG_Database::get_table('publication_queue'),
            ['priority' => $new_priority],
            ['id' => $queue_id],
            ['%d'],
            ['%d']
        );
    }

    /**
     * Eliminar de la cola
     */
    public function remove($queue_id) {
        global $wpdb;
        
        return $wpdb->delete(
            SAG_Database::get_table('publication_queue'),
            ['id' => $queue_id],
            ['%d']
        );
    }

    /**
     * Pausar item de la cola
     */
    public function pause($queue_id) {
        global $wpdb;
        
        return $wpdb->update(
            SAG_Database::get_table('publication_queue'),
            ['status' => 'paused'],
            ['id' => $queue_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Reanudar item pausado
     */
    public function resume($queue_id) {
        global $wpdb;
        
        return $wpdb->update(
            SAG_Database::get_table('publication_queue'),
            ['status' => 'pending'],
            ['id' => $queue_id, 'status' => 'paused'],
            ['%s'],
            ['%d', '%s']
        );
    }

    /**
     * Obtener estadísticas de la cola
     */
    public function get_stats() {
        global $wpdb;
        $table = SAG_Database::get_table('publication_queue');

        return [
            'pending' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
            'paused' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'paused'"),
            'completed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'"),
            'failed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'failed'"),
            'next_scheduled' => $wpdb->get_var("SELECT scheduled_for FROM $table WHERE status = 'pending' ORDER BY scheduled_for ASC LIMIT 1"),
        ];
    }

    /**
     * Limpiar items antiguos completados
     */
    public function cleanup($days = 30) {
        global $wpdb;
        $table = SAG_Database::get_table('publication_queue');

        return $wpdb->query($wpdb->prepare("
            DELETE FROM $table 
            WHERE status IN ('completed', 'failed') 
            AND processed_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
    }

    /**
     * Reordenar cola por fecha
     */
    public function reorder_by_date() {
        $pending = $this->get_pending();
        $priority = 1;

        foreach ($pending as $item) {
            $this->update_priority($item->id, $priority);
            $priority++;
        }

        return count($pending);
    }
}
