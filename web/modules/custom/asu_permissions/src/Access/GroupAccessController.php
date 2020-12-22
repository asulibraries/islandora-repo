<?php

namespace Drupal\asu_permissions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Checks access based on group membership.
 */
class GroupAccessController implements AccessInterface {
  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a GroupAccessController object.
   *
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(GroupMembershipLoaderInterface $membership_loader) {
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
    $grps = $this->membershipLoader->loadByUser($account);
    $access = FALSE;
    $plugin_id = 'group_node:collection';
    foreach ($grps as $grp) {
      if ($grp) {
        $access |= ($grp->hasPermission("edit $plugin_id entity", $account));
      }
    }
    return ($access) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
