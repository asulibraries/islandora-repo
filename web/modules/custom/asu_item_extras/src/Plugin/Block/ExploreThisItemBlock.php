<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'About this item' Block.
 *
 * @Block(
 *   id = "explore_this_item_block",
 *   admin_label = @Translation("Explore this item"),
 *   category = @Translation("Views"),
 * )
 */
class ExploreThisItemBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // depending on what the islandora_object model is, the links will differ.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }

    $field_model_tid = $node->get('field_model')->getString();
    $field_model_mappings = array(
        '22' => 'Audio',
        '23' => 'Binary',
        '24' => 'Collection',
        '27' => 'Digital Document',
        '25' => 'Image',
        '29' => 'Page',
        '28' => 'Paged Content',
        '30' => 'Publication Issue',
        '26' => 'Video');
    $field_model = (array_key_exists($field_model_tid, $field_model_mappings) ? 
      $field_model_mappings[$field_model_tid] : "");

    $output_links = array();
    if ($field_model == 'Image') {
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/openseadragon_view');
      $link = Link::fromTextAndUrl(t('View Image'), $url);
      // get the node's service file information from the node - just use the openseadragon view
      $link = $link->toRenderable();
      $output_links[] = render($link);
    } elseif ($field_model == 'Paged Content' || $field_model == 'Page' ||
      $field_model == 'Digital Document') {
      // "Start reading" and "Show all pages" links as well as a search box.
      // get the node's openseadragon viewer url.
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/openseadragon_view');
      $link = Link::fromTextAndUrl(t('Start reading'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/all_pages');
      $link = Link::fromTextAndUrl(t('Show all pages'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    $return = [
      '#cache' => ['max-age' => 0],
      '#markup' =>
        ((count($output_links) > 0) ?
        "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
        ""),
    ];
    if ($field_model == 'Paged Content') {
      $return['permalink'] = [
          '#type' => 'textfield',
          '#id' => 'permalink_about_editbox',
          '#attributes' => [
            'class' => array('disabled_small_prompt'),
            'readonly' => TRUE,
          ],
          '#value' => $url->toString(),
        ];
    }
    return $return;
  }

}