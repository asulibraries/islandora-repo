<?php

namespace Drupal\asu_mods\Encoder;

use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Encodes a node as a MODS (XML) record.
 */
class ModsEncoder extends XmlEncoder {

  const ROOT_NODE_NAME = 'xml_root_node_name';
  const MACHINE_FIELDS = [
    'nid',
    'uid',
    'changed',
    'created',
    'description',
    'name',
    'label',
    'status',
  ];
  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected $format = 'mods';

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, [$this->format, 'form']);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    if ($format === 'xml') {
      return parent::decode($data, $format, $context);
    }
    elseif ($format === 'form') {
      return $data;
    }
  }

  /**
   * Returns field values based on provided config.
   *
   * Called by processNode, this function determines what value OR subvalue
   * should be returned based on the provided MODS element/attribute to
   * entity field value/property.
   *
   * E.g. given the field_name 'field_title' and the configuration
   * ```
   * _top: titleInfo
   * '@supplied': field_supplied
   * title:
   *   '#': field_main_title
   * nonSort: field_nonsort
   * subTitle: field_subtitle
   * ```
   * will result in a titleInfo element with field_supplied providing the
   * value for the `@supplied' attribute whith a child elements 'title',
   * 'nonSort', and 'subTitle' provided by field_main_title, field_nonsort,
   * and field_subtitle, respectively.
   *
   * The configuration also supports using field properties, such as
   * `'@authority': field_authority_link/source`, where the authority attribute
   * will be provided by the field_authority_link's source property.
   *
   * Literal values can also be used in the configuration, e.g.
   * `'@valueURI': 'http://vocab.getty.edu/page/aat/300380321'` where the
   * valueURI attribute is populated with the provided literal URI value.
   *
   * @param mixed $data
   *   Entity being processed.
   * @param mixed $field_name
   *   The entity's field being processed. Why the field name would be an array
   *   instead of a string, I have no idea.
   * @param array|string $config
   *   Key-value pairs of MODS elements or attributes to entity field values or
   *   properties.
   * @param string|Null $sub_field
   *   A field's property name for extraction.
   */
  private static function getFieldValues($data, $field_name, $config, $sub_field = NULL) {
    if (!is_array($field_name) && str_contains($field_name, '/')) {
      $field_name_parts = explode('/', $field_name);
      $field_name = $field_name_parts[0];
      $sub_part = $field_name_parts[1];
    }
    $return_vals = $vals = [];

    if ((!is_array($field_name) && str_contains($field_name, 'field_')) || (in_array($field_name, self::MACHINE_FIELDS))) {
      if ($data->hasField($field_name)) {
        $field = $data->get($field_name);
        $vals = $field->getValue();
        if (is_array($vals) && count($vals) > 0 && array_key_exists('target_id', $vals[0])) {
          $vals = $field->referencedEntities();
        }
        if ($field_name == "uid") {
          if (get_class($data) == 'Drupal\user\Entity\User') {
            $vals = [$data->getAccountName()];
          }
        }
      }
    }
    else {
      $vals = [$field_name];
    }

    if (is_array($config) && array_key_exists('#', $config) && $config['#'] == $field_name && count($config) == 1) {
      $vals = $vals;
    }
    elseif (is_array($config)) {
      $i = 0;
      foreach ($vals as $val) {
        $field_arr = [];
        foreach ($config as $ck => $cv) {
          if ($field_name == "field_linked_agent") {
            $rel_type = $field->getValue()[$i]['rel_type'];
            $rel_type = str_replace("barrettrelators:", "", $rel_type);
            $rel_type = str_replace("relators:", "", $rel_type);
          }
          if ($cv == "bundle") {
            $cv = $val->bundle();
            switch ($cv) {
              // MODS schema requires "personal".
              case 'person':
                $cv = 'personal';
                break;

              // MODS schema requires "Corporate".
              case 'corporate_body':
                $cv = 'corporate';
                break;
            }
          }
          elseif ($ck == "@supplied") {
            $cv = $field->value;
            $cv = ($cv) ? "yes" : "no";
          }
          if (!is_array($cv)) {
            // Like nonSort: "field_nonsort".
            if ($cv == $field_name) {
              if (is_array($val)) {
                $val = $val['value'];
              }
              $field_arr[$ck] = $val;
            }
            else {
              if (str_contains($cv, "/name")) {
                $cv = str_replace('/name', '', $cv);
                $field_arr[$ck] = self::getFieldValues($val, $cv, $ck, 'name');
              }
              else {
                if ($ck == "@supplied") {
                  if ($cv == 'yes') {
                    $field_arr[$ck] = self::getFieldValues($val, $cv, $ck);
                  }
                }
                elseif (str_contains($cv, 'field_') || (in_array($cv, self::MACHINE_FIELDS))) {
                  $field_arr[$ck] = self::getFieldValues($val, $cv, $ck);
                }
                else {
                  $field_arr[$ck] = $cv;
                }
              }
            }
          }
          else {
            foreach ($cv as $sub_ck => $sub_cv) {
              if ($sub_ck == "roleTerm") {
                $sub_cv['#'] = $rel_type;
              }
              if (is_array($val)) {
                $temp_val = $data;
              }
              else {
                $temp_val = $val;
              }
              $returned = self::getFieldValues($temp_val, $sub_cv, $sub_ck);
              if (!empty($returned)) {
                // Keys prefixed with '@' turn into XML attributes which can
                // only have a single value, so we'll give them the first one.
                if (str_starts_with($sub_ck, '@')) {
                  $field_arr[$ck][$sub_ck] = $returned[0];
                }
                else {
                  $field_arr[$ck][$sub_ck] = $returned;
                }
              }
            }
          }
          $other_arr[] = $field_arr;
        }
        $return_vals[] = $field_arr;
        $i++;
      }
      if (!isset($other_arr) || (count($other_arr) > count($return_vals))) {
        return $return_vals;
      }
      else {
        return $other_arr;
      }
    }
    if ($sub_field) {
      $vals = $data->get($field_name)->referencedEntities();
    }
    foreach ($vals as $val) {
      if (is_object($val)) {
        if ($sub_field && (str_contains($sub_field, 'field_') || (in_array($sub_field, self::MACHINE_FIELDS)))) {
          if (str_contains($sub_field, '/')) {
            $sub_field_name_parts = explode('/', $sub_field);
            $sub_field_temp = $val->get($sub_field_name_parts[0])->getValue()[$sub_field_name_parts[1]];
            $val = $sub_field_temp;
          }
          else {
            $val = $val->get($sub_field)->getValue();
          }
        }
        else {
          $val = $config;
        }
      }
      if (is_array($val) && array_key_exists('value', $val)) {
        $val = $val['value'];
      }
      elseif (is_array($val) && array_key_exists(0, $val) && is_array($val[0])) {
        if (array_key_exists('value', $val[0])) {
          $val = $val[0]['value'];
        }
        else {
          $val = $val[0][0];
        }
      }
      elseif (is_array($val) && count($val) == 1) {
        $val = $val[0];
      }
      if (!empty($sub_part)) {
        $return_vals[] = $val[$sub_part];
      }
      else {
        $return_vals[] = $val;
      }
    }
    if (count($return_vals) == 1) {
      $return_vals = $return_vals[0];
    }
    return $return_vals;
  }

  /**
   * Builds an array from a node for XML serialization based on passed config.
   *
   * The provided config maps a content type's fields to their MODS structure.
   * However, this function focuses on the first level of mapping.
   *
   * E.g. field_rich_description's value is the value of MODS' abstract and
   * field_title is a 'titleInfo'.
   *
   * The more complex aspects of mapping is done by getFieldValues.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $mods_config
   *   Array of field mapping configurations.
   * @param mixed $node
   *   Node being processed.
   *
   * @return array
   *   Array representing a MODS structure
   */
  public function processNode(ImmutableConfig $mods_config, $node) {
    $new_data = [];
    foreach ($mods_config->getRawData() as $field_name => $field_config) {
      if (!is_array($field_config)) {
        $simple_data = $this->getFieldValues($node, $field_name, $field_config);
        $new_data[$field_config][] = [
          '#' => $simple_data,
        ];
      }
      else {
        if (array_key_exists('_top', $field_config)) {
          $top_level_elem = $field_config['_top'];
          if (!array_key_exists($top_level_elem, $new_data)) {
            $new_data[$top_level_elem] = [];
          }
          unset($field_config['_top']);
        }

        $complex_data = $this->getFieldValues($node, $field_name, $field_config);
        if (is_array($complex_data)) {
          if (count($complex_data) == 0) {
            continue;
          }
          foreach ($complex_data as $cp) {
            if (is_array($cp)) {
              if ($top_level_elem == "recordInfo" || $top_level_elem == "originInfo") {
                foreach ($cp as $kk => $vv) {
                  $new_data[$top_level_elem][0][$kk] = $vv;
                }
              }
              else {
                $new_data[$top_level_elem][] = [
                  '#' => $cp,
                ];
              }
            }
          }
        }
        else {
          if (!$complex_data || $complex_data == "") {
            continue;
          }
          $new_data[$top_level_elem][] = [
            '#' => $complex_data,
          ];
        }
      }
    }

    return $this->pruneEmptyArrays($new_data);
  }

  /**
   * Removes 'twig' array keys with empty array value (no 'leaves').
   *
   * Follows array key 'branches' to the last key 'twig' referencing an
   * array of literal value 'leaves'.
   *
   * E.g.
   * ```
   * [
   *   'twig 1' => [],
   *   'twig 2' => ['leaf 1'],
   *   'branch 1' => [
   *     'twig 3' => [],
   *    ],
   * ]
   * ```
   * becomes
   * ```
   * [
   *   'twig 2' => ['leaf 1'],
   *   'branch 1' => []
   * ]
   * ```
   *
   * In this example 'branch 1' still remains because it was a 'branch'
   * even if it's 'twig' had no leaves (was a key to an empty array)
   * whereas 'twig 1' had no leaves and so was removed.
   * Note, if the example array was passed through the function twice,
   * branch 1 would be removed as it became a twig during the first pass.
   */
  private function pruneEmptyArrays($data) {
    foreach ($data as $k => $v) {
      if (is_array($v)) {
        if (count($v) < 1) {
          unset($data[$k]);
        }
        else {
          $data[$k] = $this->pruneEmptyArrays($v);
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    // @todo set mods namespaces.
    $mods_config = \Drupal::config('asu_mods.asu_repository_item');
    $all_records = [];
    if (is_array($data)) {
      $context[self::ROOT_NODE_NAME] = 'modsCollection';
      foreach ($data as $node) {
        $new_data = $this->processNode($mods_config, $node);
        $all_records['mods'][] =
        [
          '#' => $new_data,
        ];
      }
    }
    else {
      $context[self::ROOT_NODE_NAME] = 'mods';
      $new_data = $this->processNode($mods_config, $data);
      $all_records['#'] = $new_data;
    }

    $xml = parent::encode($all_records, $format, $context);
    if (is_array($data)) {
      $xml = str_replace("<modsCollection>", '<modsCollection xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.loc.gov/mods/v3" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd">', $xml);
    }
    else {
      $xml = str_replace("<mods>", '<mods xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.loc.gov/mods/v3" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd">', $xml);
    }
    $search = [
      '<metadata-xml><![CDATA[',
      ']]></metadata-xml>',
      '<mods-string>',
      '</mods-string>',
    ];
    $replace = [
      '',
      '',
      '',
      '',
    ];

    return str_replace($search, $replace, $xml);
  }

}
