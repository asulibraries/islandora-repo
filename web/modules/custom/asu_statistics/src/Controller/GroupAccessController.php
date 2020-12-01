<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Controller.
 */
class GroupAccessController {

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
    // Check permissions and combine that with any custom access checking needed. Pass forward
    // parameters from the route and/or request as needed.
    /* 
     * the current user object. if they are not logged in- just return forbidden. 
     * but if they are logged in - you can check for admin first and allow and if 
     * not admin, then look up the groups a user belongs to. then loop through 
     * those groups and see if any of them are named Collection X Group. (or if 
     * you don't want to string match - you could look at the node members of each 
     * group that the user belongs to and see if it contains that collection)
    */
    $groups = array();
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    $access = FALSE;
    $plugin_id = 'group_node:collection';
    foreach ($grps as $grp) {
      if ($grp) {
        $access |= ($grp->hasPermission("view $plugin_id entity", $account));
        $groups[] = $grp->getGroup();
      }
    }
    return ($access) ?  AccessResult::allowed() : AccessResult::forbidden();
    // return AccessResult::allowedIf($account->hasPermission('do example things') && $this->someOtherCustomCondition());
//    return AccessResult::allowedIf($account->hasPermission('view content'));
  }

}
