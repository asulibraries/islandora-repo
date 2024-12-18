/**
 * @file - asu_collection_extras.js
 */

(function (Drupal, once) {

Drupal.behaviors.asu_collection_extras = {
  attach(context) {
    // on click code to handle clipboard copy of "Permalink".
    once('asu_collection_extras', '.copy_permalink_link', context).forEach(function (element) { element.onclick = function () {
      try {
        // this value is stored on the span's title attribute.
        let url = element.getAttribute("title");
        copyToClipboard(url);
        alert("Permalink URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy permalink URL", err);
      }
      return;
    }});
  }
};

}(Drupal, once));

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
