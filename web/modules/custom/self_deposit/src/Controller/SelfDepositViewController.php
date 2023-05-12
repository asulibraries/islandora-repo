<?php

namespace Drupal\self_deposit\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Controller\WebformSubmissionViewController;

/**
 * Renders create item form on self_deposit webforms
 */
class SelfDepositViewController extends WebformSubmissionViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $webform_submission, $view_mode = 'default', $langcode = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    $settings = \Drupal::config('self_deposit.selfdepositsettings');

    if ($webform->id() == $settings->get('webform_id')) {
      $build = \Drupal::formBuilder()->getForm('Drupal\self_deposit\Form\SelfDepositCreateForm', $webform_submission);
    }
    
    $build['submissioninfo'] = parent::view($webform_submission, $view_mode, $langcode);
    return $build;
  }
}
