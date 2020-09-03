<?php

namespace Drupal\deposit_agreement\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;
use Drupal\user\Entity\User;

/**
 * Deposit webform Handler.
 *
 * @WebformHandler(
 *   id = "deposit_webform_handler",
 *   label = @Translation("Deposit Webform Handler"),
 *   category = @Translation("ASU"),
 *   description = @Translation("handler for assigning permissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class DepositWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $values = $webform_submission->getData();
    if (array_key_exists('agree', $values) && $values['agree'] == 1) {
      $uid = \Drupal::currentUser()->id();
      $user = User::load($uid);
      if ($user->hasField('field_terms_accepted')) {
        $user->set('field_terms_accepted', 1);
      }
      $user->save();
    }
  }

}
