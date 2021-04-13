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
    if ($exception instanceof LibauthException) {
      $this->logger->get('libauth')->error($exception->getMessage());
      $build = [
        '#theme' => 'exception_template',
        '#name' => 'Error',
        '#message' => $exception->getMessage(),
        '#cache' => ['max-age' => 0],
      ];
      $content = \Drupal::service('renderer')->renderRoot($build);
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
