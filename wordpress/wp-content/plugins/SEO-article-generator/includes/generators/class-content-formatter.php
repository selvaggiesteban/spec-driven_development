<?php
/**
 * Formateador de contenido
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAG_Content_Formatter {

    /**
     * Formatear contenido generado
     */
    public function format($content) {
        // Limpiar contenido
        $content = $this->clean_content($content);

        // Asegurar estructura HTML válida
        $content = $this->ensure_valid_html($content);

        // Añadir clases CSS si es necesario
        $content = $this->add_styling_classes($content);

        // Procesar tabla de contenidos si existe
        $content = $this->process_toc($content);

        return $content;
    }

    /**
     * Limpiar contenido
     */
    private function clean_content($content) {
        // Eliminar code fences de markdown (```html, ```, etc.)
        $content = $this->remove_code_fences($content);

        // Eliminar markdown residual si existe
        $content = $this->convert_markdown_to_html($content);

        // Eliminar espacios duplicados
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

        // Eliminar comentarios HTML
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        return trim($content);
    }

    /**
     * Eliminar code fences de markdown
     */
    private function remove_code_fences($content) {
        // Eliminar code fences de bloque con lenguaje especificado: ```html, ```php, etc.
        $content = preg_replace('/^```[a-z]*\s*$/m', '', $content);

        // Eliminar code fences simples: ```
        $content = preg_replace('/^```\s*$/m', '', $content);

        return $content;
    }

    /**
     * Convertir markdown a HTML si es necesario
     */
    private function convert_markdown_to_html($content) {
        // Si el contenido ya es HTML, devolverlo
        if (preg_match('/<(p|h[1-6]|div|ul|ol)[^>]*>/i', $content)) {
            return $content;
        }

        // Convertir headings markdown
        $content = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $content);
        $content = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $content);
        $content = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $content);
        $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $content);

        // Convertir negritas
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $content);

        // Convertir cursivas
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/_(.+?)_/', '<em>$1</em>', $content);

        // Convertir listas no ordenadas
        $content = preg_replace('/^[\*\-]\s+(.+)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $content);

        // Convertir listas ordenadas
        $content = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $content);
        
        // Envolver párrafos
        $lines = explode("\n", $content);
        $result = [];
        $in_list = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Si no es un elemento HTML, envolverlo en <p>
            if (!preg_match('/^<(h[1-6]|p|ul|ol|li|div|blockquote|table|figure)/i', $line)) {
                if (!preg_match('/<\/(h[1-6]|p|ul|ol|div|blockquote|table|figure)>$/i', $line)) {
                    $line = '<p>' . $line . '</p>';
                }
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Asegurar HTML válido
     */
    private function ensure_valid_html($content) {
        // Cerrar tags abiertos
        $tags = ['p', 'div', 'span', 'ul', 'ol', 'li', 'strong', 'em', 'a'];
        
        foreach ($tags as $tag) {
            $open_count = preg_match_all("/<{$tag}[^>]*>/i", $content);
            $close_count = preg_match_all("/<\/{$tag}>/i", $content);
            
            $diff = $open_count - $close_count;
            if ($diff > 0) {
                $content .= str_repeat("</{$tag}>", $diff);
            }
        }

        return $content;
    }

    /**
     * Añadir clases de estilo
     */
    private function add_styling_classes($content) {
        // Añadir clases a tablas
        $content = preg_replace('/<table(?![^>]*class=)([^>]*)>/', '<table class="sag-table"$1>', $content);

        // Añadir clases a blockquotes
        $content = preg_replace('/<blockquote(?![^>]*class=)([^>]*)>/', '<blockquote class="sag-quote"$1>', $content);

        return $content;
    }

    /**
     * Procesar tabla de contenidos
     */
    private function process_toc($content) {
        // Buscar si hay marcador de TOC
        if (strpos($content, '[TOC]') === false && strpos($content, '<!-- TOC -->') === false) {
            return $content;
        }

        // Extraer headings
        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return str_replace(['[TOC]', '<!-- TOC -->'], '', $content);
        }

        // Generar TOC
        $toc_html = '<nav class="sag-toc"><h2>' . __('Tabla de Contenidos', 'seo-article-generator') . '</h2><ul>';
        $counter = 0;

        foreach ($matches as $match) {
            $level = $match[1];
            $text = wp_strip_all_tags($match[2]);
            $slug = sanitize_title($text);
            $counter++;
            $anchor = $slug . '-' . $counter;

            // Añadir ID al heading en el contenido
            $old_heading = $match[0];
            $new_heading = "<h{$level} id=\"{$anchor}\">{$match[2]}</h{$level}>";
            $content = str_replace($old_heading, $new_heading, $content);

            // Añadir al TOC
            $indent = $level == '3' ? ' class="sag-toc-sub"' : '';
            $toc_html .= "<li{$indent}><a href=\"#{$anchor}\">{$text}</a></li>";
        }

        $toc_html .= '</ul></nav>';

        // Reemplazar marcador con TOC
        $content = str_replace(['[TOC]', '<!-- TOC -->'], $toc_html, $content);

        return $content;
    }

    /**
     * Generar excerpt
     */
    public function generate_excerpt($content, $length = 55) {
        $text = wp_strip_all_tags($content);
        return wp_trim_words($text, $length, '...');
    }

    /**
     * Sanitizar contenido para guardado
     */
    public function sanitize_for_save($content) {
        // Usar el mismo filtrado que WordPress para post_content
        return wp_kses_post($content);
    }

    /**
     * Extraer FAQ schema data
     */
    public function extract_faq_schema($content) {
        $faqs = [];

        // Buscar sección de FAQ
        if (preg_match('/<h[2-3][^>]*>.*?(?:FAQ|Preguntas Frecuentes).*?<\/h[2-3]>(.*?)(?=<h[2-3]|$)/is', $content, $section)) {
            $faq_content = $section[1];

            // Extraer preguntas y respuestas
            preg_match_all('/<h[3-4][^>]*>(.*?)<\/h[3-4]>\s*<p[^>]*>(.*?)<\/p>/is', $faq_content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $faqs[] = [
                    'question' => wp_strip_all_tags($match[1]),
                    'answer' => wp_strip_all_tags($match[2]),
                ];
            }
        }

        return $faqs;
    }
}
