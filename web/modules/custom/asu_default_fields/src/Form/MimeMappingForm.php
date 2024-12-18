<?php

namespace Drupal\asu_default_fields\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mime Type Mapping Configuration Form.
 */
class MimeMappingForm extends ConfigFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new MimeMappingForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_default_fields.mimesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mime_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asu_default_fields.mimesettings');
    $results = $this->connection
      ->query('SELECT field_mime_type_value FROM media__field_mime_type GROUP BY field_mime_type_value')
      ->fetchAll();

    $mimes = [
      'image_png' => 'image/png',
      'image_jpeg' => 'image/jpeg',
    ];

    foreach ($results as $mim) {
      $mim = $mim->field_mime_type_value;
      if (!in_array($mim, $mimes)) {
        $mk = str_replace('/', '_', $mim);
        $mk = str_replace('.', '_', $mk);
        $mimes[$mk] = $mim;
      }
    }

    $vocab = Vocabulary::load('resource_types');
    $vid = $vocab->id();
    $dc_types = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
    $dc_types_drop = [];
    foreach ($dc_types as $dt) {
      $dc_types_drop[$dt->id()] = $dt->getName();
    }

    foreach ($mimes as $mimekey => $mimeval) {
      $form[$mimekey] = [
        '#type' => 'select',
        '#options' => $dc_types_drop,
        '#title' => t($mimeval),
        '#size' => 1,
        '#required' => FALSE,
        '#default_value' => $config->get($mimekey),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $vals = $form_state->getValues();
    $config = $this->config('asu_default_fields.mimesettings');
    unset($vals['op']);
    unset($vals['submit']);
    unset($vals['form_build_id']);
    unset($vals['form_id']);
    unset($vals['form_token']);
    foreach ($vals as $k => $v) {
      $config->set($k, $v);
    }
    $config->save();
  }

}
