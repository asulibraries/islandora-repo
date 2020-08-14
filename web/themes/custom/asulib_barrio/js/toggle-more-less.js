/**
 *  From https://boostrapcreative.com/pattern/toggle-show-hide-text-collapse-button
 *
 */
(function($, Drupal) {

  $(document).ready(function() {
    $('[data-toggle="collapse"]').click(function() {
      $(this).toggleClass( "active" );
      if ($(this).hasClass("active")) {
        $(this).text("(less)");
      } else {
        $(this).text("(more)");
      }
    });
  });

})(jQuery, Drupal);