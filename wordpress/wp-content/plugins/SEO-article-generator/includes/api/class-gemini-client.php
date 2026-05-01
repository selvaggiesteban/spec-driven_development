<?php
/**
 * Cliente de API Gemini
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Gemini_Client {

    /**
     * Endpoint base de la API
     */
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * API Key
     */
    private $api_key;

    /**
     * Modelo de texto
     */
    private $text_model;

    /**
     * Constructor
     */
    public function __construct() {
        // Obtener y desencriptar API key
        $encrypted_key = get_option('sag_api_key', '');
        $this->api_key = !empty($encrypted_key) ? SAG_Crypto_Helper::decrypt($encrypted_key) : '';

        $this->text_model = get_option('sag_model_text', 'gemini-2.5-flash');
    }

    /**
     * Verificar si la API está configurada
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Generar contenido de texto
     */
    public function generate_content($prompt, $options = []) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', __('La API Key de Gemini no está configurada.', 'seo-article-generator'));
        }

        $model = $options['model'] ?? $this->text_model;
        $url = $this->api_base . $model . ':generateContent?key=' . $this->api_key;

        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 8192;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $max_tokens,
                'topP' => 0.95,
                'topK' => 40,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? __('Error desconocido en la API.', 'seo-article-generator');
            return new WP_Error('api_error', $error_message);
        }

        // Extraer texto de la respuesta
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('no_content', __('La API no devolvió contenido.', 'seo-article-generator'));
        }

        return [
            'text' => $body['candidates'][0]['content']['parts'][0]['text'],
            'usage' => $body['usageMetadata'] ?? [],
            'model' => $model,
        ];
    }

    /**
     * Generar artículo SEO completo
     */
    public function generate_seo_article($params) {
        $keyword = $params['keyword'];
        $type = $params['type'] ?? 'guide';
        $length = $params['length'] ?? 'medium';
        $tone = $params['tone'] ?? 'professional';
        $language = get_option('sag_default_language', 'es');
        $include_toc = $params['include_toc'] ?? true;
        $include_faq = $params['include_faq'] ?? true;

        // Mapear longitud a palabras aproximadas
        $word_counts = [
            'short' => '500-800',
            'medium' => '1000-1500',
            'long' => '2000-3000',
            'epic' => '3000-4000',
        ];
        $target_words = $word_counts[$length] ?? '1000-1500';

        // Mapear tipos de artículo
        $article_types = [
            'guide' => 'guía completa y detallada',
            'tutorial' => 'tutorial paso a paso con instrucciones claras',
            'listicle' => 'artículo de lista con puntos numerados',
            'comparison' => 'artículo comparativo analizando pros y contras',
            'case_study' => 'caso de estudio con datos y resultados',
            'news' => 'artículo de noticias o actualidad',
        ];
        $article_style = $article_types[$type] ?? 'guía completa';

        // Mapear tonos
        $tones = [
            'professional' => 'profesional y autorizado',
            'conversational' => 'conversacional y cercano',
            'technical' => 'técnico y detallado',
            'persuasive' => 'persuasivo y convincente',
            'educational' => 'educativo y didáctico',
            'informal' => 'informal y amigable',
        ];
        $tone_style = $tones[$tone] ?? 'profesional';

        $prompt = $this->build_article_prompt([
            'keyword' => $keyword,
            'article_style' => $article_style,
            'target_words' => $target_words,
            'tone_style' => $tone_style,
            'language' => $language,
            'include_toc' => $include_toc,
            'include_faq' => $include_faq,
        ]);

        return $this->generate_content($prompt, [
            'temperature' => 0.7,
            'max_tokens' => $length === 'epic' ? 16384 : 8192,
        ]);
    }

    /**
     * Construir prompt para artículo
     */
    private function build_article_prompt($params) {
        $toc_instruction = $params['include_toc'] 
            ? "\n- Incluye una tabla de contenidos al inicio del artículo con enlaces ancla."
            : "";
        
        $faq_instruction = $params['include_faq']
            ? "\n- Al final, incluye una sección de Preguntas Frecuentes (FAQ) con 4-6 preguntas relevantes y sus respuestas. Formatea las FAQ para Schema markup (cada pregunta como H3)."
            : "";

        $prompt = <<<PROMPT
Eres un experto redactor SEO especializado en crear contenido de alta calidad optimizado para motores de búsqueda.

**TAREA**: Escribe un artículo completo sobre "{$params['keyword']}" en idioma {$params['language']}.

**REQUISITOS**:
- Tipo de artículo: {$params['article_style']}
- Longitud objetivo: {$params['target_words']} palabras
- Tono: {$params['tone_style']}
{$toc_instruction}
{$faq_instruction}

**ESTRUCTURA SEO**:
1. **Título H1**: Atractivo, incluye la keyword principal, máximo 60 caracteres
2. **Meta descripción**: Persuasiva, incluye keyword, 150-160 caracteres (incluir al inicio del artículo entre corchetes)
3. **Introducción**: Engancha al lector, presenta el problema/solución, incluye keyword en las primeras 100 palabras
4. **Cuerpo**: 
   - Usa subtítulos H2 y H3 con variaciones de la keyword
   - Párrafos cortos (3-4 líneas máximo)
   - Usa listas y bullets cuando sea apropiado
   - Incluye datos, estadísticas o ejemplos
5. **Conclusión**: Resume puntos clave y call-to-action

**FORMATO DE SALIDA**:
Devuelve el artículo en formato HTML válido con:
- Etiquetas de encabezado (h1, h2, h3)
- Párrafos (p)
- Listas (ul, ol, li)
- Negritas (strong) para términos importantes
- La meta descripción entre corchetes al inicio: [META: tu meta descripción aquí]

**IMPORTANTE**:
- El contenido debe ser 100% original y único
- Optimiza naturalmente para SEO sin keyword stuffing
- Asegura coherencia y fluidez en todo el texto
- Usa transiciones suaves entre secciones
PROMPT;

        return $prompt;
    }

    /**
     * Generar meta datos SEO
     */
    public function generate_meta($content, $keyword) {
        $prompt = <<<PROMPT
Analiza el siguiente contenido y genera meta datos SEO optimizados.

**KEYWORD PRINCIPAL**: {$keyword}

**CONTENIDO**:
{$content}

**GENERA**:
1. Meta título (50-60 caracteres, incluye keyword)
2. Meta descripción (150-160 caracteres, persuasiva, incluye keyword)
3. Slug URL optimizado (solo letras minúsculas, números y guiones)
4. 5 keywords secundarias relevantes

**FORMATO DE RESPUESTA** (JSON):
{
    "meta_title": "",
    "meta_description": "",
    "slug": "",
    "secondary_keywords": []
}
PROMPT;

        $result = $this->generate_content($prompt, ['temperature' => 0.3]);
        
        if (is_wp_error($result)) {
            return $result;
        }

        // Intentar parsear JSON
        $text = $result['text'];
        
        // Limpiar markdown si existe
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $meta = json_decode($text, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', __('Error al parsear la respuesta de meta datos.', 'seo-article-generator'));
        }

        return $meta;
    }

    /**
     * Sugerir enlaces internos
     */
    public function suggest_internal_links($content, $available_posts) {
        $posts_list = "";
        foreach ($available_posts as $post) {
            $posts_list .= "- ID: {$post->ID} | Título: {$post->post_title} | URL: " . get_permalink($post->ID) . "\n";
        }

        $prompt = <<<PROMPT
Analiza el siguiente contenido y sugiere enlaces internos relevantes de la lista de posts disponibles.

**CONTENIDO A ANALIZAR**:
{$content}

**POSTS DISPONIBLES PARA ENLAZAR**:
{$posts_list}

**INSTRUCCIONES**:
1. Identifica frases o términos en el contenido que serían buenos anchor texts
2. Relaciona cada anchor text con el post más relevante
3. Sugiere máximo 5 enlaces internos
4. El anchor text debe ser natural y contextual

**FORMATO DE RESPUESTA** (JSON array):
[
    {
        "anchor_text": "texto exacto del contenido a convertir en enlace",
        "target_post_id": 123,
        "relevance_reason": "breve explicación de por qué este enlace es relevante"
    }
]
PROMPT;

        $result = $this->generate_content($prompt, ['temperature' => 0.3]);
        
        if (is_wp_error($result)) {
            return $result;
        }

        $text = preg_replace('/```json\s*/', '', $result['text']);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $suggestions = json_decode($text, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $suggestions;
    }

    /**
     * Analizar contenido existente
     */
    public function analyze_content($content) {
        $prompt = <<<PROMPT
Analiza el siguiente contenido desde una perspectiva SEO y extrae información útil.

**CONTENIDO**:
{$content}

**EXTRAE**:
1. Keywords principales detectadas (máximo 10)
2. Puntuación de legibilidad (0-100)
3. Temas principales cubiertos
4. Sugerencias de mejora SEO
5. Temas relacionados no cubiertos que podrían complementar el contenido

**FORMATO DE RESPUESTA** (JSON):
{
    "keywords": ["keyword1", "keyword2"],
    "readability_score": 75,
    "main_topics": ["tema1", "tema2"],
    "seo_suggestions": ["sugerencia1", "sugerencia2"],
    "related_topics": ["tema relacionado 1", "tema relacionado 2"]
}
PROMPT;

        $result = $this->generate_content($prompt, ['temperature' => 0.3]);
        
        if (is_wp_error($result)) {
            return $result;
        }

        $text = preg_replace('/```json\s*/', '', $result['text']);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        return json_decode($text, true) ?: [];
    }
}
