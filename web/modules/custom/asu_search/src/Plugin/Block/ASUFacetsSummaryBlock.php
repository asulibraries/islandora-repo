<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\FacetsSummaryBlockInterface;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorInterface;

/**
 * Exposes a summary based on all the facets as a block.
 *
 * @Block(
 *   id = "asu_facets_summary_block",
 *   deriver = "Drupal\facets_summary\Plugin\Block\FacetsSummaryBlockDeriver"
 * )
 */
class ASUFacetsSummaryBlock extends BlockBase implements FacetsSummaryBlockInterface, ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager
   */
  protected $facetsSummaryManager;

  /**
   * The associated facets_source_summary entity.
   *
   * @var \Drupal\facets_summary\FacetsSummaryInterface
   */
  protected $facetsSummary;

  /**
   * The Facet Manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Constructs a source summary block.
   *
   * @param array $configuration
   *   The configuration of the Facets Summary Block.
   * @param string $plugin_id
   *   The block plugin block identifier.
   * @param array $plugin_definition
   *   The block plugin block definition.
   * @param \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager $facets_summary_manager
   *   The facet manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DefaultFacetsSummaryManager $facets_summary_manager, DefaultFacetManager $facet_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsSummaryManager = $facets_summary_manager;
    $this->facetManager = $facet_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets_summary.manager'),
      $container->get('facets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!isset($this->facetsSummary)) {
      $source_id = $this->getDerivativeId();
      if (!$this->facetsSummary = FacetsSummary::load($source_id)) {
        $this->facetsSummary = FacetsSummary::create(['id' => $source_id]);
        $this->facetsSummary->save();
      }
    }
    return $this->facetsSummary;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\facets_summary\FacetsSummaryInterface $summary */
    $facets_summary = $this->getEntity();

    // Let the facet_manager build the facets.
    $build = $this->facets_build($facets_summary);

    // Add contextual links only when we have results.
    if (!empty($build)) {
      $build['#contextual_links']['facets_summary'] = [
        'route_parameters' => ['facets_summary' => $facets_summary->id()],
      ];
    }

    /** @var \Drupal\views\ViewExecutable $view */
    if ($view = $facets_summary->getFacetSource()->getViewsDisplay()) {
      $build['#attached']['drupalSettings']['facets_views_ajax'] = [
        'facets_summary_ajax' => [
          'facets_summary_id' => $facets_summary->id(),
          'view_id' => $view->id(),
          'current_display_id' => $view->current_display,
          'ajax_path' => Url::fromRoute('views.ajax')->toString(),
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $source_id = $this->getDerivativeId();
    if ($summary = FacetsSummary::load($source_id)) {
      return [$summary->getConfigDependencyKey() => [$summary->getConfigDependencyName()]];
    }
    return [];
  }

  public function facets_build(FacetsSummaryInterface $facets_summary) {
    $facetsource_id = $facets_summary->getFacetSourceId();

    /** @var \Drupal\facets\Entity\Facet[] $facets */
    $facets = $this->facetManager->getFacetsByFacetSourceId($facetsource_id);
    // Get the current results from the facets and let all processors that
    // trigger on the build step do their build processing.
    // @see \Drupal\facets\Processor\BuildProcessorInterface.
    // @see \Drupal\facets\Processor\SortProcessorInterface.
    $this->facetManager->updateResults($facetsource_id);

    $facets_config = $facets_summary->getFacets();
    // Exclude facets which were not selected for this summary.
    $facets = array_filter($facets,
      function ($item) use ($facets_config) {
        return (isset($facets_config[$item->id()]));
      }
    );

    foreach ($facets as $facet) {
      // Do not build the facet in summary if facet is not rendered.
      if (!$facet->getActiveItems()) {
        continue;
      }
      // For clarity, process facets is called each build.
      // The first facet therefor will trigger the processing. Note that
      // processing is done only once, so repeatedly calling this method will
      // not trigger the processing more than once.
      $this->facetManager->build($facet);
    }

    $build = [
      '#theme' => 'facets_summary_item_list',
      '#facet_summary_id' => $facets_summary->id(),
      '#attributes' => [
        'class' => ['facets_summary'],
        'data-drupal-facets-summary-id' => $facets_summary->id(),
      ],
    ];

    $results = [];
    foreach ($facets as $facet) {
      $show_count = $facets_config[$facet->id()]['show_count'];
      $results = array_merge($results, $this->buildResultTree($show_count, $facet->getResults(), $facets_config));
    }
    $build['#items'] = $results;

    $x = [
      '#attributes' => $build['#attributes'],
      '#facet_summary_id' => $build['#facet_summary_id'],
      '#theme' => $build['#theme'],
      '#items' => []
    ];
    // Allow our Facets Summary processors to alter the build array in a
    // configured order.
    foreach ($facets_summary->getProcessorsByStage(ProcessorInterface::STAGE_BUILD) as $processor) {
      if (!$processor instanceof BuildProcessorInterface) {
        throw new InvalidProcessorException("The processor {$processor->getPluginDefinition()['id']} has a build definition but doesn't implement the required BuildProcessorInterface interface");
      }
      $x = $processor->build($facets_summary, $x, $facets);
    }

    return $build;
  }

  /**
   * Build result tree, taking possible children into account.
   *
   * @param bool $show_count
   *   Show the count next to the facet.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   Facet results array.
   *
   * @return array
   *   The rendered links to the active facets.
   */
  protected function buildResultTree($show_count, array $results, $facets_config) {
    $items = [];
    foreach ($results as $result) {
      if ($result->isActive()) {
        $item = [
          '#theme' => 'facets_result_item__summary',
          '#value' => $result->getDisplayValue(),
          '#show_count' => $show_count,
          '#count' => $result->getCount(),
          '#is_active' => TRUE,
          '#facet' => $result->getFacet(),
          '#raw_value' => $result->getRawValue(),
        ];
        $item = (new Link($item, $result->getUrl()))->toRenderable();
        $item['#wrapper_attributes'] = [
          'class' => [
            'facet-summary-item--facet',
          ],
        ];
        $item['#prefix'] = $facets_config[$result->getFacet()->id()]['label'] . ": ";
        $items[] = $item;
     }
      if ($children = $result->getChildren()) {
        $items = array_merge($items, $this->buildResultTree($show_count, $children, $facets_config));
      }
    }
    return $items;
  }

}
