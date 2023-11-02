<?php

namespace Drupal\asu_collection_extras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ExploreForm to search the collection by calling the search GET address.
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
    return 'asu_collection_extras_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->currentRouteMatch->getParameter('node');
    $url = Url::fromUri(
      $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
      '/collections/' . (($node) ? $node->id() : 0) .
      '/search/?search_api_fulltext=',
      ['attributes' => ['class' => 'nav-link']]);
    $link = Link::fromTextAndUrl($this->t('Explore items'), $url);
    $link = $link->toRenderable();
    $form['explore_link'] = [
      '#markup' =>
      (($link) ?
        \Drupal::service('renderer')->render($link) :
        ""),
    ];
    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fulltext search'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Search for items'),
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
   * SubmitForm performs a redirect to the GET address for this search page.
   *
   * @param array $form
   *   Drupal $form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal FormStateIterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Need to redirect this to the solr_search_api view to handle the query.
    $node = $this->currentRouteMatch->getParameter('node');
    $search_term = $form_state->getValue('search_api_fulltext');
    $url = Url::fromUri(
      $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
       '/collections/' . (($node) ? $node->id() : 0) .
       '/search/?search_api_fulltext=' . $search_term);
    $form_state->setRedirectUrl($url);
  }

}
