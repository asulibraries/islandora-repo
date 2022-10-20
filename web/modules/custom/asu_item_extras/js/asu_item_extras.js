/**
 * @file - asu_item_extras.js
 */

(function ($) {

Drupal.behaviors.asu_item_extras = {
  attach: function (context, settings) {
    // on click code to handle "Copy link" for iiif section.
    $('#copy_manifest_link', context).once('asu_item_extras').click(function () {
      try {
        var copy_from_box = $('#iiif_editbox');
        var url = copy_from_box.val();
        copyToClipboard(url);
        alert("Manifest URL \"" + url + "\" copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy manifest URL", err);
      }
      return;
    });
    // on click code to handle clipboard copy of "Permalink".
    $('.copy_permalink_link', context).once('asu_item_extras').click(function () {
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
    // on click code to handle clipboard copy of Citation
    $('#copy_citation', context).once('asu_item_extras').click(function () {
      try {
        var citation = $('#citation-text').text();
        copyToClipboard(citation);
        alert("Citation copied to clipboard.");
      } catch (err) {
        alert("Unable to copy the text with your browser.");
        console.error("Unable to copy citation", err);
      }
      return;
    });

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
