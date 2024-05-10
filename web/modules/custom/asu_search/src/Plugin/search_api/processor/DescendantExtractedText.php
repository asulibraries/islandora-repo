<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
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
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

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
    $plugin->nodeStorage = $container->get('entity_type.manager')->getStorage('node');
    $plugin->extractedTextTerm = $plugin->islandoraUtils->getTermForUri('http://pcdm.org/use#ExtractedText');

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
      $properties['descendant_extracted_text'] = new ProcessorProperty($definition);
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
    $results = array_filter($this->gatherDescendantExtractedText($node));
    $this->getLogger()->debug("Item's results: " . print_r($results, TRUE));
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'descendant_extracted_text');
    foreach ($fields as $field) {
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
   *
   * @return array
   *   Array of extracted text values.
   */
  protected function gatherDescendantExtractedText(NodeInterface $node) {
    $results = [];

    // Get text from the current item.
    $extractedTextMedia = $this->islandoraUtils->getMediaWithTerm($node, $this->extractedTextTerm);
    if ($extractedTextMedia) {
      $extracted = '';
      foreach ($extractedTextMedia->get(self::EDITED_TEXT_PROPERTY) as $value) {
        $extracted .= ($value->value) ? $value->value : '';
      }
      $results[] = $extracted;
    }
    // Grab text from children.
    foreach ($this->nodeStorage->loadByProperties([IslandoraUtils::MEMBER_OF_FIELD => $node->id()]) as $child) {
      $results += $this->gatherDescendantExtractedText($child);
    }
    return $results;
  }

}
