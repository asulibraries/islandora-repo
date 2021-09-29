<?php

namespace Drupal\asu_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class SearchForm.
 */
class SearchForm extends FormBase {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('system.site');
    $form['#attributes']['class'][] = 'form--inline';
    $form['search_api_fulltext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('keyword'),
      '#size' => 30,
      '#weight' => '0',
      '#title_display' => 'invisible',
      '#attributes' => ['placeholder' => 'Search ' . $config->get('name') ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Search'),
      '#weight' => '0',
      '#value' => 'Search',
      '#attributes' => ['class' => ['col-md-3', 'form--inline'], 'aria-label' => 'Search ' + $config->get('name')],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_term = $form_state->getValue('search_api_fulltext');
    $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/search/?search_api_fulltext=' . $search_term);
    $form_state->setRedirectUrl($url);
  }

}
