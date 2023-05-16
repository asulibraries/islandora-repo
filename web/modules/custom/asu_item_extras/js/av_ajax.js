/** 
 * Attaches an on-click event allowing us to load the video_media_evas:smallest
 * view into a div with the classes "av_ajax_player" and "js-view-dom-id-av-player-{{nid}}"
 * with the node's ID in the data-nid attribute.
 * E.g. `<div class='av_ajax_player js-view-dom-id-av-player-{{nid}}' data-nid={{nid}}>PLAYER!</div>`
 */

(function (Drupal, once) {
  Drupal.behaviors.ajaxViewDemo = {
    attach(context) {
      once('attach-player', '.av_ajax_player', context).forEach(
        element => {
          attachPlayer(element);
        }
      );
    },
  };

  function attachPlayer(element) {
    // Pull node ID from div.
    var nid = element.attributes['data-nid'].value;

    // Everything we need to specify about the view.
    var view_info = {
      view_name: 'video_media_evas',
      view_display_id: 'smallest',
      view_args: nid,
      view_dom_id: 'av-player-' + nid
    };

    // Details of the ajax action.
    var ajax_settings = {
      submit: view_info,
      url: '/views/ajax',
      element: element,
      event: 'click'
    };

    // Have drupal/ajax attach the on-click event.
    Drupal.ajax(ajax_settings);
  }
}(Drupal, once));