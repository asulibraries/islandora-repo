<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'file_video_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "file_video_caption",
 *   label = @Translation("Video with Caption"),
 *   description = @Translation("Display the file using an HTML5 video tag and caption track."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileVideoCaptionFormatter extends FileVideoFormatter {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $utils = \Drupal::service('islandora.utils');

    $source_files = $this->getSourceFiles($items, $langcode);
    if (empty($source_files)) {
      return $elements;
    }

    $attributes = $this->prepareAttributes();
    foreach ($source_files as $delta => $files) {
      $file = $files[0]['file'];
      $medias = \Drupal::service('islandora.utils')->getReferencingMedia($file->id());
      $first_media = array_values($medias)[0];
      if ($first_media->get('field_captions')->entity != NULL) {
        $caption = $first_media->get('field_captions')->entity->url();
      }
      $node = $utils->getParentNode($first_media);
      $thumbn_term = $utils->getTermForUri('http://pcdm.org/use#ThumbnailImage');
      $thumb_media = $utils->getMediaWithTerm($node, $thumbn_term);
      if ($thumb_media) {
        $poster = $thumb_media->get('field_media_image')->entity->url();
      }

      $elements[$delta] = [
        '#theme' => 'file_video_with_caption',
        '#attributes' => $attributes,
        '#files' => $files,
        '#cache' => ['tags' => []],
      ];

      if (isset($caption)) {
        $elements[$delta]['#caption'] = $caption;
      }
      if (isset($poster)) {
        $elements[$delta]['#poster'] = $poster;
      }

      $cache_tags = [];
      foreach ($files as $file) {
        $cache_tags = Cache::mergeTags($cache_tags, $file['file']->getCacheTags());
      }
      $elements[$delta]['#cache']['tags'] = $cache_tags;
    }
    return $elements;
  }

}
