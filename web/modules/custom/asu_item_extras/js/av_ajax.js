/** Loads the video_media_evas:smallest into a div. */

(function ($) {
    Drupal.behaviors.ajaxViewDemo = {
      attach: function (context, settings) {
        // Attach ajax action click event of each view column.
        $('.av_ajax_player').once('attach-player').each(this.attachPlayer);
      },
   
      attachPlayer: function (idx, column) {
   
        // Pull node ID from div.
        var nid = column.getAttribute('nid');

   
        // Everything we need to specify about the view.
        var view_info = {
          view_name: 'video_media_evas',
          view_display_id: 'smallest',
          view_args: nid,
          view_dom_id: 'ajax-demo'
        };
   
        // Details of the ajax action.
        var ajax_settings = {
          submit: view_info,
          url: '/views/ajax',
          element: column,
          event: 'click'
        };
   
        Drupal.ajax(ajax_settings);
      }
    };
  })(jQuery);
  
  