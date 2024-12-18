/**
 *  From https://boostrapcreative.com/pattern/toggle-show-hide-text-collapse-button
 *
 */
(function($, Drupal) {

  $(document).ready(function() {
    $('.text-show-more [data-toggle="collapse"]').click(function() {
      $(this).toggleClass("active");
      $(this).parent().find('.multi-collapse').collapse('toggle');
      if ($(this).hasClass("active")) {
        $(this).text("(less)");
      } else {
        $(this).text("(more)");
      }
    });
  });

})(jQuery, Drupal);

