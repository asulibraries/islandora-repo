<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

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
$groups = array();
$grp_membership_service = \Drupal::service('group.membership_loader');
$grps = $grp_membership_service->loadByUser($user);
foreach ($grps as $grp) {
        $groups[] = $grp->getGroup();
}
     */
    
    // return AccessResult::allowedIf($account->hasPermission('do example things') && $this->someOtherCustomCondition());
    
    return AccessResult::allowedIf($account->hasPermission('view content'));
  }

}
