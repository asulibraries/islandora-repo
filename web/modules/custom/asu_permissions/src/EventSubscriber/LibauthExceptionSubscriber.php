<?php

namespace Drupal\asu_permissions\EventSubscriber;

use Drupal\asu_permissions\Exception\LibauthException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
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
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($exception instanceof LibauthException || $exception instanceof EntityStorageException) {
      $message = $exception->getMessage();
      if ($exception instanceof EntityStorageException && substr($message, 0, strlen("Libauth: ")) === "Libauth: ") {
        $message = str_replace("Libauth: ", "", $message);
      }
      else {
        return;
      }
      $this->logger->get('libauth')->error($message);
      $build = [
        '#theme' => 'exception_template',
        '#name' => 'Error',
        '#message' => $message,
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
