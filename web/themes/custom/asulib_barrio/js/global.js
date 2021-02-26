/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function(context, settings) {
      $('[data-toggle="tooltip"]').tooltip({
        placement: 'top'
      });
      var position = $(window).scrollTop();
      $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
          $('body').addClass("scrolled");
        }
        else {
          $('body').removeClass("scrolled");
        }
        var scroll = $(window).scrollTop();
        if (scroll > position) {
          $('body').addClass("scrolldown");
          $('body').removeClass("scrollup");
        } else {
          $('body').addClass("scrollup");
          $('body').removeClass("scrolldown");
        }
        position = scroll;
      });

      var adjustSearchButton = function() {
        if ($(window).width() < 1261) {
          if (Drupal.url.toAbsolute().includes("prism")) {
            var sitename = "PRISM";
          } else {
            var sitename = "KEEP";
          }
          $('nav #search-form #edit-submit').val("Search " + sitename);
        } else {
          $('nav #search-form #edit-submit').val("Search");
        }
      }

      $(window).resize(function () {
        adjustSearchButton();
      });

      $(document).ready( function() {
        adjustSearchButton();
      })
    }
  };

})(jQuery, Drupal);
