<?php
/**
 * @file
 * Contains \Drupal\asu_item_extras\Form\WorkForm.
 */
namespace Drupal\asu_item_extras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class ExploreForm extends FormBase {
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
    $node = \Drupal::routeMatch()->getParameter('node');

    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' .
       (($node) ? $node->id() : 0) . '/members');
    $link = Link::fromTextAndUrl(t('View all associated media'), $url);
    $link = $link->toRenderable();
    $form['members_link'] = array(
      '#markup' =>
        (($link) ?
        "<p>" . render($link) . "</p>":
        ""),
    );
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' .
       (($node) ? $node->id() : 0) . '/search/?search_api_fulltext=');
    $link = Link::fromTextAndUrl(t('Explore media'), $url);
    $link = $link->toRenderable();
    $form['explore_link'] = array(
      '#markup' =>
        (($link) ?
        "<p>" . render($link) . "</p><hr>":
        ""),
    );
    $form['search_api_fulltext'] = array(
      '#type' => 'textfield',
      '#title' => t('Fulltext search'),
      '#title_display' => 'invisible',
      '#placeholder' => t('Search for items'),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We do need to redirect this to the solr_search_api view to handle the query.
    $node = \Drupal::routeMatch()->getParameter('node');
    $search_term = $form_state->getValue('search_api_fulltext');
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' .
       (($node) ? $node->id() : 0) . '/search/?search_api_fulltext=' . $search_term);
    $form_state->setRedirectUrl($url);
    return;
  }
}
