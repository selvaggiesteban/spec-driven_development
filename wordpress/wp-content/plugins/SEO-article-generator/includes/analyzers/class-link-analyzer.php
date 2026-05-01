<?php
/**
 * Analizador de enlaces
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Link_Analyzer {

    /**
     * URL del sitio
     */
    private $site_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->site_url = get_site_url();
    }

    /**
     * Analizar enlaces de un post
     */
    public function analyze_post_links($post_id, $content = null) {
        if ($content === null) {
            $post = get_post($post_id);
            $content = $post ? $post->post_content : '';
        }

        // Extraer todos los enlaces
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER);

        $internal_links = [];
        $external_links = [];
        $broken_links = [];

        foreach ($matches as $match) {
            $url = $match[1];
            $anchor = wp_strip_all_tags($match[2]);

            // Ignorar anclas y mailto
            if (strpos($url, '#') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
                continue;
            }

            $link_data = [
                'url' => $url,
                'anchor' => $anchor,
            ];

            // Determinar si es interno o externo
            if ($this->is_internal_link($url)) {
                $link_data['post_id'] = url_to_postid($url);
                $internal_links[] = $link_data;
            } else {
                $link_data['domain'] = parse_url($url, PHP_URL_HOST);
                $external_links[] = $link_data;
            }
        }

        return [
            'internal' => $internal_links,
            'external' => $external_links,
            'internal_count' => count($internal_links),
            'external_count' => count($external_links),
            'total_count' => count($internal_links) + count($external_links),
        ];
    }

    /**
     * Verificar si un enlace es interno
     */
    private function is_internal_link($url) {
        // URLs relativas son internas
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }

        // Comparar con URL del sitio
        $url_host = parse_url($url, PHP_URL_HOST);
        $site_host = parse_url($this->site_url, PHP_URL_HOST);

        return $url_host === $site_host;
    }

    /**
     * Analizar estructura de enlaces del sitio
     */
    public function analyze_site_structure() {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        $structure = [
            'total_posts' => count($posts),
            'orphan_pages' => [],
            'most_linked' => [],
            'least_linked' => [],
            'avg_internal_links' => 0,
            'link_distribution' => [],
        ];

        // Mapa de enlaces entrantes
        $incoming_links = [];
        $outgoing_links = [];

        foreach ($posts as $post) {
            $incoming_links[$post->ID] = 0;
            $outgoing_links[$post->ID] = 0;
        }

        // Analizar cada post
        foreach ($posts as $post) {
            $analysis = $this->analyze_post_links($post->ID, $post->post_content);
            $outgoing_links[$post->ID] = $analysis['internal_count'];

            // Contar enlaces entrantes
            foreach ($analysis['internal'] as $link) {
                if ($link['post_id'] && isset($incoming_links[$link['post_id']])) {
                    $incoming_links[$link['post_id']]++;
                }
            }
        }

        // Encontrar páginas huérfanas (sin enlaces entrantes)
        foreach ($posts as $post) {
            if ($incoming_links[$post->ID] === 0) {
                $structure['orphan_pages'][] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                ];
            }
        }

        // Ordenar por enlaces entrantes
        arsort($incoming_links);
        
        // Más enlazados
        $top_linked = array_slice($incoming_links, 0, 10, true);
        foreach ($top_linked as $post_id => $count) {
            $post = get_post($post_id);
            if ($post) {
                $structure['most_linked'][] = [
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'incoming_links' => $count,
                    'url' => get_permalink($post_id),
                ];
            }
        }

        // Menos enlazados (excluyendo huérfanos)
        asort($incoming_links);
        $bottom_linked = array_slice(array_filter($incoming_links, function($c) { return $c > 0; }), 0, 10, true);
        foreach ($bottom_linked as $post_id => $count) {
            $post = get_post($post_id);
            if ($post) {
                $structure['least_linked'][] = [
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'incoming_links' => $count,
                    'url' => get_permalink($post_id),
                ];
            }
        }

        // Promedio de enlaces internos
        $total_outgoing = array_sum($outgoing_links);
        $structure['avg_internal_links'] = count($posts) > 0 
            ? round($total_outgoing / count($posts), 1) 
            : 0;

        // Distribución de enlaces
        $structure['link_distribution'] = [
            '0_links' => count(array_filter($outgoing_links, function($c) { return $c === 0; })),
            '1-2_links' => count(array_filter($outgoing_links, function($c) { return $c >= 1 && $c <= 2; })),
            '3-5_links' => count(array_filter($outgoing_links, function($c) { return $c >= 3 && $c <= 5; })),
            '6+_links' => count(array_filter($outgoing_links, function($c) { return $c >= 6; })),
        ];

        return $structure;
    }

    /**
     * Verificar enlaces rotos
     */
    public function check_broken_links($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $analysis = $this->analyze_post_links($post_id, $post->post_content);
        $broken = [];

        // Límite configurable para verificación de enlaces externos
        // Límite absoluto de 100 para prevenir DoS
        $max_links = apply_filters('sag_max_links_to_check', (int) get_option('sag_max_links_to_check', 50));
        $max_links = max(1, min($max_links, 100)); // Forzar entre 1 y 100
        $external_to_check = array_slice($analysis['external'], 0, $max_links);

        foreach ($external_to_check as $link) {
            $status = $this->check_url_status_with_cache($link['url']);
            if ($status === false || $status >= 400) {
                $broken[] = [
                    'url' => $link['url'],
                    'anchor' => $link['anchor'],
                    'status' => $status,
                    'type' => 'external',
                ];
            }
        }

        // Verificar enlaces internos
        foreach ($analysis['internal'] as $link) {
            if (!$link['post_id'] && !$this->url_exists($link['url'])) {
                $broken[] = [
                    'url' => $link['url'],
                    'anchor' => $link['anchor'],
                    'status' => 404,
                    'type' => 'internal',
                ];
            }
        }

        return $broken;
    }

    /**
     * Verificar estado de URL con cache
     */
    private function check_url_status_with_cache($url) {
        // Generar key para transient
        $cache_key = 'sag_link_status_' . md5($url);

        // Intentar obtener del cache
        $cached_status = get_transient($cache_key);
        if ($cached_status !== false) {
            return $cached_status;
        }

        // Verificar URL
        $status = $this->check_url_status($url);

        // Cachear resultado (24 horas para válidos, 1 hora para rotos)
        $expiration = ($status >= 200 && $status < 400) ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
        set_transient($cache_key, $status, $expiration);

        return $status;
    }

    /**
     * Verificar estado de URL
     */
    private function check_url_status($url) {
        $response = wp_remote_head($url, [
            'timeout' => 5,
            'redirection' => 3,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_response_code($response);
    }

    /**
     * Verificar si URL existe localmente
     */
    private function url_exists($url) {
        // Para URLs relativas
        if (strpos($url, '/') === 0) {
            $url = $this->site_url . $url;
        }

        // Intentar obtener post ID
        $post_id = url_to_postid($url);
        if ($post_id) {
            return true;
        }

        // Verificar si es una página o archivo existente
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $page = get_page_by_path(trim($path, '/'));
            if ($page) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener sugerencias de enlaces para un post
     */
    public function get_link_opportunities($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $current_links = $this->analyze_post_links($post_id, $post->post_content);
        $linked_ids = array_filter(array_column($current_links['internal'], 'post_id'));

        // Obtener posts relacionados por categoría
        $categories = wp_get_post_categories($post_id);
        $related_posts = [];

        if (!empty($categories)) {
            $related = get_posts([
                'category__in' => $categories,
                'post__not_in' => array_merge([$post_id], $linked_ids),
                'posts_per_page' => 10,
                'post_status' => 'publish',
            ]);

            foreach ($related as $rel) {
                $related_posts[] = [
                    'id' => $rel->ID,
                    'title' => $rel->post_title,
                    'url' => get_permalink($rel->ID),
                    'reason' => __('Misma categoría', 'seo-article-generator'),
                ];
            }
        }

        // Obtener posts relacionados por tags
        $tags = wp_get_post_tags($post_id, ['fields' => 'ids']);
        
        if (!empty($tags)) {
            $tag_related = get_posts([
                'tag__in' => $tags,
                'post__not_in' => array_merge([$post_id], $linked_ids, array_column($related_posts, 'id')),
                'posts_per_page' => 5,
                'post_status' => 'publish',
            ]);

            foreach ($tag_related as $rel) {
                $related_posts[] = [
                    'id' => $rel->ID,
                    'title' => $rel->post_title,
                    'url' => get_permalink($rel->ID),
                    'reason' => __('Tags similares', 'seo-article-generator'),
                ];
            }
        }

        return $related_posts;
    }
}
