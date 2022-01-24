<?php

namespace Drupal\bagger_extension\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Create a bag of a node.
 *
 * @Action(
 *   id = "bag_node",
 *   label = @Translation("Bag a node"),
 *   type = "node"
 * )
 */
class BagNode extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        LoggerInterface $logger
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('logger.channel.islandora')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity) {
      return;
    }
    $entity_type = $entity->getEntityTypeId();

    if (!$entity_type == 'node') {
      return;
    }

    $config = \Drupal::config('islandora_bagger_integration.settings');
    $mode = $config->get('islandora_bagger_mode');
    $node = $entity;
    $nid = $node->id();

    if ($mode == 'local') {
      $title = $node->getTitle();

      $config = \Drupal::config('islandora_bagger_integration.settings');
      // @todo if this is FALSE, report error.
      $utils = \Drupal::service('islandora_bagger_integration.utils');

      $islandora_bagger_config_file_path = $utils->getConfigFilePath();

      // Allow other modules to modify the Islandor Bagger config file. Write out modified config
      // file contents and modify $islandora_bagger_config_file_path to point to the modified file.
      $config_file_contents = file_get_contents($islandora_bagger_config_file_path);
      \Drupal::moduleHandler()->invokeAll('islandora_bagger_config_file_contents_alter', [$nid, &$config_file_contents]);
      $tmp_dir = \Drupal::service('file_system_config')->getTempDirectory();
      ;
      $tmp_islandora_bagger_config_file_path = $tmp_dir . DIRECTORY_SEPARATOR .
                pathinfo($islandora_bagger_config_file_path, PATHINFO_BASENAME) . '.islandora_bagger.' . $nid . '.tmp.yml';
      file_put_contents($tmp_islandora_bagger_config_file_path, $config_file_contents);
      $islandora_bagger_config_file_path = $tmp_islandora_bagger_config_file_path;

      $bagger_directory = $config->get('islandora_bagger_local_bagger_directory');
      $bagger_cmd = ['./bin/console', 'app:islandora_bagger:create_bag', '--settings=' . $islandora_bagger_config_file_path, '--node=' . $nid];

      $process = new Process($bagger_cmd);
      $process->setWorkingDirectory($bagger_directory);
      $process->run();

      $path_to_bag = preg_replace('/^.*\s+at\s+/', '', trim($process->getOutput()));
      $bag_filename = pathinfo($path_to_bag, PATHINFO_BASENAME);
      $path_to_bag = file_create_url('public://' . $bag_filename);
      $url = Url::fromUri($path_to_bag);
      $link = \Drupal::service('link_generator')->generate($this->t('here'), $url);

      if ($process->isSuccessful()) {
        $messenger_level = 'addStatus';
        $logger_level = 'notice';
        $message = $this->t(
              'Download your Bag @link.',
              ['@link' => $link]
          );
        @unlink($tmp_islandora_bagger_config_file_path);
      }
      else {
        throw new ProcessFailedException($process);
        $messenger_level = 'addWarning';
        $logger_level = 'warning';
        $message = $this->t(
              'Request to create Bag for "@title" (node @nid) failed with return code @return_code.',
              ['@title' => $title, '@nid' => $nid, '@return_code' => $return_code]
                );
      }

      \Drupal::logger('islandora_bagger_integration')->{$logger_level}($message);
      $this->messenger()->{$messenger_level}($message);
    }

    if ($mode == 'remote') {
      $title = $node->getTitle();

      $endpoint = $config->get('islandora_bagger_rest_endpoint');

      $utils = \Drupal::service('islandora_bagger_integration.utils');
      $islandora_bagger_config_file_path = $utils->getConfigFilePath();

      // Allow other modules to modify $config_file_contents before it is POSTed to the microservice.
      $config_file_contents = file_get_contents($islandora_bagger_config_file_path);
      \Drupal::moduleHandler()->invokeAll('islandora_bagger_config_file_contents_alter', [$nid, &$config_file_contents]);

      if ($config->get('islandora_bagger_add_email_user')) {
        $user = \Drupal::currentUser();
        $user_email = $user->getEmail();
        $user_email_yaml_string = "\n# Added by the Islandora Bagger Integration module\nrecipient_email: $user_email";
        $config_file_contents = $config_file_contents . $user_email_yaml_string;
      }

      $headers = ['Islandora-Node-ID' => $nid];
      $response = \Drupal::httpClient()->post(
            $endpoint,
            ['headers' => $headers, 'body' => $config_file_contents, 'allow_redirects' => ['strict' => TRUE]]
        );
      $http_code = $response->getStatusCode();
      if ($http_code == 200) {
        $messenger_level = 'addStatus';
        $logger_level = 'notice';
        if ($config->get('islandora_bagger_add_email_user')) {
          $message = $this->t(
            'Request to create Bag for "@title" (node @nid) submitted. You will receive an email at @email when the Bag is ready to download.',
            ['@title' => $title, '@nid' => $nid, '@email' => $user_email]
            );
        }
        else {
          $message = $this->t(
            'Request to create Bag for "@title" (node @nid) submitted.',
            ['@title' => $title, '@nid' => $nid]
                );
        }
      }
      else {
        $messenger_level = 'addWarning';
        $logger_level = 'warning';
        $message = $this->t(
          'Request to create Bag for "@title" (node @nid) failed with status code @http.',
          ['@title' => $title, '@nid' => $nid, '@http' => $http_code]
            );
      }

      \Drupal::logger('islandora_bagger_integration')->{$logger_level}($message);
      $this->messenger()->{$messenger_level}($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }

}
