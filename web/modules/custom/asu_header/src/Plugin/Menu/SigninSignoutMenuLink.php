<?php

namespace Drupal\asu_header\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

class SigninSignoutMenuLink extends LoginLogoutMenuLink {

  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      return $this->t('Sign Out');
    }
    else {
      return $this->t('Sign In');
    }
  }

}