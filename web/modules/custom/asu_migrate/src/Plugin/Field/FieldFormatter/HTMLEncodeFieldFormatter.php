<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'HTMLEncodeFieldFormatter'.
 *
 * @FieldFormatter(
 *   id = "html_encode_field_formatter",
 *   label = @Translation("HTML Encode Field Formatter - CSV export"),
 *   field_types = {
 *     "text_long",
 *     "string_long"
 *   }
 * )
 */
class HTMLEncodeFieldFormatter extends EntityReferenceLabelFormatter {

// @todo - write a settings form to allow setting the field name to return.

  /**
   * {@inheritdoc}
   */
//  public function settingsForm(array $form, FormStateInterface $form_state) {
//    $fieldnames = $this->getFieldnames();
//    $elements['field_name'] = [
//      '#type' => 'select',
//      '#options' => $fieldnames,
//      '#title' => t('Field name'),
//      '#default_value' => '',
//      '#required' => TRUE,
//    ];
//
//    return $elements;
//  }

  private function getFieldnames() {
    // Get the definitions
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'asu_repository_item');

    // Iterate through the definitions
    foreach (array_keys($definitions) as $field_name) {
      // Use getValue() if you want to get an array instead of text.
      if ($field_name <> 'nid') {
        $field_type = $definitions[$field_name]->getType();
        if ($field_type == 'text_long' || $field_type == 'string_long' || $field_type == 'string' || $field_type == 'list_string') {
          $field_label = $definitions[$field_name]->getLabel();
          $values[$field_name] = $field_label . ' [' . $field_type . ']';
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
//  public function viewElements(FieldItemListInterface $items, $langcode) {
//    $elements = parent::viewElements($items, $langcode);
//    foreach ($items as $delta => $item) {
//      // @todo - load the node and get the field value that is configured.
//      // $field_name = $this->getSetting('field_name');
//      $node = $item->entity;
//      $taxo_term = $item->entity;
//    }
//    return $elements;
//  }

}

#0 /var/www/html/drupal/web/core/lib/Drupal/Core/Field/Plugin/Field/FieldFormatter/EntityReferenceFormatterBase.php(132): Drupal\\Core\\Field\\Plugin\\Field\\FieldFormatter\\EntityReferenceFormatterBase->needsEntityLoad()\n
##1 /var/www/html/drupal/web/core/lib/Drupal/Core/Entity/Entity/EntityViewDisplay.php(245): Drupal\\Core\\Field\\Plugin\\Field\\FieldFormatter\\EntityReferenceFormatterBase->prepareView()\n
##2 /var/www/html/drupal/web/core/modules/views/src/Entity/Render/EntityFieldRenderer.php(256): Drupal\\Core\\Entity\\Entity\\EntityViewDisplay->buildMultiple()\n
##3 /var/www/html/drupal/web/core/modules/views/src/Entity/Render/EntityFieldRenderer.php(143): Drupal\\views\\Entity\\Render\\EntityFieldRenderer->buildFields()\n
##4 /var/www/html/drupal/web/core/modules/views/src/Plugin/views/field/EntityField.php(829): Drupal\\views\\Entity\\Render\\EntityFieldRenderer->render()\n
##5 /var/www/html/drupal/web/core/modules/views/src/Plugin/views/field/FieldPluginBase.php(1149): Drupal\\views\\Plugin\\views\\field\\EntityField->getItems()\n
##6 /var/www/html/drupal/web/core/modules/rest/src/Plugin/views/row/DataFieldRow.php(147): Drupal\\views\\Plugin\\views\\field\\FieldPluginBase->advancedRender()\n
##7 /var/www/html/drupal/web/modules/contrib/views_data_export/src/Plugin/views/style/DataExport.php(321): Drupal\\rest\\Plugin\\views\\row\\DataFieldRow->render()\n
##8 /var/www/html/drupal/web/core/modules/rest/src/Plugin/views/display/RestExport.php(432): Drupal\\views_data_export\\Plugin\\views\\style\\DataExport->render()\n
##9 /var/www/html/drupal/web/core/lib/Drupal/Core/Render/Renderer.php(573): Drupal\\rest\\Plugin\\views\\display\\RestExport->Drupal\\rest\\Plugin\\views\\display\\{closure}()\n
##10 /var/www/html/drupal/web/core/modules/rest/src/Plugin/views/display/RestExport.php(433): Drupal\\Core\\Render\\Renderer->executeInRenderContext()\n
##11 /var/www/html/drupal/web/core/modules/views/src/ViewExecutable.php(1533): Drupal\\rest\\Plugin\\views\\display\\RestExport->render()\n
##12 /var/www/html/drupal/web/core/modules/rest/src/Plugin/views/display/RestExport.php(423): Drupal\\views\\ViewExecutable->render()\n
##13 /var/www/html/drupal/web/core/modules/views/src/ViewExecutable.php(1630): Drupal\\rest\\Plugin\\views\\display\\RestExport->execute()\n
##14 /var/www/html/drupal/web/core/modules/views/src/Element/View.php(77): Drupal\\views\\ViewExecutable->executeDisplay()\n
##15 [internal function]: Drupal\\views\\Element\\View::preRenderViewElement()\n
##16 /var/www/html/drupal/web/core/lib/Drupal/Core/Security/DoTrustedCallbackTrait.php(101): call_user_func_array()\n
##17 /var/www/html/drupal/web/core/lib/Drupal/Core/Render/Renderer.php(781): Drupal\\Core\\Render\\Renderer->doTrustedCallback()\n
##18 /var/www/html/drupal/web/core/lib/Drupal/Core/Render/Renderer.php(372): Drupal\\Core\\Render\\Renderer->doCallback()\n
##19 /var/www/html/drupal/web/core/lib/Drupal/Core/Render/Renderer.php(200): Drupal\\Core\\Render\\Renderer->doRender()\n