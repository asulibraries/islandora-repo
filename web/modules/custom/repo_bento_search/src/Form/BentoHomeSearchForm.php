<?php

namespace Drupal\repo_bento_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class BentoHomeSearchForm.
 */
class BentoHomeSearchForm extends FormBase {

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
    return 'bento_home_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form--inline';
    $form['q'] = [
      '#type' => 'textfield',
      '#title' => $this->t('keyword'),
      '#size' => 80,
      '#weight' => '0',
      '#title_display' => 'invisible',
      '#attributes' => ['placeholder' => 'Enter keyword(s) here'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Search'),
      '#weight' => '0',
      '#value' => 'Search',
      '#attributes' => ['class' => ['col-md-3', 'form--inline']],
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
    $search_term = $form_state->getValue('q');
    $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/all/search/?q=' . $search_term);
    $form_state->setRedirectUrl($url);
  }

}
