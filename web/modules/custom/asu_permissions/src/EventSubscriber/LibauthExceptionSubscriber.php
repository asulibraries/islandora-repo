<?php

namespace Drupal\asu_permissions\EventSubscriber;

use Drupal\asu_permissions\Exception\LibauthException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catch libauth exceptions.
 */
class LibauthExceptionSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *   The logger.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $this->logger->get('hello')->info("in libauthsubscriber");
    $class = get_class($exception);
    if ($exception instanceof LibauthException) {
      $this->logger->get('libauth')->error($exception->getMessage());
      // $content = file_get_contents(DRUPAL_ROOT . '/../500-error.html');
      $build = [
        '#theme' => 'exception_template',
        '#name' => 'Error',
        '#message' => $exception->getMessage(),
        '#cache' => ['max-age' => 0],
      ];
      $content = \Drupal::service('renderer')->renderRoot($build);
      // $response = new Response($exception->getMessage(), 500);
      $response = new Response($content, 500);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 60];
    return $events;
  }

}
