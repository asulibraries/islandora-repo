<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'MimeTypeIcon' formatter.
 *
 * Most of this was copied straight from https://git.drupalcode.org/project/font_awesome/-/blob/e316f68bafc8634dd26edd5c1b8d9c39e754eb07/src/Plugin/Field/FieldFormatter/IconFormatter.php
 * with the exception of the Mime Type association with the corresponding
 * font-awesome icon.
 *
 * @FieldFormatter(
 *   id = "mime_type_icon",
 *   label = @Translation("Mime Type Icon"),
 *   field_types = {
 *     "string"
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class MimeTypeIcon extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '',
      'fixed_width' => 'fa-fw',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size of the icon'),
      '#default_value' => $this->getSetting('size'),
      '#options' => [
        ''  => $this->t('Default'),
        'fa-xs' => $this->t('Extra small, @size', ['@size' => '.75em']),
        'fa-sm' => $this->t('Small, @size', ['@size' => '.875em']),
        'fa-lg' => $this->t('Large, @size', ['@size' => '1.33em']),
        'fa-2x' => $this->t('Two times, @size', ['@size' => '2em']),
        'fa-3x' => $this->t('Three times, @size', ['@size' => '3em']),
        'fa-4x' => $this->t('Four times, @size', ['@size' => '4em']),
        'fa-5x' => $this->t('Five times, @size', ['@size' => '5em']),
        'fa-6x' => $this->t('Six times, @size', ['@size' => '6em']),
        'fa-7x' => $this->t('Seven times, @size', ['@size' => '7em']),
        'fa-8x' => $this->t('Eight times, @size', ['@size' => '8em']),
        'fa-9x' => $this->t('Nine times, @size', ['@size' => '9em']),
        'fa-10x' => $this->t('Ten times, @size', ['@size' => '10em']),
      ],

    ];
    $elements['fixed_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show icon with a fixed width'),
      '#description' => $this->t('Add the class "fa-fw" to the icon. See <a href="@url">documentation</a> for more information', [
        '@url' => 'https://fontawesome.com/how-to-use/on-the-web/styling/fixed-width-icons',
      ]),
      '#return_value' => 'fa-fw',
      '#default_value' => $this->getSetting('fixed_width'),
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    if ($settings['size']) {
      $summary[] = $this->t('Size: @size', ['@size' => $this->getSetting('size')]);
    }
    if (!empty($settings['fixed_width'])) {
      $summary[] = $this->t('Add "@fixed_width" class to the icon', ['@fixed_width' => $settings['fixed_width']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $url = NULL;
    if ($this->getSetting('link_to_entity')) {
      $url = $this->getEntityUrl($items->getEntity());
    }

    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);
      if ($url) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $view_value,
          '#url' => $url,
        ];
      }
      else {
        $elements[$delta] = $view_value;
      }
    }

    $elements['#attached']['library'][] = 'lp_fontawesome/fontawesome';
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $settings = [
      'fa',
      $this->getSetting('size'),
      $this->getSetting('fixed_width'),
      $this->mimeToFAIcon($item->value),
    ];

    return [
      '#type' => 'html_tag',
      '#tag' => 'i',
      '#attributes' => [
        'class' => $settings,
	'title' => $item->value,
      ],
      '#value' => '',
    ];
  }

  /**
   * Converts mime-type to a font-awesome icon class.
   */
  private function mimeToFAIcon($mime_type) {
    // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
    // Font Awesome 5 icons.
    // Generic cases.
    if (str_starts_with($mime_type, 'image')) {
      return 'fa-file-image';
    }
    elseif (str_starts_with($mime_type, 'audio')) {
      return 'fa-file-audio';
    }
    elseif (str_starts_with($mime_type, 'video')) {
      return 'fa-file-video';
    }

    // Application cases:
    switch ($mime_type) {
      // Documents.
      case 'application/pdf':
        return 'fa-file-pdf';

      case 'application/msword':
      case 'application/vnd.ms-word':
      case 'application/vnd.oasis.opendocument.text':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml':
        return 'fa-file-word';

      case 'application/vnd.ms-excel':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml':
      case 'application/vnd.oasis.opendocument.spreadsheet':
        return 'fa-file-excel';

      case 'application/vnd.ms-powerpoint':
      case 'application/vnd.openxmlformats-officedocument.presentationml':
      case 'application/vnd.oasis.opendocument.presentation':
        return 'fa-file-powerpoint';

      case 'text/plain':
        return 'fa-file-alt';

      case 'text/html':
      case 'application/json':
        return 'fa-file-code';

      // Archives.
      case 'application/gzip':
      case 'application/zip':
        return 'fa-file-archive';

      default:
        return 'fa-file';
    }
  }

}

