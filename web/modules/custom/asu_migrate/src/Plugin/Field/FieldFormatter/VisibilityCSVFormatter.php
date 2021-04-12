<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'VisibilityCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "visibility_csv",
 *   label = @Translation("Visibility CSV Formatter"),
 *   field_types = {
 *     "text"
 *   }
 * )
 */
class VisibilityCSVFormatter extends IntegerFormatter implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a VisibilityCSVFormatter object.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManager $entityTypeManager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $asu_utils = \Drupal::service('asu_utils');
    // This takes each node and converts the moderation_state of it into the
    // Visibility value.
    //  - Private: draft
    //  - Public: published
    foreach ($items as $delta => $item) {
      $item_entity_id = $item->value;
      $item_entity = @$this->entityTypeManager->getStorage('node')->load($item_entity_id);
      $is_published = $asu_utils->isNodePublished($item_entity);
      $elements[$delta]['#markup'] = ($is_published ? "Public" : "Private");
    }

    return $elements;
  }

}
