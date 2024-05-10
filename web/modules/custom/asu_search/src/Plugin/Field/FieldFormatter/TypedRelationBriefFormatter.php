<?php

namespace Drupal\asu_search\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TypedRelationBriefFormatter'.
 *
 * @FieldFormatter(
 *   id = "typed_relation_brief",
 *   label = @Translation("Typed Relation Brief Formatter"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationBriefFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'unlink_without_uri' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['unlink_without_uri'] = [
      '#title' => $this->t('Unlink Relations without URIs'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('unlink_without_uri'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $unique_tids = [];

    foreach ($items as $delta => $item) {
      $this_tid = $item->target_id;
      $delta_to_update = in_array($this_tid, $unique_tids);
      $rel_types = $item->getRelTypes();
      $rel_type = $rel_types[$item->rel_type] ?? $item->rel_type;
      if (isset($elements[$delta])) {
        if (!$delta_to_update) {
          $unique_tids[$delta] = $this_tid;
          $elements[$delta]['#suffix'] = ' (' . $this->cleanUpRelator($rel_type) . ')';
        }
        else {
          $delta_to_update = array_search($this_tid, $unique_tids);
          $suffix_before = $elements[$delta_to_update]['#suffix'];
          $suffix_parts = explode(" ( ", $suffix_before);
          $elements[$delta_to_update]['#suffix'] = str_replace(')', '', $suffix_parts[0]) . ", " . $this->cleanUpRelator($rel_type) . ')';
          unset($elements[$delta]);
        }
      }
      $target_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($this_tid);
      if ($this->getSetting('unlink_without_uri') && $target_term && $target_term->hasField('field_authority_link') && $target_term->get('field_authority_link')->isEmpty()) {
        $label = '';
        foreach (['#title', '#suffix'] as $label_part) {
          $label .= ($elements[$delta][$label_part]) ? $elements[$delta][$label_part] : '';
        }
        $elements[$delta] = ['#plain_text' => $label];
      }
      elseif (array_key_exists($delta, $elements) && array_key_exists('#title', $elements[$delta])) {
        $url = \Drupal::service('facets.utility.url_generator')->getUrl(['linked_agents' => [$elements[$delta]['#title']]]);
        $elements[$delta]['#url'] = $url;
      }
    }
    return $elements;
  }

  /**
   * Clean up Relator utility.
   */
  private function cleanUpRelator($rel_type) {
    $re = '/(.*) \(\S*/m';
    $str = $rel_type;
    $subst = '$1';
    $rel_type = preg_replace($re, $subst, $str);
    return $rel_type;
  }

}
