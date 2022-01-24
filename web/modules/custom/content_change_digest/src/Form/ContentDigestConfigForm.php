<?php

namespace Drupal\content_change_digest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 *
 */
class ContentDigestConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_change_digest.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_change_digest';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_change_digest.adminsettings');

    $form['fieldset_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Email Digest Recipients'),
      '#description' => 'Individual users may opt out of the emailing by visiting their user edit page <code>user/{uid}/edit</code>',
    ];
    $form['fieldset_wrapper']['description_item'] = [
      '#type' => 'item',
      '#description' => t('Select Roles and individual Users that should get the Content Changed Digest emailings.'),
    ];
    $form['fieldset_wrapper']['content_change_digest_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Select roles'),
      '#options' => $this->get_roles(),
      '#default_value' => $config->get('content_change_digest_roles'),
    ];
    $form['fieldset_wrapper']['content_change_digest_users'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 14,
      '#title' => $this->t('Users'),
      '#description' => $this->t('Select individual users'),
      '#options' => $this->get_users(),
      '#default_value' => $config->get('content_change_digest_users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('content_change_digest.adminsettings')
      ->set('content_change_digest_roles', $form_state->getValue('content_change_digest_roles'))
      ->set('content_change_digest_users', $form_state->getValue('content_change_digest_users'))
      ->save();
  }

  /**
   *
   */
  public function get_roles() {
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    return $roles;
  }

  /**
   *
   */
  public function get_users() {
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->execute();
    $users = User::loadMultiple($ids);
    $userlist = [];
    foreach ($users as $user) {
      $username = $user->get('name')->value;
      $uid = $user->get('uid')->value;
      $userlist[$uid] = $username;
    }
    return $userlist;
  }

}
