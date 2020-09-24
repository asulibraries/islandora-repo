<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Cite this collection' Block.
 *
 * @Block(
 *   id = "asu_collection_search",
 *   admin_label = @Translation("Search this collection"),
 *   category = @Translation("Views"),
 * )
 */
class ASUCollectionsSearch extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The links within this block should be:
     *  - link to Browse this collection
     *  - Responsibilities of use
     *  - Licensing and Permissions
     *  - Linking and Embedding
     *  - Copies and Reproductions
     */
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", or possibly "Collection", the underlying
    // node can be accessed via the path.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $node_url = Url::fromRoute('<current>', array());
    $url_string = \Drupal::request()->getSchemeAndHttpHost() . $node_url->toString();
    $output_links = array();
    $url = Url::fromUri($url_string . '/citation/#citing');
    $link = Link::fromTextAndUrl(t('Citing this collection'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#responsibilities');
    $link = Link::fromTextAndUrl(t('Responsibilities of use'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#licensing');
    $link = Link::fromTextAndUrl(t('Licensing and Permissions'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#linking');
    $link = Link::fromTextAndUrl(t('Linking and Embedding'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#copies');
    $link = Link::fromTextAndUrl(t('Copies and Reproductions'), $url)->toRenderable();
    $output_links[] = render($link);
    $render_this = [
      '#markup' =>
        ((count($output_links) > 0) ?
          "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
          ""),
    ];
    return [
      'citations-container' => [
        '#type' => 'item',
        '#id' => 'citations_box',
        'container' => [
          '#type' => 'container',
          'the-items' => [
            '#type' => 'item',
            $render_this
          ]]]];
  }
}
