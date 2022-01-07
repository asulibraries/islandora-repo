<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The asuUtils definition.
   *
   * @var asuUtils
   */
  protected $asuUtils;

  /**
   * Constructs a VisibilityCSVFormatter object.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManagerInterface $entityTypeManager,
      asu_utils $ASUUtils
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->asuUtils = $ASUUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('asu_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // This takes each node and converts the moderation_state of it into the
    // Visibility value.
    //  - Private: draft
    //  - Public: published.
    foreach ($items as $delta => $item) {
      $item_entity_id = $item->value;
      $item_entity = @$this->entityTypeManager->getStorage('node')->load($item_entity_id);
      $is_published = $this->asuUtils->isNodePublished($item_entity);
      $elements[$delta]['#markup'] = ($is_published ? "Public" : "Private");
    }

    return $elements;
  }

}
