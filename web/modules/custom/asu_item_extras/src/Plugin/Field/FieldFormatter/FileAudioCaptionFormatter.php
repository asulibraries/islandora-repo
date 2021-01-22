<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Cache\Cache;
use Drupal\islandora\IslandoraUtils;

/**
 * Plugin implementation of the 'file_audio_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "file_audio_caption",
 *   label = @Translation("audio with Caption"),
 *   description = @Translation("Display the file using an HTML5 audio tag and caption track."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAudioCaptionFormatter extends FileAudioFormatter {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'audio';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $source_files = $this->getSourceFiles($items, $langcode);
    if (empty($source_files)) {
      return $elements;
    }

    $attributes = $this->prepareAttributes();
    foreach ($source_files as $delta => $files) {
      $file = $files[0]['file'];
      $medias = $this->islandora_utils->getReferencingMedia($file->id());
      $first_media = array_values($medias)[0];
      if ($first_media->get('field_captions')->entity != NULL) {
        $caption = $first_media->get('field_captions')->entity->createFileUrl();
      }
      $elements[$delta] = [
        '#theme' => 'file_audio_with_caption',
        '#attributes' => $attributes,
        '#files' => $files,
        '#cache' => ['tags' => []],
      ];

      if (isset($caption)) {
        $elements[$delta]['#caption'] = $caption;
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
