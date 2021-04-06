<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ThumbnailController.
 */
class ThumbnailController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * Get the thumbnail.
   *
   * @return string
   *   Return the thumbnail.
   */
  public function getThumbnail() {
    $node = $this->currentRouteMatch->getParameter('node');
    if ($node) {
      $routeName = 'entity.node.canonical';
      $routeParameters = ['node' => $node->id()];
      // $url = Url::fromRoute($routeName, $routeParameters);
      $content_type = $node->bundle();
      if ($content_type == 'asu_repository_item') {
        $utils = \Drupal::service('islandora.utils');
        $thumbn_term = $utils->getTermForUri('http://pcdm.org/use#ThumbnailImage');

        // get the thumbnail
        $thumb_media = $utils->getMediaWithTerm($node, $thumbn_term);
        if ($thumb_media) {
          $file = $thumb_media->get('field_media_image')->entity;
          // $thumb_response = $this->entityTypeManager->getViewBuilder('file')->view(, 'full');
          // $file_uri = file_create_url($file->getFileUri());
          // $file_uri = $file->createFileUrl();
          // return (new TrustedRedirectResponse($file_uri))
          // ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));        }
          $url = file_create_url($file->getFileUri());
          if (str_contains($url, "keep.lib") || str_contains($url, "prism.lib")) {
            $response = new RedirectResponse($url);
          } else {
            $response = (new TrustedRedirectResponse($url))->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
          }
          return $response;
        }
        else {

          if ($node->get('field_model') != NULL && count($node->get('field_model')) > 0 && $node->get('field_model')->referencedEntities()[0]->getName() == "Complex Object") {
            $complex_obj_child = $this->entityTypeManager->getStorage('node')->loadByProperties(['field_member_of' => $node->id()]);
            foreach ($complex_obj_child as $child) {
              $thumb_media = $utils->getMediaWithTerm($child, $thumbn_term);
              if ($thumb_media) {
                $file = $thumb_media->get('field_media_image')->entity;
                // $file_uri = file_create_url($file->getFileUri());
                // $file_uri = $file->createFileUrl();
                // return (new TrustedRedirectResponse($file_uri))
                // ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
                $url = file_create_url($file->getFileUri());
                if (str_contains($url, "keep.lib") || str_contains($url, "prism.lib")) {
                  $response = new RedirectResponse($url);
                }
                else {
                  $response = (new TrustedRedirectResponse($url))->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
                }
                return $response;
              }
            }
          }
          throw new NotFoundHttpException();
        }
      }
      else {
        throw new NotFoundHttpException();
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
