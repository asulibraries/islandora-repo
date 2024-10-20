<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\asu_search\Plugin\search_api\processor\Property\DescendantExtractedTextProperty;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an additional field containing the child extracted text.
 *
 * @SearchApiProcessor(
 *   id = "descendant_extracted_text",
 *   label = @Translation("Descendant Extracted Text"),
 *   description = @Translation("Adds an additional field containing the extracted text of descendant items."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class DescendantExtractedText extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * Islandora Utils for working with Media.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $islandoraUtils;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * The term for extracted text media use.
   *
   * @var \Drupal\taxomony\TermInterface
   */
  protected $extractedTextTerm;

  const EDITED_TEXT_PROPERTY = 'field_edited_text';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setLogger($container->get('logger.channel.search_api'));
    $plugin->islandoraUtils = $container->get('islandora.utils');
    $plugin->typeManager = $container->get('entity_type.manager');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Descendant Extracted Text'),
        'description' => $this->t('The extracted text of descendant items.'),
        'type' => 'search_api_text',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['descendant_extracted_text'] = new DescendantExtractedTextProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if (!$node instanceof NodeInterface) {
      $this->getLogger()->warning("Item isn't a node: " . get_class($node));
      return;
    }
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'descendant_extracted_text');
    foreach ($fields as $field) {
      $config = $field->getConfiguration();
      $results = array_filter($this->gatherDescendantExtractedText(
          $node,
          $config['bundles'],
          $this->typeManager->getStorage('taxonomy_term')->load($config['media_use_term']))
      );
      foreach ($results as $result) {
        $field->addValue($result);
      }
    }
  }

  /**
   * Recurses descendants to pull extracted text.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check for descendants with extracted text.
   * @param array $bundles
   *   Machine names of node bundles to descend through.
   * @param \Drupal\taxonomy\TermInterface $use_term
   *   Term used to identify media with extracted text.
   *
   * @return array
   *   Array of extracted text values.
   */
  protected function gatherDescendantExtractedText(NodeInterface $node, array $bundles, TermInterface $use_term) {
    $results = [];

    // Skip bundles not in config, if set.
    if (!empty($bundles) && !in_array($node->bundle(), $bundles)) {
      return $results;
    }

    // Get text from the current item.
    $extractedTextMedia = $this->islandoraUtils->getMediaWithTerm($node, $use_term);
    if ($extractedTextMedia) {
      $extracted = '';
      foreach ($extractedTextMedia->get(self::EDITED_TEXT_PROPERTY) as $value) {
        $extracted .= ($value->value) ? $value->value : '';
      }
      $results[] = $extracted;
    }
    // Grab text from children.
    foreach ($this->typeManager->getStorage('node')->loadByProperties([IslandoraUtils::MEMBER_OF_FIELD => $node->id()]) as $child) {
      $results += $this->gatherDescendantExtractedText($child, $bundles, $use_term);
    }
    return $results;
  }

}
