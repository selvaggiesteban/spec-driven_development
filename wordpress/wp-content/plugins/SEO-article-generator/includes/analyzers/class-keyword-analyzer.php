<?php
/**
 * Analizador de keywords
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Keyword_Analyzer {

    /**
     * Palabras vacías en español
     */
    private $stopwords_es = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al',
        'a', 'en', 'que', 'y', 'o', 'para', 'por', 'con', 'sin', 'sobre', 'como',
        'pero', 'más', 'menos', 'este', 'esta', 'estos', 'estas', 'ese', 'esa',
        'esos', 'esas', 'aquel', 'aquella', 'su', 'sus', 'mi', 'mis', 'tu', 'tus',
        'se', 'lo', 'le', 'les', 'me', 'te', 'nos', 'os', 'es', 'son', 'era',
        'fue', 'ser', 'estar', 'ha', 'he', 'han', 'hay', 'sido', 'siendo',
        'muy', 'mucho', 'poco', 'todo', 'todos', 'toda', 'todas', 'otro', 'otra',
        'otros', 'otras', 'mismo', 'misma', 'tan', 'tanto', 'así', 'ya', 'sí',
        'no', 'ni', 'aquí', 'ahí', 'allí', 'donde', 'cuando', 'quien', 'cual',
        'cuyo', 'cuya', 'qué', 'cómo', 'cuándo', 'dónde', 'porque', 'aunque',
        'sino', 'pues', 'entonces', 'luego', 'después', 'antes', 'mientras',
        'desde', 'hasta', 'entre', 'durante', 'mediante', 'según', 'contra',
    ];

    /**
     * Palabras vacías en inglés
     */
    private $stopwords_en = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been',
        'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that',
        'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
        'what', 'which', 'who', 'when', 'where', 'why', 'how', 'all', 'each',
        'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no',
        'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just',
    ];

    /**
     * Extraer keywords de contenido
     */
    public function extract_keywords($content, $limit = 15) {
        $text = wp_strip_all_tags($content);
        $text = mb_strtolower($text);
        
        // Eliminar caracteres especiales pero mantener acentos
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Dividir en palabras
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtrar stopwords y palabras cortas
        $stopwords = array_merge($this->stopwords_es, $this->stopwords_en);
        $filtered = array_filter($words, function($word) use ($stopwords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopwords);
        });

        // Contar frecuencias
        $frequencies = array_count_values($filtered);
        arsort($frequencies);

        // También extraer n-gramas (2 palabras)
        $bigrams = $this->extract_ngrams($words, 2, $stopwords);
        
        // Combinar singles y bigrams
        $all_keywords = [];
        
        // Añadir palabras simples
        foreach (array_slice($frequencies, 0, $limit) as $word => $count) {
            $all_keywords[$word] = $count;
        }
        
        // Añadir bigramas relevantes
        foreach (array_slice($bigrams, 0, 5) as $bigram => $count) {
            if ($count >= 2) { // Solo si aparece al menos 2 veces
                $all_keywords[$bigram] = $count;
            }
        }

        arsort($all_keywords);
        return array_keys(array_slice($all_keywords, 0, $limit, true));
    }

    /**
     * Extraer n-gramas
     */
    private function extract_ngrams($words, $n, $stopwords) {
        $ngrams = [];
        $count = count($words);

        for ($i = 0; $i <= $count - $n; $i++) {
            $gram_words = array_slice($words, $i, $n);
            
            // Verificar que ninguna palabra sea stopword
            $valid = true;
            foreach ($gram_words as $w) {
                if (in_array($w, $stopwords) || mb_strlen($w) <= 2) {
                    $valid = false;
                    break;
                }
            }

            if ($valid) {
                $gram = implode(' ', $gram_words);
                if (!isset($ngrams[$gram])) {
                    $ngrams[$gram] = 0;
                }
                $ngrams[$gram]++;
            }
        }

        arsort($ngrams);
        return $ngrams;
    }

    /**
     * Calcular densidad de keyword
     */
    public function calculate_density($content, $keyword) {
        $text = wp_strip_all_tags($content);
        $text_lower = mb_strtolower($text);
        $keyword_lower = mb_strtolower($keyword);

        $word_count = str_word_count($text);
        $keyword_count = substr_count($text_lower, $keyword_lower);

        if ($word_count === 0) {
            return 0;
        }

        return round(($keyword_count / $word_count) * 100, 2);
    }

    /**
     * Sugerir keywords relacionadas
     */
    public function suggest_related($keyword, $existing_keywords = []) {
        // Variaciones básicas
        $suggestions = [];

        // Prefijos comunes
        $prefixes = ['cómo', 'qué es', 'mejores', 'guía de', 'tutorial'];
        foreach ($prefixes as $prefix) {
            $suggestion = $prefix . ' ' . $keyword;
            if (!in_array($suggestion, $existing_keywords)) {
                $suggestions[] = $suggestion;
            }
        }

        // Sufijos comunes
        $suffixes = ['gratis', 'online', 'fácil', 'para principiantes', 'profesional'];
        foreach ($suffixes as $suffix) {
            $suggestion = $keyword . ' ' . $suffix;
            if (!in_array($suggestion, $existing_keywords)) {
                $suggestions[] = $suggestion;
            }
        }

        return array_slice($suggestions, 0, 10);
    }

    /**
     * Analizar competencia de keyword en el sitio
     */
    public function analyze_keyword_usage($keyword) {
        global $wpdb;

        $keyword_lower = mb_strtolower($keyword);

        // Buscar en títulos
        $in_titles = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'post'
            AND LOWER(post_title) LIKE %s
        ", '%' . $wpdb->esc_like($keyword_lower) . '%'));

        // Buscar en contenido
        $in_content = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'post'
            AND LOWER(post_content) LIKE %s
        ", '%' . $wpdb->esc_like($keyword_lower) . '%'));

        // Buscar en RankMath focus keywords
        $as_focus = 0;
        if (class_exists('RankMath')) {
            $as_focus = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = 'rank_math_focus_keyword'
                AND LOWER(meta_value) LIKE %s
            ", '%' . $wpdb->esc_like($keyword_lower) . '%'));
        }

        return [
            'keyword' => $keyword,
            'in_titles' => (int) $in_titles,
            'in_content' => (int) $in_content,
            'as_focus_keyword' => (int) $as_focus,
            'saturation' => $this->calculate_saturation($in_titles, $in_content, $as_focus),
        ];
    }

    /**
     * Calcular saturación de keyword
     */
    private function calculate_saturation($titles, $content, $focus) {
        $total_posts = wp_count_posts('post')->publish;
        
        if ($total_posts === 0) {
            return 'low';
        }

        $usage_rate = (($titles * 2) + $content + ($focus * 3)) / ($total_posts * 3);

        if ($usage_rate > 0.5) return 'high';
        if ($usage_rate > 0.2) return 'medium';
        return 'low';
    }

    /**
     * Detectar keyword cannibalization
     */
    public function detect_cannibalization($keyword) {
        global $wpdb;

        $keyword_lower = mb_strtolower($keyword);

        // Buscar posts que compitan por la misma keyword
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, pm.meta_value as focus_keyword
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'rank_math_focus_keyword'
            WHERE p.post_status = 'publish'
            AND p.post_type = 'post'
            AND (
                LOWER(p.post_title) LIKE %s
                OR LOWER(pm.meta_value) LIKE %s
            )
            ORDER BY p.post_date DESC
        ", '%' . $wpdb->esc_like($keyword_lower) . '%', '%' . $wpdb->esc_like($keyword_lower) . '%'));

        if (count($posts) <= 1) {
            return [
                'has_cannibalization' => false,
                'posts' => [],
            ];
        }

        return [
            'has_cannibalization' => true,
            'posts' => array_map(function($p) {
                return [
                    'id' => $p->ID,
                    'title' => $p->post_title,
                    'focus_keyword' => $p->focus_keyword,
                    'url' => get_permalink($p->ID),
                ];
            }, $posts),
            'recommendation' => __('Considera consolidar estos artículos o diferenciar sus focus keywords.', 'seo-article-generator'),
        ];
    }
}
