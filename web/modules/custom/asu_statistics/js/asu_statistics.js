/**
 * @file - asu_statistics.js
 */

(function ($) {
Drupal.behaviors.asu_statistics = {
  attach: function (context, settings) {
    // Since any of these would require the _paq library is defined -- and that
    // depends on whether or not the user is configured to track Matomo events
    // i.e. "do not add tracking for Admin role", this logic can wrap any
    // further logic.
    if (typeof _paq !== 'undefined') {
      var page_pathname = window.location.pathname;
      // remove leading "/" character.
      page_pathname = page_pathname.substring(1, page_pathname.length);
      $('audio').each( function () {
          $(this, context).once('asu_statistics').on("play", function () {
              var article = $(this).closest("article");
              // Check if this node is the child of a complex object.
              var article_node_id = article.attr('data-history-node-id');
              if (article_node_id) {
                  page_pathname = 'items/' + article_node_id;
              }
              // Push the tracking event to Matomo.
              _paq.push(['trackEvent', 'MediaEvents', 'Play audio', page_pathname]);
          });
      });
      $('video').each( function () {
          $(this, context).once('asu_statistics').on("play", function () {
              var article = $(this).closest("article");
              var article_node_id = article.attr('data-history-node-id');
              // Check if this node is the child of a complex object.
              if (article_node_id) {
                   page_pathname = 'items/' + article_node_id;
              }
              // Push the tracking event to Matomo.
              _paq.push(['trackEvent', 'MediaEvents', 'Play video', page_pathname]);
          });
      });
    }
  }
};

})(jQuery, Drupal);
