/**
 * SEO Article Generator - Admin Scripts
 */

(function ($) {
  "use strict";

  // Esperar a que el DOM esté listo
  $(document).ready(function () {
    initTabs();
    initGenerator();
    initAnalyzer();
    initLinker();
    initScheduler();
    initSettings();
  });

  /**
   * Inicializar tabs
   */
  function initTabs() {
    $(".sag-tab").on("click", function () {
      var tab = $(this).data("tab");

      // Actualizar tabs activos
      $(this).siblings().removeClass("active");
      $(this).addClass("active");

      // Mostrar contenido correspondiente
      $(this).closest(".wrap").find(".sag-tab-content").hide();
      $("#tab-" + tab).show();
    });
  }

  /**
   * Inicializar generador
   */
  function initGenerator() {
    var $form = $("#sag-generator-form");
    var $progressModal = $("#sag-progress-modal");
    var $resultModal = $("#sag-result-modal");

    if (!$form.length) return;

    $form.on("submit", function (e) {
      e.preventDefault();
      generateArticle();
    });

    // Cerrar modal de resultado
    $resultModal
      .find(".sag-modal-close, .sag-new-article")
      .on("click", function () {
        $resultModal.hide();
        if ($(this).hasClass("sag-new-article")) {
          $form[0].reset();
        }
      });

    function generateArticle() {
      var formData = $form.serialize();

      $progressModal.show();
      updateProgress("Conectando con Gemini API...");

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: formData + "&action=sag_generate_article&nonce=" + sagAdmin.nonce,
        success: function (response) {
          $progressModal.hide();

          if (response.success) {
            showResult(response.data);
          } else {
            showError((response.data && response.data.message) || sagAdmin.strings.error);
          }
        },
        error: function () {
          $progressModal.hide();
          showError(sagAdmin.strings.error);
        },
      });
    }

    function updateProgress(message) {
      $("#sag-progress-message").text(message);
    }

    function showResult(data) {
      $("#result-words").text(data.word_count.toLocaleString());
      $("#result-seo").text(data.seo_score + "%");
      $("#result-edit-link").attr("href", data.edit_url);
      $("#result-preview-link").attr("href", data.preview_url);
      $resultModal.show();
    }

    function showError(message) {
      alert(message);
    }
  }

  /**
   * Inicializar analizador
   */
  function initAnalyzer() {
    var $analyzeBtn = $("#sag-analyze-site");
    var $results = $("#sag-analysis-results");
    var $loader = $("#sag-analyzer-loader");

    if (!$analyzeBtn.length) return;

    $analyzeBtn.on("click", function () {
      analyzeSite();
    });

    function analyzeSite() {
      $analyzeBtn.prop("disabled", true);
      $results.hide();
      $loader.show();

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_analyze_content",
          nonce: sagAdmin.nonce,
        },
        success: function (response) {
          $loader.hide();
          $analyzeBtn.prop("disabled", false);

          if (response.success) {
            displayAnalysisResults(response.data);
          } else {
            alert(response.data.message || sagAdmin.strings.error);
          }
        },
        error: function () {
          $loader.hide();
          $analyzeBtn.prop("disabled", false);
          alert(sagAdmin.strings.error);
        },
      });
    }

    function displayAnalysisResults(data) {
      // Actualizar resumen
      $("#analyzed-posts").text(data.analyzed_posts || 0);
      $("#total-keywords").text(Object.keys(data.keywords_map || {}).length);
      $("#orphan-pages").text((data.link_structure && data.link_structure.orphan_pages && data.link_structure.orphan_pages.length) || 0);
      $("#topic-suggestions").text((data.suggestions && data.suggestions.length) || 0);

      // Keywords
      var $keywordsCloud = $("#keywords-cloud").empty();
      var $keywordsTable = $("#keywords-table tbody").empty();

      $.each(data.keywords_map, function (keyword, count) {
        $keywordsCloud.append(
          '<span class="sag-keyword-tag">' + keyword + " (" + count + ")</span>"
        );
        $keywordsTable.append(
          "<tr>" +
            "<td>" +
            keyword +
            "</td>" +
            "<td>" +
            count +
            "</td>" +
            '<td><button class="button button-small sag-generate-from-keyword" data-keyword="' +
            keyword +
            '">Generar artículo</button></td>' +
            "</tr>"
        );
      });

      // Gaps
      var $gapsList = $("#gaps-list").empty();
      $.each(data.topic_gaps, function (i, gap) {
        $gapsList.append(
          "<li><strong>" + gap.topic + "</strong>: " + gap.suggestion + "</li>"
        );
      });

      // Suggestions
      var $suggestionsList = $("#suggestions-list").empty();
      $.each(data.suggestions, function (i, suggestion) {
        $suggestionsList.append(
          '<div class="sag-suggestion-card">' +
            "<h4>" +
            suggestion.topic +
            "</h4>" +
            "<p>" +
            suggestion.reason +
            "</p>" +
            '<button class="button button-small sag-generate-from-suggestion" data-topic="' +
            suggestion.topic +
            '" data-keyword="' +
            suggestion.keyword +
            '">Generar</button>' +
            "</div>"
        );
      });

      // Links structure
      $("#avg-internal-links").text(data.link_structure.avg_internal_links);

      var $orphanList = $("#orphan-list").empty();
      $.each(data.link_structure.orphan_pages || [], function (i, page) {
        $orphanList.append(
          '<div class="sag-orphan-item">' +
            '<a href="' +
            page.url +
            '" target="_blank">' +
            page.title +
            "</a>" +
            "</div>"
        );
      });

      var $mostLinked = $("#most-linked-table tbody").empty();
      $.each(data.link_structure.most_linked || [], function (i, page) {
        $mostLinked.append(
          '<tr><td><a href="' +
            page.url +
            '">' +
            page.title +
            "</a></td><td>" +
            page.incoming_links +
            "</td></tr>"
        );
      });

      $results.show();
    }
  }

  /**
   * Inicializar gestor de enlaces
   */
  function initLinker() {
    var $postSelect = $("#post-select");
    var $analyzeBtn = $("#analyze-links-btn");
    var $results = $("#link-suggestions-results");

    if (!$postSelect.length) return;

    $postSelect.on("change", function () {
      $analyzeBtn.prop("disabled", !$(this).val());
    });

    $analyzeBtn.on("click", function () {
      getLinkSuggestions($postSelect.val());
    });

    function getLinkSuggestions(postId) {
      $analyzeBtn.prop("disabled", true).text("Analizando...");

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_get_link_suggestions",
          nonce: sagAdmin.nonce,
          post_id: postId,
        },
        success: function (response) {
          $analyzeBtn
            .prop("disabled", false)
            .html(
              '<span class="dashicons dashicons-search"></span> Obtener Sugerencias'
            );

          if (response.success) {
            displayLinkSuggestions(response.data);
          } else {
            alert(response.data.message || sagAdmin.strings.error);
          }
        },
        error: function () {
          $analyzeBtn
            .prop("disabled", false)
            .html(
              '<span class="dashicons dashicons-search"></span> Obtener Sugerencias'
            );
          alert(sagAdmin.strings.error);
        },
      });
    }

    function displayLinkSuggestions(suggestions) {
      var $tbody = $("#suggestions-table tbody").empty();

      if (!suggestions.length) {
        $tbody.append(
          '<tr><td colspan="5">No se encontraron sugerencias de enlaces.</td></tr>'
        );
        $results.show();
        return;
      }

      $.each(suggestions, function (i, s) {
        $tbody.append(
          '<tr data-id="' +
            (s.id || i) +
            '">' +
            '<td><input type="checkbox" class="suggestion-check"></td>' +
            "<td><strong>" +
            s.anchor_text +
            "</strong></td>" +
            '<td><a href="' +
            s.target_url +
            '" target="_blank">' +
            s.target_title +
            "</a></td>" +
            '<td class="sag-context">' +
            (s.context || "") +
            "</td>" +
            "<td>" +
            '<button class="button button-small sag-apply-link">Aplicar</button> ' +
            '<button class="button button-small sag-ignore-link">Ignorar</button>' +
            "</td>" +
            "</tr>"
        );
      });

      $results.show();
    }

    // Aplicar enlace individual
    $(document).on("click", ".sag-apply-link", function () {
      var $row = $(this).closest("tr");
      var suggestionId = $row.data("id");

      $(this).prop("disabled", true).text("Aplicando...");

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_apply_link",
          nonce: sagAdmin.nonce,
          suggestion_id: suggestionId,
        },
        success: function (response) {
          if (response.success) {
            $row.fadeOut(function () {
              $(this).remove();
            });
          } else {
            alert(response.data.message || sagAdmin.strings.error);
            $row.find(".sag-apply-link").prop("disabled", false).text("Aplicar");
          }
        },
        error: function () {
          alert(sagAdmin.strings.error);
          $row.find(".sag-apply-link").prop("disabled", false).text("Aplicar");
        },
      });
    });

    // Ignorar enlace individual
    $(document).on("click", ".sag-ignore-link", function () {
      var $row = $(this).closest("tr");
      var suggestionId = $row.data("id");

      $(this).prop("disabled", true).text("Ignorando...");

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_ignore_link",
          nonce: sagAdmin.nonce,
          suggestion_id: suggestionId,
        },
        success: function (response) {
          if (response.success) {
            $row.fadeOut(function () {
              $(this).remove();
            });
          } else {
            alert(response.data.message || sagAdmin.strings.error);
            $row.find(".sag-ignore-link").prop("disabled", false).text("Ignorar");
          }
        },
        error: function () {
          alert(sagAdmin.strings.error);
          $row.find(".sag-ignore-link").prop("disabled", false).text("Ignorar");
        },
      });
    });
  }

  /**
   * Inicializar programador
   */
  function initScheduler() {
    // Publicar ahora
    $(document).on("click", ".sag-publish-now", function () {
      var $btn = $(this);
      var postId = $btn.data("post-id");

      if (!confirm("¿Publicar este artículo ahora?")) return;

      $btn.prop("disabled", true).text("Publicando...");

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_publish_now",
          nonce: sagAdmin.nonce,
          post_id: postId,
        },
        success: function (response) {
          if (response.success) {
            $btn.closest("tr").fadeOut(function () {
              $(this).remove();
            });
          } else {
            $btn.prop("disabled", false).text("Publicar Ahora");
            alert(response.data.message || sagAdmin.strings.error);
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Publicar Ahora");
          alert(sagAdmin.strings.error);
        },
      });
    });

    // Cancelar programación
    $(document).on("click", ".sag-unschedule", function () {
      var $btn = $(this);
      var postId = $btn.data("post-id");

      if (!confirm("¿Cancelar la programación de este artículo?")) return;

      $btn.prop("disabled", true);

      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_unschedule",
          nonce: sagAdmin.nonce,
          post_id: postId,
        },
        success: function (response) {
          if (response.success) {
            $btn.closest("tr").fadeOut(function () {
              $(this).remove();
            });
          } else {
            $btn.prop("disabled", false);
            alert(response.data.message || sagAdmin.strings.error);
          }
        },
        error: function () {
          $btn.prop("disabled", false);
          alert(sagAdmin.strings.error);
        },
      });
    });
  }

  /**
   * Inicializar configuración
   */
  function initSettings() {
    var $testBtn = $("#test-api-btn");

    if (!$testBtn.length) return;

    $testBtn.on("click", function () {
      var $btn = $(this);
      var apiKey = $("#sag_api_key").val();

      if (!apiKey) {
        alert("Por favor, introduce una API Key.");
        return;
      }

      $btn.prop("disabled", true).text("Probando...");

      // Guardar primero y luego probar
      $.ajax({
        url: sagAdmin.ajaxUrl,
        type: "POST",
        data: {
          action: "sag_test_api",
          nonce: sagAdmin.nonce,
        },
        success: function (response) {
          $btn.prop("disabled", false).text("Probar Conexión");

          if (response.success) {
            alert("✓ " + response.data.message);
          } else {
            alert("✗ " + (response.data.message || "Error de conexión"));
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Probar Conexión");
          alert("Error al probar la conexión.");
        },
      });
    });
  }
})(jQuery);
