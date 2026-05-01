<?php
/**
 * Gestor de enlaces externos
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_External_Linker {

    /**
     * Fuentes de autoridad por categoría
     */
    private $authority_sources = [
        'general' => [
            'wikipedia.org',
            'britannica.com',
        ],
        'tech' => [
            'developer.mozilla.org',
            'w3.org',
            'github.com',
            'stackoverflow.com',
        ],
        'marketing' => [
            'hubspot.com',
            'moz.com',
            'searchengineland.com',
            'semrush.com',
        ],
        'business' => [
            'forbes.com',
            'hbr.org',
            'businessinsider.com',
        ],
        'science' => [
            'nature.com',
            'sciencedirect.com',
            'pubmed.ncbi.nlm.nih.gov',
        ],
        'statistics' => [
            'statista.com',
            'pewresearch.org',
            'worldbank.org',
        ],
    ];

    /**
     * Añadir enlaces externos a contenido
     */
    public function add_external_links($content, $options = []) {
        $max_links = $options['max_links'] ?? (int) get_option('sag_max_external_links', 3);
        $category = $options['category'] ?? 'general';
        $keyword = $options['keyword'] ?? '';

        // Obtener fuentes de autoridad para la categoría
        $sources = $this->authority_sources[$category] ?? $this->authority_sources['general'];

        if (empty($sources)) {
            return $content;
        }

        // Extraer términos relevantes del contenido para enlazar
        $terms_to_link = $this->extract_linkable_terms($content, $keyword);

        if (empty($terms_to_link)) {
            return $content;
        }

        $links_added = 0;
        $modified_content = $content;

        // Intentar agregar enlaces hasta el máximo permitido
        foreach ($terms_to_link as $term) {
            if ($links_added >= $max_links) {
                break;
            }

            // Seleccionar fuente de autoridad aleatoria
            $source = $sources[array_rand($sources)];

            // Construir URL de búsqueda
            $search_url = $this->build_search_url($source, $term);

            // Verificar que el término no esté ya enlazado
            if ($this->is_term_already_linked($modified_content, $term)) {
                continue;
            }

            // Crear el enlace con atributos apropiados
            $rel_attr = $this->get_rel_attribute($search_url);
            $target_attr = get_option('sag_external_target_blank', true) ? ' target="_blank"' : '';
            $link = '<a href="' . esc_url($search_url) . '" rel="' . $rel_attr . '"' . $target_attr . '>' . esc_html($term) . '</a>';

            // Reemplazar primera ocurrencia del término (fuera de tags HTML)
            $modified_content = $this->replace_first_occurrence($modified_content, $term, $link);

            if ($modified_content !== $content) {
                $links_added++;
                $content = $modified_content;
            }
        }

        return $modified_content;
    }

    /**
     * Extraer términos relevantes para enlazar
     */
    private function extract_linkable_terms($content, $keyword) {
        $text = wp_strip_all_tags($content);
        $words = str_word_count($text, 1);

        // Términos comunes que suelen enlazarse a fuentes de autoridad
        $common_terms = [
            'wikipedia', 'estudio', 'investigación', 'datos', 'estadísticas',
            'informe', 'análisis', 'guía', 'documentación', 'referencia',
        ];

        $linkable_terms = [];

        // Buscar términos comunes en el contenido
        foreach ($common_terms as $term) {
            if (stripos($text, $term) !== false) {
                // Extraer contexto alrededor del término (hasta 3 palabras)
                $pattern = '/\b(\w+\s+)?' . preg_quote($term, '/') . '(\s+\w+)?\b/i';
                if (preg_match($pattern, $text, $matches)) {
                    $linkable_terms[] = trim($matches[0]);
                }
            }
        }

        // Si hay keyword, agregarla como término linkable
        if (!empty($keyword) && stripos($text, $keyword) !== false) {
            $linkable_terms[] = $keyword;
        }

        // Limitar a los primeros términos únicos
        return array_unique(array_slice($linkable_terms, 0, 10));
    }

    /**
     * Construir URL de búsqueda para fuente de autoridad
     */
    private function build_search_url($domain, $term) {
        // Sanitizar término para evitar caracteres inválidos en URLs
        $safe_term = sanitize_title($term);

        // URLs específicas por dominio
        $search_patterns = [
            'wikipedia.org' => 'https://es.wikipedia.org/wiki/' . str_replace('-', '_', $safe_term),
            'github.com' => 'https://github.com/search?q=' . urlencode($term),
            'stackoverflow.com' => 'https://stackoverflow.com/search?q=' . urlencode($term),
            'moz.com' => 'https://moz.com/search?q=' . urlencode($term),
        ];

        // Si existe patrón específico, usarlo
        if (isset($search_patterns[$domain])) {
            return $search_patterns[$domain];
        }

        // Fallback: búsqueda en Google site-specific
        return 'https://www.google.com/search?q=' . urlencode($term . ' site:' . $domain);
    }

    /**
     * Verificar si un término ya está enlazado
     */
    private function is_term_already_linked($content, $term) {
        // Buscar si el término aparece dentro de un tag <a>
        $pattern = '/<a[^>]*>' . preg_quote($term, '/') . '<\/a>/i';
        return preg_match($pattern, $content) > 0;
    }

    /**
     * Reemplazar primera ocurrencia fuera de tags HTML
     */
    private function replace_first_occurrence($content, $search, $replace) {
        // Dividir contenido en partes HTML y texto
        $parts = preg_split('/(<[^>]+>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $replaced = false;

        foreach ($parts as $i => $part) {
            // Solo procesar partes que no son HTML tags
            if (!$replaced && strpos($part, '<') !== 0) {
                $pos = stripos($part, $search);
                if ($pos !== false) {
                    // Extraer texto original con mayúsculas/minúsculas
                    $original = substr($part, $pos, strlen($search));
                    // Reemplazar manteniendo el case original en el texto del enlace
                    $link_with_original = str_replace(esc_html($search), $original, $replace);
                    $parts[$i] = substr_replace($part, $link_with_original, $pos, strlen($search));
                    $replaced = true;
                }
            }
        }

        return implode('', $parts);
    }

    /**
     * Verificar y limpiar enlaces externos
     */
    public function validate_external_links($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        // Extraer enlaces externos
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches);
        
        $results = [
            'total' => 0,
            'valid' => 0,
            'broken' => [],
            'redirects' => [],
        ];
        
        $site_url = get_site_url();

        foreach ($matches[1] as $url) {
            // Solo procesar externos
            if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                continue;
            }

            $results['total']++;
            
            $status = $this->check_link_status($url);
            
            if ($status['code'] >= 200 && $status['code'] < 300) {
                $results['valid']++;
            } elseif ($status['code'] >= 300 && $status['code'] < 400) {
                $results['redirects'][] = [
                    'url' => $url,
                    'redirect_to' => $status['redirect'],
                ];
            } else {
                $results['broken'][] = [
                    'url' => $url,
                    'status' => $status['code'],
                ];
            }
        }

        return $results;
    }

    /**
     * Verificar estado de enlace
     */
    private function check_link_status($url) {
        $response = wp_remote_head($url, [
            'timeout' => 10,
            'redirection' => 0,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return ['code' => 0, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $result = ['code' => $code];

        if ($code >= 300 && $code < 400) {
            $result['redirect'] = wp_remote_retrieve_header($response, 'location');
        }

        return $result;
    }

    /**
     * Obtener atributo rel apropiado
     */
    public function get_rel_attribute($url, $context = 'regular') {
        $rels = ['noopener'];

        // Obtener configuración de enlaces externos
        $external_setting = get_option('sag_external_links_rel', 'auto');

        // Aplicar lógica según configuración
        if ($external_setting === 'nofollow') {
            // Forzar nofollow en todos los enlaces externos
            $rels[] = 'nofollow';
        } elseif ($external_setting === 'follow') {
            // Forzar follow (no agregar nofollow)
            // No hacer nada, solo dejar noopener
        } elseif ($external_setting === 'auto') {
            // Lógica automática: nofollow solo si NO es sitio de autoridad
            if (!$this->is_authority_site($url)) {
                $rels[] = 'nofollow';
            }
        }

        // Enlaces patrocinados (si está habilitado)
        if (($context === 'sponsored' || $context === 'affiliate') &&
            get_option('sag_external_sponsored_enabled', false)) {
            $rels[] = 'sponsored';
        }

        // Contenido generado por usuarios
        if ($context === 'ugc') {
            $rels[] = 'ugc';
        }

        return implode(' ', $rels);
    }

    /**
     * Verificar si es sitio de autoridad
     */
    private function is_authority_site($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        // Limpiar www
        $host = preg_replace('/^www\./', '', $host);

        foreach ($this->authority_sources as $sources) {
            if (in_array($host, $sources)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sugerir fuentes de autoridad
     */
    public function suggest_authority_sources($topic, $category = 'general') {
        $suggestions = [];
        
        // Obtener fuentes de la categoría
        $sources = $this->authority_sources[$category] ?? $this->authority_sources['general'];
        
        foreach ($sources as $domain) {
            $suggestions[] = [
                'domain' => $domain,
                'search_query' => urlencode($topic . ' site:' . $domain),
                'google_search' => 'https://www.google.com/search?q=' . urlencode($topic . ' site:' . $domain),
            ];
        }

        return $suggestions;
    }

    /**
     * Actualizar enlaces rotos
     */
    public function fix_broken_links($post_id, $replacements) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $content = $post->post_content;
        $updated = false;

        foreach ($replacements as $old_url => $new_url) {
            if (strpos($content, $old_url) !== false) {
                $content = str_replace($old_url, $new_url, $content);
                $updated = true;
            }
        }

        if ($updated) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $content,
            ]);
        }

        return $updated;
    }

    /**
     * Añadir atributos rel a enlaces existentes
     */
    public function update_link_attributes($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $content = $post->post_content;
        $site_url = get_site_url();
        $updated = false;

        // Buscar enlaces externos sin rel apropiado
        $content = preg_replace_callback(
            '/<a([^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
            function($matches) use ($site_url, &$updated) {
                $before = $matches[1];
                $url = $matches[2];
                $after = $matches[3];

                // Solo procesar externos
                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    return $matches[0];
                }

                // Verificar si ya tiene rel
                if (preg_match('/rel=["\']/', $before . $after)) {
                    return $matches[0];
                }

                $rel = $this->get_rel_attribute($url);
                $updated = true;

                // Obtener configuración de target="_blank"
                $target = get_option('sag_external_target_blank', true) ? ' target="_blank"' : '';

                return '<a' . $before . 'href="' . $url . '" rel="' . $rel . '"' . $target . $after . '>';
            },
            $content
        );

        if ($updated) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $content,
            ]);
        }

        return $updated;
    }

    /**
     * Obtener estadísticas de enlaces externos
     */
    public function get_external_link_stats() {
        global $wpdb;

        $stats = [
            'total_external_links' => 0,
            'domains' => [],
            'posts_with_external_links' => 0,
            'avg_links_per_post' => 0,
        ];

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        $domains = [];
        $posts_with_links = 0;

        foreach ($posts as $post) {
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches);
            
            $external_count = 0;
            $site_url = get_site_url();

            foreach ($matches[1] as $url) {
                if (strpos($url, $site_url) !== 0 && strpos($url, '/') !== 0) {
                    $external_count++;
                    $host = parse_url($url, PHP_URL_HOST);
                    if ($host) {
                        $host = preg_replace('/^www\./', '', $host);
                        if (!isset($domains[$host])) {
                            $domains[$host] = 0;
                        }
                        $domains[$host]++;
                    }
                }
            }

            $stats['total_external_links'] += $external_count;
            if ($external_count > 0) {
                $posts_with_links++;
            }
        }

        arsort($domains);
        $stats['domains'] = array_slice($domains, 0, 20, true);
        $stats['posts_with_external_links'] = $posts_with_links;
        $stats['avg_links_per_post'] = count($posts) > 0 
            ? round($stats['total_external_links'] / count($posts), 2) 
            : 0;

        return $stats;
    }
}
