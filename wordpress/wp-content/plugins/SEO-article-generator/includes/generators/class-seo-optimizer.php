<?php
/**
 * Optimizador SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_SEO_Optimizer {

    /**
     * Configuraciones SEO
     */
    private $max_title_length;
    private $max_desc_length;
    private $target_keyword_density;
    private $max_internal_links;

    /**
     * Constructor
     */
    public function __construct() {
        $this->max_title_length = (int) get_option('sag_meta_title_max', 60);
        $this->max_desc_length = (int) get_option('sag_meta_desc_max', 160);
        $this->target_keyword_density = (float) get_option('sag_keyword_density', 1.5);
        $this->max_internal_links = (int) get_option('sag_max_internal_links', 5);
    }

    /**
     * Optimizar contenido para SEO
     */
    public function optimize($content, $keyword) {
        // Análisis inicial
        $analysis = $this->analyze($content, $keyword);

        // Optimizaciones automáticas
        $optimized_content = $content;

        // Asegurar que los headings sean jerárquicos
        $optimized_content = $this->fix_heading_hierarchy($optimized_content);

        // Añadir atributos alt a imágenes si faltan
        $optimized_content = $this->optimize_images($optimized_content, $keyword);

        // Calcular score SEO
        $seo_score = $this->calculate_seo_score($optimized_content, $keyword, $analysis);

        return [
            'content' => $optimized_content,
            'seo_score' => $seo_score,
            'analysis' => $analysis,
            'secondary_keywords' => $this->extract_secondary_keywords($optimized_content),
        ];
    }

    /**
     * Analizar contenido
     */
    public function analyze($content, $keyword) {
        $text = wp_strip_all_tags($content);
        $text_lower = mb_strtolower($text);
        $keyword_lower = mb_strtolower($keyword);

        $word_count = str_word_count($text);
        $keyword_count = substr_count($text_lower, $keyword_lower);
        $keyword_density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

        // Contar headings
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $headings);
        $heading_counts = array_count_values($headings[1] ?? []);

        // Verificar keyword en lugares importantes
        $first_paragraph = $this->get_first_paragraph($content);
        $keyword_in_first_para = stripos($first_paragraph, $keyword) !== false;
        
        // Contar enlaces
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $links);
        $internal_links = 0;
        $external_links = 0;
        $site_url = get_site_url();
        
        foreach ($links[1] ?? [] as $link) {
            if (strpos($link, $site_url) === 0 || strpos($link, '/') === 0) {
                $internal_links++;
            } else {
                $external_links++;
            }
        }

        // Contar imágenes
        preg_match_all('/<img[^>]+>/i', $content, $images);
        $image_count = count($images[0] ?? []);

        // Verificar imágenes sin alt
        preg_match_all('/<img(?![^>]*alt=)[^>]+>/i', $content, $images_no_alt);
        $images_without_alt = count($images_no_alt[0] ?? []);

        return [
            'word_count' => $word_count,
            'keyword_count' => $keyword_count,
            'keyword_density' => round($keyword_density, 2),
            'keyword_in_first_paragraph' => $keyword_in_first_para,
            'heading_counts' => $heading_counts,
            'has_h1' => isset($heading_counts['1']) && $heading_counts['1'] > 0,
            'has_h2' => isset($heading_counts['2']) && $heading_counts['2'] > 0,
            'internal_links' => $internal_links,
            'external_links' => $external_links,
            'image_count' => $image_count,
            'images_without_alt' => $images_without_alt,
            'paragraph_count' => substr_count($content, '</p>'),
        ];
    }

    /**
     * Calcular score SEO (0-100)
     */
    private function calculate_seo_score($content, $keyword, $analysis) {
        $score = 0;
        $max_score = 100;

        // Longitud del contenido (20 puntos)
        if ($analysis['word_count'] >= 300) $score += 5;
        if ($analysis['word_count'] >= 600) $score += 5;
        if ($analysis['word_count'] >= 1000) $score += 5;
        if ($analysis['word_count'] >= 1500) $score += 5;

        // Densidad de keyword (15 puntos)
        if ($analysis['keyword_density'] >= 0.5 && $analysis['keyword_density'] <= 2.5) {
            $score += 15;
        } elseif ($analysis['keyword_density'] > 0 && $analysis['keyword_density'] < 3) {
            $score += 8;
        }

        // Keyword en primer párrafo (10 puntos)
        if ($analysis['keyword_in_first_paragraph']) {
            $score += 10;
        }

        // Estructura de headings (15 puntos)
        if ($analysis['has_h2']) $score += 10;
        if (($analysis['heading_counts']['2'] ?? 0) >= 3) $score += 5;

        // Enlaces internos (10 puntos)
        if ($analysis['internal_links'] >= 1) $score += 3;
        if ($analysis['internal_links'] >= 2) $score += 3;
        if ($analysis['internal_links'] >= 3) $score += 4;

        // Enlaces externos (5 puntos)
        if ($analysis['external_links'] >= 1) $score += 5;

        // Imágenes (10 puntos)
        if ($analysis['image_count'] >= 1) $score += 5;
        if ($analysis['images_without_alt'] === 0 && $analysis['image_count'] > 0) $score += 5;

        // Párrafos (10 puntos)
        if ($analysis['paragraph_count'] >= 5) $score += 5;
        if ($analysis['paragraph_count'] >= 10) $score += 5;

        // Legibilidad básica (5 puntos)
        $avg_words_per_paragraph = $analysis['paragraph_count'] > 0 
            ? $analysis['word_count'] / $analysis['paragraph_count'] 
            : 0;
        if ($avg_words_per_paragraph > 0 && $avg_words_per_paragraph <= 100) {
            $score += 5;
        }

        return min($score, $max_score);
    }

    /**
     * Obtener primer párrafo
     */
    private function get_first_paragraph($content) {
        if (preg_match('/<p[^>]*>(.+?)<\/p>/is', $content, $matches)) {
            return wp_strip_all_tags($matches[1]);
        }
        return '';
    }

    /**
     * Corregir jerarquía de headings
     */
    private function fix_heading_hierarchy($content) {
        // Asegurar que no haya saltos en la jerarquía
        // Por ahora, solo verificamos estructura básica
        return $content;
    }

    /**
     * Optimizar imágenes
     */
    private function optimize_images($content, $keyword) {
        // Añadir alt text basado en keyword si falta
        $content = preg_replace_callback(
            '/<img(?![^>]*alt=)([^>]+)>/i',
            function($matches) use ($keyword) {
                return '<img alt="' . esc_attr($keyword) . '"' . $matches[1] . '>';
            },
            $content
        );

        return $content;
    }

    /**
     * Extraer keywords secundarias
     */
    private function extract_secondary_keywords($content) {
        $text = wp_strip_all_tags($content);
        $text = mb_strtolower($text);
        
        // Eliminar palabras comunes
        $stopwords = ['el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al', 
                      'a', 'en', 'que', 'y', 'o', 'para', 'por', 'con', 'sin', 'sobre', 'como',
                      'pero', 'más', 'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos',
                      'su', 'sus', 'se', 'lo', 'le', 'les', 'es', 'son', 'fue', 'ser', 'ha',
                      'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'];
        
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        $word_counts = array_count_values($words);
        arsort($word_counts);
        
        return array_slice(array_keys($word_counts), 0, 10);
    }

    /**
     * Generar meta título
     */
    public function generate_meta_title($title, $keyword) {
        // Si el título ya es bueno, usarlo
        if (strlen($title) <= $this->max_title_length && stripos($title, $keyword) !== false) {
            return $title;
        }

        // Truncar si es necesario
        if (strlen($title) > $this->max_title_length) {
            $title = substr($title, 0, $this->max_title_length - 3) . '...';
        }

        return $title;
    }

    /**
     * Generar meta descripción
     */
    public function generate_meta_description($content, $keyword) {
        // Intentar usar el primer párrafo
        $first_para = $this->get_first_paragraph($content);
        
        if (!empty($first_para)) {
            if (strlen($first_para) > $this->max_desc_length) {
                $first_para = substr($first_para, 0, $this->max_desc_length - 3) . '...';
            }
            return $first_para;
        }

        // Fallback: primeras palabras del contenido
        $text = wp_strip_all_tags($content);
        return wp_trim_words($text, 25, '...');
    }

    /**
     * Sugerir mejoras SEO
     */
    public function get_suggestions($content, $keyword) {
        $analysis = $this->analyze($content, $keyword);
        $suggestions = [];

        if ($analysis['word_count'] < 300) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => __('El contenido es muy corto. Recomendamos al menos 300 palabras.', 'seo-article-generator'),
            ];
        }

        if (!$analysis['keyword_in_first_paragraph']) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => __('La keyword principal no aparece en el primer párrafo.', 'seo-article-generator'),
            ];
        }

        if ($analysis['keyword_density'] < 0.5) {
            $suggestions[] = [
                'type' => 'info',
                'message' => __('La densidad de keyword es baja. Considera incluirla más veces.', 'seo-article-generator'),
            ];
        } elseif ($analysis['keyword_density'] > 2.5) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => __('La densidad de keyword es alta. Puede considerarse keyword stuffing.', 'seo-article-generator'),
            ];
        }

        if (!$analysis['has_h2']) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => __('No hay subtítulos H2. Añade estructura al contenido.', 'seo-article-generator'),
            ];
        }

        if ($analysis['internal_links'] === 0) {
            $suggestions[] = [
                'type' => 'info',
                'message' => __('No hay enlaces internos. Añade enlaces a otros artículos.', 'seo-article-generator'),
            ];
        }

        if ($analysis['image_count'] === 0) {
            $suggestions[] = [
                'type' => 'info',
                'message' => __('No hay imágenes. Añade al menos una imagen relevante.', 'seo-article-generator'),
            ];
        }

        if ($analysis['images_without_alt'] > 0) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => sprintf(
                    __('%d imagen(es) sin texto alt.', 'seo-article-generator'),
                    $analysis['images_without_alt']
                ),
            ];
        }

        return $suggestions;
    }
}
