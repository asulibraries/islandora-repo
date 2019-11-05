<?php

namespace Drupal\deposit_agreement\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformTokenManagerInterface;

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
    // \Drupal::logger("webform")->notice("submit form");
    // $values = $webform_submission->getData();
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $user->addRole('depositor');
    // TODO make sure the depositor role exists with the correct permissions - probably a migration or config/install necessary
    $user->save();
  }

}
