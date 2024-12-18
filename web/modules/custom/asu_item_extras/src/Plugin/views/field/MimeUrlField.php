<?php

namespace Drupal\asu_item_extras\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mime_url_field")
 */
class MimeUrlField extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['mimetype'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['mimetype'] = [
      '#type' => 'textfield',
      '#title' => "Mime Type",
      '#default_value' => $this->options['mimetype'],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (empty($this->options['mimetype'])) {
      return '';
    }
    $node = $values->_entity;
    if (!$node) {
      return '';
    }
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
      'field_mime_type' => $this->options['mimetype'],
      'field_media_of' => ['target_id' => $node->id()],
    ]);

    foreach ($media as $m) {
      // Return the first one we can access.
      if ($m->access('view')) {
        // Short-cut for getting the source field.
        $bundle = $m->bundle();
        if (!$m->hasField('field_media_' . $bundle)) {
          continue;
        }
        $file = $m->get('field_media_' . $bundle)->entity;
        if (!is_null($file)) {
          return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }
    }
    return '';
  }

}
