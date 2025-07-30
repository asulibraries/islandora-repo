<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Custom node redirect controller.
 */
class ItemViewController extends NodeViewController {

  /**
   * Determine which view mode to render.
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    if ($node->getType() == 'asu_repository_item') {
      if ($node->hasField('field_model') && !$node->get('field_model')->isEmpty()) {
        $model_term = $node->get('field_model')->referencedEntities()[0];
        $model = $model_term->getName();
        if ($model == 'Digital Document') {
          $view_mode = 'asu_document';
        }
        elseif ($model == 'Image') {
          $view_mode = 'asu_image';
        }
        elseif ($model == 'Video') {
          $view_mode = 'asu_video';
        }
        elseif ($model == 'Complex Object' || $model == 'Paged Content') {
          $view_mode = 'asu_complex_object';
        }
        elseif ($model == 'Audio') {
          $view_mode = 'asu_audio';
        }
        elseif ($model == 'Page' && !$node->field_member_of->isEmpty()) {
          return new RedirectResponse(Url::fromRoute('entity.node.canonical', [
            'node' => $node->field_member_of->target_id,
          ],
          [
            'query' => [
              'pageIdentifier' => $node->id(),
            ],
          ]
          )->toString());
        }
      }
      return parent::view($node, $view_mode, $langcode);
    }
    else {
      return parent::view($node, $view_mode, $langcode);
    }
  }

}
