<?php

namespace Drupal\persistent_identifiers;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

abstract class PersistentIdentifierPluginBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    if (isset($this->getConfiguration()['id'])) {
      return $this->getConfiguration()['id'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * Form validation handler is optional.
   *
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

//     /**
//    * Constructs a new Handle .
//    *
//    * @param array $configuration
//    *   A configuration array containing information about the plugin instance.
//    * @param string $plugin_id
//    *   The plugin_id for the plugin instance.
//    * @param mixed $plugin_definition
//    *   The plugin implementation definition.
//    * @param \Drupal\Core\Config\ConfigManagerInterface $manager
//    *   The config manager for retrieving dependent config.
//    * @param \Drupal\Core\Config\StorageInterface|null $secondary
//    *   The config storage for the blacklisted config.
//    */
//   public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $manager, StorageInterface $secondary = NULL) {
//     parent::__construct($configuration, $plugin_id, $plugin_definition);
//     $this->manager = $manager;

//   }

//   public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
//     $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

//     $plugin->eventDispatcher = $container->get('event_dispatcher');

//     return $plugin;
//   }
}
