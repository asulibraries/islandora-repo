<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Part of {parent_complex_object:pid}' Block.
 *
 * @Block(
 *   id = "asu_item_is_part_of",
 *   admin_label = @Translation("Item is part of (complex object)"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemIsPartOf extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - "Part of {link to : parent complex object}"
     */
    $parents_output = [];
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('node', $block_config)) {
      if (array_key_exists('is_metadata_page', $block_config)) {
        $is_metadata_page = $block_config['is_metadata_page'];
      }
      else {
        $is_metadata_page = NULL;
      }
      $node = $block_config['node'];
      $field_complex_object_child = $node->get('field_complex_object_child')->getString();
      if ($field_complex_object_child) {
        // first look at the node's field_member_of
        $complex_object_parent = $node->get('field_member_of')->entity;
        if (is_object($complex_object_parent)) {
          $parents_output[] = $this->_make_link_and_label(t('Part of'), $is_metadata_page, $complex_object_parent);
        }
        $direct_complex_obj_parent = $complex_object_parent->get('field_member_of')->entity;
        if (is_object($direct_complex_obj_parent)) {
          $additional_complex_obj_parents = $complex_object_parent->get('field_additional_memberships')->referencedEntities();
          // also pass this the field_additional_memberships ($additional_complex_obj_parents)
          $parents_output[] = $this->_make_link_and_label(
            t('Collections this item is in'),
            $is_metadata_page,
            $direct_complex_obj_parent,
            $additional_complex_obj_parents
          );
        }
      } else {
        $collection_parent = $node->get('field_member_of')->entity;
        if (is_object($collection_parent)) {
          $additional_parents = $node->get('field_additional_memberships')->referencedEntities();
          // also pass this the field_additional_memberships ($additional_complex_obj_parents)
          $parents_output[] = $this->_make_link_and_label(
            t('Collections this item is in'),
            $is_metadata_page,
            $collection_parent,
            $additional_parents
          );
        }
      }
    }
    $split_html = ($is_metadata_page) ? '</div><div class="field--label-inline row field">' : '<br>';
    return [
      '#cache' => ['max-age' => 0],
      '#markup' => (($is_metadata_page) ? '<div class="field--label-inline row field">' : '<div>') .
        implode($split_html, $parents_output) .
        '</div>',
    ];
  }

  /**
   * Helper function to make a label and a link to the related entity.
   *
   * @param string $label_text
   *  The lable div to output before the link.
   * @param boolean $is_metadata_page
   *  Whether or not the display is being wrapped with a field row class
   * @param object $parent_node
   *  The referenced entity by way of the node's field_member_of->entity.
   * @param array $additional_parents
   *  Array of additional entites by way of the node's
   * field_additional_memberships->referencedEntities().
   * @return string
   *  The HTML that represents the label div and the link to the provided node/s.
   */
  private function _make_link_and_label($label_text, $is_metadata_page, $parent_node, $additional_parents = NULL) {
    $html_of_links[] = $this->_get_html_of_entity($parent_node);
    if (is_array($additional_parents)) {
      foreach ($additional_parents as $additional_parent) {
        $html_of_links[] = $this->_get_html_of_entity($additional_parent);
      }
    }
    return
      '  <div class="field__label' . (($is_metadata_page) ? ' col-sm-2' : '') . '">' . $label_text . '</div>' .
      '  <div class="field__item' . (($is_metadata_page) ? ' col-sm-9' : '') . '">' . implode(", ", $html_of_links) . '</div>';
  }

  /**
   * Helper function to return HTML for link to the provided entity.
   *
   * @param object $entity
   *  The referenced entity by way of the node's field_member_of->entity or one
   * of the referecedEntities().
   * @return string
   *  This is the link for the node.
   */
  private function _get_html_of_entity($entity) {
    $first_title = $entity->field_title[0];
    $view = ['type' => 'complex_title_formatter'];
    $first_title_view = $first_title->view($view);
    $title = \Drupal::service('renderer')->render($first_title_view);

    // make the link and set the title according to the $parent_title derived above.
    $link = $entity->toLink();
    $link->setText($title);
    return $link->toString();
  }

}
