<?php

namespace Drupal\asu_statistics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Controller.
 */
class GroupAccessController implements AccessInterface {

  /**
   * @var Drupal\Core\Session\AccountProxy
   *   The current account.
   */
  protected $account;
  /**
   * @var Drupal\group\GroupMembershipLoaderInterface
   *   The membership loader.
   */
  protected $membershipLoader;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Session\AccountProxy $account
   *   The current user account.
   * @param Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The membership loader.
   */
  public function __construct(AccountProxy $account, GroupMembershipLoaderInterface $membership_loader) {
    $this->account = $account;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    /*
     * the current user object. if they are not logged in- just return
     * forbidden. If they are logged in - you can check for admin first and
     * allow and if not admin, then look up the groups a user belongs to. then
     * loop through those groups and see if any of them are named Collection
     * X Group. (or if you don't want to string match - you could look at the
     * node members of each group that the user belongs to and see if it
     * contains that collection).
     */
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    $roles = $account->getRoles();
    $access = (in_array('administrator', $roles));
    $plugin_id = 'group_node:collection';
    foreach ($grps as $grp) {
      if ($grp) {
        $access |= ($grp->hasPermission("edit $plugin_id entity", $account));
      }
    }
    return ($access) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
