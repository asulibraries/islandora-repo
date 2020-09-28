<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;


/**
 * Look up user by name.
 *
 * @MigrateProcessPlugin(
 *   id = "user_lookup"
 * )
 *
 * @code
 *   uid:
 *      plugin: user_lookup
 *      source: System User
 *      default_value: constants/uid
 */
class UserLookup extends ProcessPluginBase {

  /** @inheritdoc */
  public function transform($string, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($string != "" && $string != NULL) {
      $user = user_load_by_name($string);
      if ($user == NULL) {
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('cas')) {
          $user = $this->userAdd($string, ['authenticated', 'casuser'], 'asu.edu', NULL);
        }
        else {
          $user = User::create();
          $user->enforceIsNew();
          $user->setEmail($string . "@asu.edu");
          $user->setUsername($string);
          $user->save();
        }
      }
      return $user->id();
    }
    else {
      $uid = $row->getSourceProperty('constants/uid');
      return $uid;
    }
  }

  /**
   * Perform a single CAS user creation batch operation.
   *
   * Callback for batch_set().
   *
   * @param string $cas_username
   *   The CAS username, which will also become the Drupal username.
   * @param array $roles
   *   An array of roles to assign to the user.
   * @param string $email_hostname
   *   The hostname to combine with the username to create the email address.
   */
  public static function userAdd($cas_username, array $roles, $email_hostname) {
    $cas_user_manager = \Drupal::service('cas.user_manager');

    // Back out of an account already has this CAS username.
    $existing_uid = $cas_user_manager->getUidForCasUsername($cas_username);
    if ($existing_uid) {
      return $existing_uid;
    }

    $user_properties = [
      'roles' => $roles,
      'mail' => $cas_username . '@' . $email_hostname,
    ];

    /** @var \Drupal\user\UserInterface $user */
    $user = $cas_user_manager->register($cas_username, $user_properties, $cas_username);
    return $user;
  }

}
