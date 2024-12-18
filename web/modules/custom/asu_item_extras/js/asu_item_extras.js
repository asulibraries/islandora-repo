/**
 * @file - asu_item_extras.js
 */

(function (Drupal, once) {

Drupal.behaviors.asu_item_extras = {
  attach(context) {
    // on click code to handle "Copy link" for iiif section.
    once('asu_item_extras', '#copy_manifest_link', context).forEach(function(element) { element.onclick = function () {
      try {
        let url = document.getElementById('iiif_editbox').value;
        copyToClipboard(url);
        alert("Manifest URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy manifest URL", err);
      }
      return;
    }});
    // on click code to handle clipboard copy of "Permalink".
    once('asu_item_extras','.copy_permalink_link', context).forEach(function(element) { element.onclick = function (element) {
      try {
        // this value is stored on the span's title attribute.
        let url = element.target.getAttribute("title");
        copyToClipboard(url);
        alert("Permalink URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy permalink URL", err);
      }
      return;
    }});
    // on click code to handle clipboard copy of Citation
    once('asu_item_extras', '#copy_citation', context).forEach(function(element) { element.onclick = function () {
      try {
	let citation = document.getElementById("citation-text").textContent;
        copyToClipboard(citation);
        alert("Citation copied to clipboard: " + citation);
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy citation", err);
      }
      return;
    }});

    var video = document.getElementsByTagName('video')[0];
    if (video) {
      var tracks = video.textTracks; // returns a TextTrackList
      if (tracks) {
        var track = tracks[0]; // returns TextTrack
        if (track) {
          track.activeCues[0].line = 1;
          track.activeCues[0].align = "start";
        }
      }
    }
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
