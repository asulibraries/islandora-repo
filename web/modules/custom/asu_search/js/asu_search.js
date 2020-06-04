 /**
 * @file - asu_search.js
 */

(function ($) {

Drupal.behaviors.asu_search = {
  attach: function (context, settings) {
    // on click code to handle "Copy link" for iiif section.
    $('#copy_manifest_link', context).once('asu_search').click(function () {
    //  $('#copy_manifest_link').click(function(){
      $('#iiif_editbox').focus();
      $('#iiif_editbox').select();
      try {
        document.execCommand('copy');
        var url = $('#iiif_editbox').val();
        alert("Manifest URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy manifest URL", err);
      }
      return;
    });
    // on click code to handle clipboard copy of "Permalink".
    $('.copy_permalink_link', context).once('asu_search').click(function () {
      // If called from the "About this item" block, the component to copy from
      // will be "permalink_about_editbox".
      var copy_from_box = $('#permalink_about_editbox');
      if (!copy_from_box) {
        copy_from_box = $('#permalink_interact_editbox');
      }
      copy_from_box.focus();
      copy_from_box.select();
      try {
        document.execCommand('copy');
        var url = copy_from_box.val();
        alert("Permalink URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy permalink URL", err);
      }
      return;
    });
  }
};

})(jQuery, Drupal);