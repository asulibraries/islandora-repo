<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Feedback' Block.
 *
 * @Block(
 *   id = "asu_feedback_button",
 *   admin_label = @Translation("Feedback Button"),
 *   category = @Translation("Views"),
 * )
 */
class FeedbackButton extends BlockBase {
   /**
   * {@inheritdoc}
   */
  public function build() {
   $node = \Drupal::routeMatch()->getParameter('node');
   if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $url_base = \Drupal::request()->getSchemeAndHttpHost();
    $feedback_url = Url::fromUri($url_base . '/form/feedback?source_entity_type=node&source_entity_id=' . $nid . '&item=' . $nid);
    $link = Link::fromTextAndUrl(t('Feedback'), $feedback_url)->toRenderable();
    $markup = ['#markup' => render($link)];
    return $markup;
  }
}
