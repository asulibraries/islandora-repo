<?php

namespace Drupal\self_deposit\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Returns responses for Share Your Work routes.
 */
final class SubmissionRedirectController extends ControllerBase {

  /**
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $submissionRedirectConfig;

  /**
   * The URL generator service.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The controller constructor.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    Config $config,
    UrlGeneratorInterface $url_generator,
  ) {
    $this->currentUser = $currentUser;
    $this->submissionRedirectConfig = $config;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
      $container->get('config.factory')->get('self_deposit.selfdepositsettings'),
      $container->get('url_generator'),
    );
  }

  /**
   * Builds the response.
   */
  public function checkAccess() {

    // If they aren't logged in, let them log in and hit this route again.
    if ($this->currentUser->isAnonymous()) {
      return new RedirectResponse(
        $this->urlGenerator->generateFromRoute('user.login', [], [
          'query' => [
            'destination' => $this->urlGenerator->generateFromRoute('share_your_work.share_faculty_staff_work'),
          ],
        ]),
        301);
    }
    // If authorized, redirect to configured webform.
    $webform = Webform::load($this->submissionRedirectConfig->get('target_webform'));
    if ($webform && $webform->access('submission_create', $this->currentUser)) {
      return new RedirectResponse($webform->toUrl()->toString());
    }

    // Redirect to `/form/askalib-ticket`.
    $redirect_webform = Webform::load($this->submissionRedirectConfig->get('redirect_webform'));
    if ($redirect_webform) {
      return new RedirectResponse($redirect_webform->toUrl()->toString());
    }

    throw new NotFoundHttpException("Could not find the configured submission form.");
  }

}
