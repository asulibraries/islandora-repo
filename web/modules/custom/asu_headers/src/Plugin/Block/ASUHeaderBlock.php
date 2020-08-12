<?php

namespace Drupal\asu_headers\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ASU Header' Block.
 *
 * @Block(
 *   id = "asu_header_block",
 *   admin_label = @Translation("ASU header"),
 *   category = @Translation("Views"),
 * )
 */
class ASUHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This calls a helper function that injects the part that goes above 
    // </head> as well as returns the output that goes at the top of the page 
    // under the <body> tag.
    $javascript_array = asu_brand_head_inject();
    // Set js settings, include js file, and inject head into <head>.
    return array(
      '#markup' => asu_brand_get_block_header(),

      '#attached' => array(
        'library' => array(
          array(
            'data' => $javascript_array['module_setting'],
            'scope' => 'header',
            'type' => 'inline',
          ),
          array(
            'data' => $javascript_array['inline_script'],
            'scope' => 'header',
            'type' => 'inline',
          ),
          array(
            'data' => $javascript_array['head_output'],
            'scope' => 'header',
            'type' => 'inline',
          ),
        ),
      )
    );
  }

}

/**
 * Build the content of the header block.
 */
function asu_brand_get_block_header() {
  $settings = asu_brand_get_block_settings();
  $cache_id = 'asu_brand:header';
  
  // Moved to a call from the build() function because the javascript needs to
  // be used in the block's theme array #attached values.
  //  Set js settings, include js file, and inject head into <head>.
  //  asu_brand_head_inject();
  
  return asu_brand_get_cached_content($cache_id, $settings->header_path);
}

/**
 * Build the content of the footer block.
 */
function asu_brand_get_block_footer() {
  $settings = asu_brand_get_block_settings();
  $cache_id = 'asu_brand:footer';
  return asu_brand_get_cached_content($cache_id, $settings->footer_path);
}

/**
 * Build the content of the students footer block.
 */
function asu_brand_get_block_students_footer() {
  $settings = asu_brand_get_block_settings();
  $cache_id = 'asu_brand:students_footer';
  return asu_brand_get_cached_content($cache_id, $settings->students_footer_path);
}

/**
 * Inject the head file into <head>. The order of the injections matter due to
 * how the includes are updating the header.
 */
function asu_brand_head_inject() {

  $overrides = ASU_BRAND_DO_NOT_OVERRIDE;
  $settings = asu_brand_get_block_settings();
  $cache_id = 'asu_brand:head';

  $settings->js_settings['overrides'] = $overrides;
  $head_output = asu_brand_get_cached_content($cache_id, $settings->head_path);

  // Inject header javascript into <head> and set the weight to a high negative value.
  $asu_sso_signedin = $settings->js_settings['asu_sso_signedin'];
  $asu_sso_signinurl = $settings->js_settings['asu_sso_signinurl'];
  $asu_sso_signouturl = $settings->js_settings['asu_sso_signouturl'];


  $inline_script = <<<EOL
   <script type="text/javascript">
    <!--//--><![CDATA[//><!--
    
    var overrides = $overrides;
    var hostname = window.location.hostname;
    var ASUHeader = ASUHeader || {};
    
    
    if (overrides.indexOf(hostname) == -1) {  
        ASUHeader.user_signedin = $asu_sso_signedin;
        ASUHeader.signout_url = "$asu_sso_signouturl";
    }
       
    //--><!]]>
  </script>
EOL;

  return array(
    'module_setting' => $settings->js_settings,
    'inline_script' => $inline_script,
    'head_output' => $head_output,      
  );
  //  drupal_add_js(array('asu_brand' => $settings->js_settings), 'setting');
  //  // This next addition is handled in the asu_headers.libraries.yml file.
  //  // drupal_add_js(drupal_get_path('module', 'asu_brand') . '/asu_brand.js', array());
  //  drupal_add_html_head(array('#type' => 'markup', '#markup' => $inline_script, '#weight' => -100), 'asu_brand_head_js');
  //  drupal_add_html_head(array('#type' => 'markup', '#markup' => $head_output, '#weight' => -99), 'asu_brand_head');
}