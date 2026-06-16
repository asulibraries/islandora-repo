<?php

namespace Drupal\asu_search\EventSubscriber;

use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Alters the query where necessary to implement business logic.
 *
 * @package Drupal\asu_search\EventSubscriber
 */
class SolrQueryAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::PRE_QUERY => 'preQuery',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(PreQueryEvent $event): void {
    // Only modify the query if no_pages is truthy.
    // We can't default to allowing it,
    // because unchecked checkboxes are not sent.
    $no_pages = $this->requestStack->getCurrentRequest()?->query->get('no_pages');
    if (!$no_pages) {
      return;
    }

    // Add a filter query to exclude child objects from the results.
    $solarium = $event->getSolariumQuery();
    $solarium->addFilterQuery(
    $solarium->createFilterQuery('exclude_components_keep')
      ->setQuery('-bs_complex_object_child:true')
    );
    $solarium->addFilterQuery(
      $solarium->createFilterQuery('exclude_components')
        ->setQuery('-bs_field_complex_object_child:true')
    );
  }

}
