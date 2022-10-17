/**
 * @file - asu_collection_extras.js
 */

(function ($) {

Drupal.behaviors.asu_collection_extras = {
  attach: function (context, settings) {
    // on click code to handle clipboard copy of "Permalink".
    $('.copy_permalink_link', context).once('asu_collection_extras').click(function () {
      try {
        // this value is stored on the span's title attribute.
        var url = $(this).attr("title");
        copyToClipboard(url);
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

function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    // to avoid breaking orgain page when copying more words
    // cant copy when adding below this code
    // dummy.style.display = 'none'
    document.body.appendChild(dummy);
    //Be careful if you use texarea. setAttribute('value', value), which works with "input" does not work with "textarea". – Eduard
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
document.body.removeChild(dummy);
}
