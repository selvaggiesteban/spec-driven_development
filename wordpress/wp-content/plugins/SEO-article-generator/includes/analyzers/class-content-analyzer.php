<?php
/**
 * Analizador de contenido
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Content_Analyzer {

    /**
     * Cliente Gemini
     */
    private $gemini;

    /**
     * Analizador de keywords
     */
    private $keyword_analyzer;

    /**
     * Analizador de enlaces
     */
    private $link_analyzer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->gemini = new SAG_Gemini_Client();
        $this->keyword_analyzer = new SAG_Keyword_Analyzer();
        $this->link_analyzer = new SAG_Link_Analyzer();
    }

    /**
     * Analizar todo el sitio
     */
    public function analyze_site() {
        // Aumentar límite de tiempo para análisis grandes
        set_time_limit(300); // 5 minutos

        $results = [
            'total_posts' => 0,
            'analyzed_posts' => 0,
            'keywords_map' => [],
            'topic_gaps' => [],
            'suggestions' => [],
            'link_structure' => [],
        ];

        // Obtener todos los posts publicados
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        $results['total_posts'] = count($posts);

        // Analizar cada post
        $all_keywords = [];
        foreach ($posts as $post) {
            try {
                $analysis = $this->analyze_post($post->ID);

                if ($analysis) {
                    $results['analyzed_posts']++;

                    // Recopilar keywords
                    if (!empty($analysis['keywords'])) {
                        foreach ($analysis['keywords'] as $keyword) {
                            if (!isset($all_keywords[$keyword])) {
                                $all_keywords[$keyword] = 0;
                            }
                            $all_keywords[$keyword]++;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log(sprintf('SAG: Error analizando post %d: %s', $post->ID, $e->getMessage()));
                // Continuar con el siguiente post
                continue;
            }
        }

        // Ordenar keywords por frecuencia
        arsort($all_keywords);
        $results['keywords_map'] = array_slice($all_keywords, 0, 50, true);

        // Detectar gaps de contenido (con manejo de errores)
        try {
            $results['topic_gaps'] = $this->detect_topic_gaps($all_keywords);
        } catch (Exception $e) {
            error_log('SAG: Error detectando topic gaps: ' . $e->getMessage());
            $results['topic_gaps'] = [];
        }

        // Analizar estructura de enlaces (con manejo de errores)
        try {
            $results['link_structure'] = $this->link_analyzer->analyze_site_structure();
        } catch (Exception $e) {
            error_log('SAG: Error analizando estructura de enlaces: ' . $e->getMessage());
            $results['link_structure'] = [
                'total_posts' => 0,
                'orphan_pages' => [],
                'most_linked' => [],
                'avg_internal_links' => 0,
            ];
        }

        // Generar sugerencias de nuevos temas (con manejo de errores)
        try {
            $results['suggestions'] = $this->generate_topic_suggestions($all_keywords);
        } catch (Exception $e) {
            error_log('SAG: Error generando sugerencias: ' . $e->getMessage());
            $results['suggestions'] = [];
        }

        return $results;
    }

    /**
     * Analizar un post específico
     */
    public function analyze_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return null;
        }

        $content = $post->post_content;
        $title = $post->post_title;

        // Análisis básico
        $word_count = str_word_count(wp_strip_all_tags($content));
        
        // Extraer keywords
        $keywords = $this->keyword_analyzer->extract_keywords($content);

        // Analizar enlaces
        $links = $this->link_analyzer->analyze_post_links($post_id, $content);

        // Calcular legibilidad
        $readability = $this->calculate_readability($content);

        // Generar sugerencias con IA (opcional, solo si la API está configurada)
        $ai_suggestions = [];
        if ($this->gemini->is_configured()) {
            $ai_analysis = $this->gemini->analyze_content($content);
            if (!is_wp_error($ai_analysis)) {
                $ai_suggestions = $ai_analysis['seo_suggestions'] ?? [];
                if (!empty($ai_analysis['keywords'])) {
                    $keywords = array_unique(array_merge($keywords, $ai_analysis['keywords']));
                }
            }
        }

        // Guardar análisis en base de datos
        $analysis_data = [
            'keywords' => $keywords,
            'internal_links_count' => $links['internal_count'],
            'external_links_count' => $links['external_count'],
            'word_count' => $word_count,
            'readability_score' => $readability['score'],
            'suggestions' => $ai_suggestions,
        ];

        SAG_Database::save_analysis($post_id, $analysis_data);

        return $analysis_data;
    }

    /**
     * Calcular legibilidad (Flesch-Kincaid adaptado para español)
     */
    private function calculate_readability($content) {
        $text = wp_strip_all_tags($content);
        
        // Contar palabras
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        if ($word_count === 0) {
            return ['score' => 0, 'level' => 'N/A'];
        }

        // Contar oraciones
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        if ($sentence_count === 0) {
            $sentence_count = 1;
        }

        // Contar sílabas (aproximación para español)
        $syllable_count = $this->count_syllables($text);

        // Fórmula Flesch adaptada
        $asl = $word_count / $sentence_count; // Longitud promedio de oración
        $asw = $syllable_count / $word_count; // Sílabas promedio por palabra

        // Fórmula para español (Fernández-Huerta)
        $score = 206.84 - (0.6 * $asl) - (1.02 * ($asw * 100));
        $score = max(0, min(100, $score));

        // Determinar nivel
        $level = 'Muy difícil';
        if ($score >= 80) $level = 'Muy fácil';
        elseif ($score >= 60) $level = 'Fácil';
        elseif ($score >= 50) $level = 'Normal';
        elseif ($score >= 30) $level = 'Difícil';

        return [
            'score' => round($score),
            'level' => $level,
            'avg_sentence_length' => round($asl, 1),
            'avg_syllables_per_word' => round($asw, 2),
        ];
    }

    /**
     * Contar sílabas (aproximación)
     */
    private function count_syllables($text) {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-záéíóúüñ]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $total = 0;
        $vowels = 'aeiouáéíóúü';
        
        foreach ($words as $word) {
            $count = 0;
            $prev_was_vowel = false;
            
            for ($i = 0; $i < mb_strlen($word); $i++) {
                $char = mb_substr($word, $i, 1);
                $is_vowel = mb_strpos($vowels, $char) !== false;
                
                if ($is_vowel && !$prev_was_vowel) {
                    $count++;
                }
                $prev_was_vowel = $is_vowel;
            }
            
            $total += max(1, $count);
        }
        
        return $total;
    }

    /**
     * Detectar gaps de contenido
     */
    private function detect_topic_gaps($existing_keywords) {
        // Intentar usar IA si está configurada
        $gemini = new SAG_Gemini_Client();

        if ($gemini->is_configured() && !empty($existing_keywords)) {
            $ai_gaps = $this->detect_gaps_with_ai($existing_keywords);
            if (!is_wp_error($ai_gaps) && !empty($ai_gaps)) {
                return $ai_gaps;
            }
        }

        // Fallback: lógica simple basada en categorías
        return $this->detect_gaps_simple($existing_keywords);
    }

    /**
     * Detectar gaps usando IA
     */
    private function detect_gaps_with_ai($existing_keywords) {
        // Obtener top keywords del sitio
        $top_keywords = array_slice($existing_keywords, 0, 20, true);
        $keywords_list = implode(', ', array_keys($top_keywords));

        // Obtener información del sitio
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');

        // Construir prompt para Gemini
        $prompt = "Analiza los siguientes temas/keywords de un blog:

Sitio: {$site_name}
Descripción: {$site_description}
Keywords principales: {$keywords_list}

Basándote en estos temas existentes, identifica 5 gaps o vacíos de contenido que deberían cubrirse para tener un blog más completo. Para cada gap, sugiere:
1. El tipo de contenido (guía, tutorial, comparativa, etc.)
2. El tema específico
3. Por qué es relevante para la audiencia

Responde en formato JSON con esta estructura:
[
  {
    \"topic\": \"nombre del tema\",
    \"type\": \"tipo de contenido\",
    \"suggestion\": \"descripción de por qué es útil\",
    \"keyword\": \"keyword sugerida\"
  }
]

Responde SOLO con el JSON, sin texto adicional.";

        // Usar instancia existente de Gemini en lugar de crear nueva
        $result = $this->gemini->generate_content($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        if (is_wp_error($result)) {
            error_log('SAG: Error generando topic gaps con IA: ' . $result->get_error_message());
            return new WP_Error('ai_error', 'Error al analizar gaps con IA');
        }

        // Parsear respuesta JSON
        $response_text = trim($result['text']);

        // Limpiar markdown si existe
        $response_text = preg_replace('/^```json\s*/m', '', $response_text);
        $response_text = preg_replace('/```\s*$/m', '', $response_text);

        $parsed = json_decode($response_text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('SAG: Error parseando JSON de topic gaps: ' . json_last_error_msg());
            return new WP_Error('parse_error', 'Error al parsear respuesta de IA');
        }

        return $parsed;
    }

    /**
     * Detectar gaps con lógica simple (fallback)
     */
    private function detect_gaps_simple($existing_keywords) {
        $gaps = [];

        // Categorías comunes que podrían faltar
        $common_topics = [
            'guía' => 'Crear una guía completa para principiantes',
            'tutorial' => 'Crear tutoriales paso a paso',
            'cómo' => 'Crear artículos de tipo "cómo hacer"',
            'mejores' => 'Crear listados de "mejores prácticas" o "mejores herramientas"',
            'comparativa' => 'Crear comparativas entre diferentes opciones',
            'consejos' => 'Compartir consejos prácticos',
            'errores' => 'Explicar errores comunes y cómo evitarlos',
            'herramientas' => 'Revisar herramientas útiles',
            'tendencias' => 'Analizar tendencias del sector',
        ];

        // Detectar qué temas tienen poca cobertura
        foreach ($common_topics as $topic => $suggestion) {
            $found = false;
            foreach (array_keys($existing_keywords) as $keyword) {
                if (stripos($keyword, $topic) !== false) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $gaps[] = [
                    'topic' => ucfirst($topic),
                    'type' => $topic,
                    'suggestion' => $suggestion,
                    'keyword' => $topic,
                ];
            }
        }

        // Limitar a 5 sugerencias
        return array_slice($gaps, 0, 5);
    }

    /**
     * Generar sugerencias de nuevos temas
     */
    private function generate_topic_suggestions($existing_keywords) {
        if (!$this->gemini->is_configured()) {
            return $this->generate_basic_suggestions($existing_keywords);
        }

        $top_keywords = array_slice(array_keys($existing_keywords), 0, 20);
        $keywords_list = implode(', ', $top_keywords);

        $prompt = <<<PROMPT
Basándote en estas keywords que ya existen en un blog:
{$keywords_list}

Sugiere 10 temas de artículos nuevos que:
1. Complementen el contenido existente
2. Cubran ángulos no explorados
3. Apunten a keywords de cola larga
4. Sean relevantes para la audiencia

Formato de respuesta (JSON array):
[
    {
        "topic": "Título sugerido del artículo",
        "keyword": "keyword principal",
        "reason": "Por qué este tema es relevante"
    }
]
PROMPT;

        $result = $this->gemini->generate_content($prompt, ['temperature' => 0.7]);
        
        if (is_wp_error($result)) {
            return $this->generate_basic_suggestions($existing_keywords);
        }

        $text = preg_replace('/```json\s*/', '', $result['text']);
        $text = preg_replace('/```\s*/', '', $text);
        
        $suggestions = json_decode(trim($text), true);
        
        return is_array($suggestions) ? $suggestions : $this->generate_basic_suggestions($existing_keywords);
    }

    /**
     * Generar sugerencias básicas sin IA
     */
    private function generate_basic_suggestions($existing_keywords) {
        $suggestions = [];
        $top_keywords = array_slice(array_keys($existing_keywords), 0, 5);

        $templates = [
            'Guía completa de %s para principiantes',
            'Los 10 mejores consejos sobre %s',
            'Errores comunes en %s y cómo evitarlos',
            '%s vs alternativas: comparativa completa',
            'Tendencias de %s para el próximo año',
        ];

        foreach ($top_keywords as $keyword) {
            $template = $templates[array_rand($templates)];
            $suggestions[] = [
                'topic' => sprintf($template, $keyword),
                'keyword' => $keyword,
                'reason' => 'Basado en tus keywords más frecuentes',
            ];
        }

        return $suggestions;
    }

    /**
     * Obtener posts que necesitan actualización
     */
    public function get_posts_needing_update($days = 180) {
        global $wpdb;

        $date_threshold = date('Y-m-d', strtotime("-{$days} days"));

        // Construir nombres de tabla FUERA de prepare() para prevenir SQL injection
        $posts_table = $wpdb->posts;
        $analysis_table = $wpdb->prefix . 'sag_content_analysis';

        // Construir query con variables de tabla fuera de prepare()
        $query = "
            SELECT p.ID, p.post_title, p.post_modified, a.word_count, a.readability_score
            FROM {$posts_table} p
            LEFT JOIN {$analysis_table} a ON p.ID = a.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_modified < %s
            ORDER BY p.post_modified ASC
            LIMIT 20
        ";

        // Ahora sí usar prepare() solo para los datos dinámicos
        $posts = $wpdb->get_results($wpdb->prepare($query, $date_threshold));

        return $posts;
    }
}
