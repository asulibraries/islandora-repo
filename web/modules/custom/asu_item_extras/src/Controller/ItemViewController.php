<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\views\Views;

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
        // We check to ensure the Mirador viewer will have something to display
        // before redirecting.
        elseif ($model == 'Page' && !$node->field_member_of->isEmpty() && $node->isPublished() && $node->field_member_of->entity->isPublished() && $this->checkIiifAccess($node->field_member_of->entity)) {
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

  /**
   * Check to see if a node has any IIIF components accessible.
   */
  private function checkIiifAccess(EntityInterface $node) {

    $view = Views::getView('iiif_manifest');
    $view->setDisplay('rest_export_1');
    $view->setArguments([$node->id()]);
    $view->execute();
    $results = $view->result;
    if (count($results) > 0) {
      return TRUE;
    }
    \Drupal::logger('asu_item_extras')->notice('Node %node_id has no IIIF components available.', [
      '%node_id' => $node->id(),
    ]);
    return FALSE;
  }

}
