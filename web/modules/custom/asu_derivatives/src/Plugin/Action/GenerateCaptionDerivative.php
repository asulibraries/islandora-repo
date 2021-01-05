<?php

namespace Drupal\asu_derivatives\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\Plugin\Action\AbstractGenerateDerivative;

/**
 * Emits a Node for generating caption derivatives event.
 *
 * @Action(
 *   id = "generate_caption_derivative",
 *   label = @Translation("Generate a Caption derivative"),
 *   type = "node"
 * )
 */
class GenerateCaptionDerivative extends AbstractGenerateDerivative {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['path'] = '[date:custom:Y]-[date:custom:m]/[node:nid]-[term:name].xml';
    $config['mimetype'] = 'text/plain';
    $config['queue'] = 'islandora-connector-fits';
    $config['destination_media_type'] = 'file';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['mimetype']['#description'] = t('Mimetype to convert to (e.g. text/plain, etc...)');
    $form['mimetype']['#value'] = '';
    $form['mimetype']['#type'] = 'hidden';

    unset($form['args']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $exploded_mime = explode('/', $form_state->getValue('mimetype'));
    if ($exploded_mime[0] != 'text') {
      $form_state->setErrorByName(
        'mimetype',
        t('Please enter file mimetype (e.g. text/plain.)')
      );
    }
  }

}
