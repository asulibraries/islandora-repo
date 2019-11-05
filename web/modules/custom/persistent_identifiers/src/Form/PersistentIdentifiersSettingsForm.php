<?php

namespace Drupal\persistent_identifiers\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form for Persistent Identifier settings.
 */
class PersistentIdentifiersSettingsForm extends ConfigFormBase {
  const CONFIG_NAME = 'persistent_identifiers.settings';
  const PI_PLUGINS = 'pi_enabled_plugins';

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->setConfigFactory($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'persistent_identifiers_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $selected_bundles = $config->get(self::PI_PLUGINS);

    $options = [];
    $type = \Drupal::service('plugin.manager.persistent_identifiers');
    $plugin_definitions = $type->getDefinitions();
    foreach ($plugin_definitions as $plugin) {
      $options["{$plugin}:{$plugin}"] =
        $this->t('@label (@type)', [
          '@label' => $plugin['label'],
          '@type' => $plugin,
        ]);
    }

    $form['bundle_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Persistent Identifier Plugins'),
      '#description' => $this->t('The selected plugins will create identifiers where the persistent identifier field has been added.'),
      '#open' => TRUE,
      self::PI_PLUGINS => [
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $selected_bundles,
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);

    $pseudo_types = array_filter($form_state->getValue(self::PI_PLUGINS));

    $config
      ->set(self::PI_PLUGINS, $pseudo_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
