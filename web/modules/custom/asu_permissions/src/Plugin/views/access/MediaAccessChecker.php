namespace Drupal\news\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
  * @ingroup views_access_plugins
  *
  * @ViewsAccess(
  *   id = "media_access_checker",
  *   title = @Translation("Media Access Checker"),
  *   help = @Translation("Access will be granted based on permissions for the media.")
  * )
*/
class MediaAccessChecker extends AccessPluginBase {

/**
* {@inheritdoc}
*/
public function summaryTitle() {
return $this->t('Custom Access Settings');
}


/**
* {@inheritdoc}
*/
public function access(AccountInterface $account) {
  // Skip access check for User 1.
  if ($account->id() == 1) {
    return TRUE;
  }
  // Load user entity.
  $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
  if (isset($user->field_resident_editor) && $user->field_resident_editor->value) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

public function alterRouteDefinition(Route $route) {
$route->setRequirement('_custom_access', 'temple_of_gozer.access_handler::access');
}
}
