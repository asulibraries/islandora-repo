<?php

namespace Drupal\asu_deposit_methods;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;

/**
 * Provides commonly used utility functions.
 */
class DepositUtils {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DepositUtils object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the model/media type from the type of file.
   */
  public function getModel($mime, $filename) {
    $filename = strtolower($filename);
    if (str_contains($mime, 'image') || str_contains($filename, ".jpg") || str_contains($filename, ".jpeg") || str_contains($filename, ".png")) {
      $model = 'Image';
      $media_type = 'image';
      $field_name = 'field_media_image';
      if (str_contains($filename, ".tif") || str_contains($filename, ".tiff")) {
        $media_type = 'file';
        $field_name = 'field_media_file';
      }
    }
    if (str_contains($filename, ".pdf") || str_contains($filename, ".doc") || str_contains($filename, ".docx")) {
      $model = 'Digital Document';
      $media_type = 'document';
      $field_name = 'field_media_document';
    }
    if (str_contains($mime, 'audio')) {
      $model = 'Audio';
      $media_type = 'audio';
      $field_name = 'field_media_audio_file';
    }
    if (str_contains($mime, 'video')) {
      $model = 'Video';
      $media_type = 'video';
      $field_name = 'field_media_video_file';
    }
    if (!isset($model)) {
      $media_type = 'file';
      $model = 'Binary';
      $field_name = 'field_media_file';
    }

    return [$model, $media_type, $field_name];
  }

  /**
   * Gets or creates taxonomy terms.
   */
  public function getOrCreateTerm($string, $vocab, $relator = NULL) {
    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');
    $arr = $taxo_manager->loadByProperties(['name' => $string, 'vid' => $vocab]);
    if (count($arr) > 0) {
      $term = reset($arr);
    }
    else {
      $term = Term::create([
        'name' => $string,
        'vid' => $vocab,
        'langcode' => 'en',
      ]);
      $term->save();
    }
    $term_arr = ['target_id' => $term->id()];
    if ($relator) {
      $term_arr['rel_type'] = $relator;
    }
    return $term_arr;
  }

  /**
   * Actually creates the media.
   */
  public function createMedia($media_type, $field_name, $file_id, $nid, $pterm='ASU Only') {
    $of_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'Original File']);
    $original_file = reset($of_terms);

    $media_args = [
      'bundle' => $media_type,
      'uid' => \Drupal::currentUser()->id(),
      'field_media_of' => [
        ['target_id' => $nid],
      ],
      'field_media_use' => [
        ['target_id' => $original_file->id()],
      ],
    ];

    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');
    $perm_term_arr = $taxo_manager->loadByProperties(['name' => $pterm]);
    $perm_term = reset($perm_term_arr);

    $media_args['field_access_terms'] = [
      ['target_id' => $perm_term->id()],
    ];
    $media_args[$field_name] = [
      ['target_id' => $file_id],
    ];
    $media = Media::create($media_args);
    $media->save();
    return $media;
  }

}
