<?php

namespace Drupal\asu_item_extras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an 'Explore item' (search form) Block.
 */
class ExploreForm extends FormBase {

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request_stack service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Initializes the block and set dependency injection variables.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return mixed
   *   The initialized form object.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_item_extras_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->currentRouteMatch->getParameter('node');
    $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/items/' .
       (($node) ? $node->id() : 0) . '/members');
    $link = Link::fromTextAndUrl($this->t('View all associated media'), $url);
    $link = $link->toRenderable();
    $form['members_link'] = [
      '#markup' =>
      (($link) ?
        "<p>" . render($link) . "</p>" :
        ""),
    ];
    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fulltext search'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Enter search terms'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Need to redirect this to the solr_search_api view to handle the query.
    $node = $this->currentRouteMatch->getParameter('node');
    $search_term = $form_state->getValue('search_api_fulltext');
    $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/items/' .
       (($node) ? $node->id() : 0) . '/search/?search_api_fulltext=' . $search_term);
    $form_state->setRedirectUrl($url);
  }

}
