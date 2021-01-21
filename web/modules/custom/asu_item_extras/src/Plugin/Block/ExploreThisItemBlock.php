<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\taxonomy\Entity\Term;

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
    // Depending on what the islandora_object model is, the links will differ.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    }
    else {
      $nid = 0;
    }

    $field_model_tid = $node->get('field_model')->getString();
    $field_model_term = Term::load($field_model_tid);
    $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
      $field_model_term->getName() : '';

    $output_links = [];
    if ($field_model == 'Image') {
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' . $nid . '/view');
      $link = Link::fromTextAndUrl($this->t('View Image'), $url);
      // Get the node's service file information from the node - just use the
      // openseadragon view.
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    elseif ($field_model == 'Complex Object') {
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' . $nid . '/members');
      $link = Link::fromTextAndUrl($this->t('View all associated media'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    elseif ($field_model == 'Paged Content' || $field_model == 'Page' ||
      $field_model == 'Digital Document') {
      // "Start reading" and "Show all pages" links as well as a search box.
      // get the node's openseadragon viewer url.
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' . $nid . '/view');
      $link = Link::fromTextAndUrl($this->t('Explore Document'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    $return = [
      '#cache' => ['max-age' => 0],
      '#markup' =>
      ((count($output_links) > 0) ?
        "<nav><ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul></nav>" :
        ""),
    ];
    return $return;
  }

}
