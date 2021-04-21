 /**
 * @file - asu_collection_extras.js
 */

(function ($) {
    $(document).ready(function(){
//        alert(context);    // [object HTMLUListElement]
//        alert(settings);   // [object Object]
        // var p_parts = settings.path.currentPath.split('/');
        // create a URL that will trigger the PHP code to perform the heavy lifting
        // for this node.
        var this_uri = window.location.href;
        var uri_parts = this_uri.split('/');
        var node_id = '';
        var s = '';
        var build_call_uri = '';
        for (i = 0; i < uri_parts.length; i++) {
            x = parseInt(uri_parts[i]);
            s = uri_parts[i].split('?');
            if (parseInt(s[0]) == s[0]) {
                node_id = s[0];
            }
            if (build_call_uri != '') {
                build_call_uri = build_call_uri + "/" + s[0];
            } else {
                build_call_uri = s[0];
            }
        }
        if (node_id != '') {
            build_call_uri = build_call_uri + "/matomosync";
//            build_call_uri = '/items/5623/matomosync';
            alert('call ' + build_call_uri);
            var settings = [];
            $.ajax(build_call_uri, {
                success: function(data) {
                    alert('success');
            },
                error: function() {
                    alert('error');
                }
              });
            }
        });            
    })
(jQuery, Drupal);