<?php

namespace Drupal\asu_item_extras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The currentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Initializes an ExploreForm object - set dependency injection variables.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   *
   * @return mixed
   *   The initialized form object.
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
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
       (($node) ? $node->id() : 0) . '/members', ['attributes' => ['class' => 'nav-link']]);
    $link = Link::fromTextAndUrl($this->t('View all associated media'), $url);
    $link = $link->toRenderable();
    $form['members_link'] = [
      '#markup' =>
      (($link) ?
        render($link):
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

