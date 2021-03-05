<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'authority_link_brief' widget.
 *
 * @FieldWidget(
 *   id = "authority_link_brief",
 *   label = @Translation("Authority Link (Brief) Widget"),
 *   field_types = {
 *     "authority_link"
 *   }
 * )
 */
class AuthorityLinkBrief extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {
    // Item of interest.
    $item = &$items[$delta];
    $settings = $item->getFieldDefinition()->getSettings();

    // Load up the form fields.
    $element += [
      '#type' => 'fieldset',
    ];
    $element['source'] = [
      '#title' => $this->t('Source'),
      '#type' => 'select',
      '#options' => $settings['authority_sources'],
      '#default_value' => isset($item->source) ? $item->source : '',
    ];
    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('URI'),
      '#placeholder' => $this
        ->getSetting('placeholder_url'),
      '#default_value' => !$item
        ->isEmpty() ? static::getUriAsDisplayableString($item->uri) : NULL,
      '#element_validate' => [
              [
                get_called_class(),
                'validateUriElement',
              ],
      ],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    ];
    return $element;
  }

}
