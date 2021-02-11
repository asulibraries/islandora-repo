<?php

namespace Drupal\asu_mods\Encoder;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 *
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
   * Plucks the data out of a field.
   */
  private static function get_field_values($data, $field_name, $config, $sub_field = NULL) {
    if (str_contains($field_name, '/')) {
      $field_name_parts = explode('/', $field_name);
      $field_name = $field_name_parts[0];
      $sub_part = $field_name_parts[1];
    }
    $return_vals = [];

    if (str_contains($field_name, 'field_') || (in_array($field_name, self::MACHINE_FIELDS))) {
      if ($data->hasField($field_name)) {
        $field = $data->get($field_name);
        $vals = $field->getValue();
        if (is_array($vals) && count($vals) > 0 && array_key_exists('target_id', $vals[0])) {
          $vals = $field->referencedEntities();
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
            $rel_type = str_replace("relators:", "", $rel_type);
          }
          if ($cv == "bundle") {
            $cv = $val->bundle();
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
              if (is_array($cv)) {
                $arr_cv = $cv;
              }
              if (str_contains($cv, "/name")) {
                $cv = str_replace('/name', '', $cv);
                $field_arr[$ck] = self::get_field_values($val, $cv, $ck, 'name');
              }
              else {
                $field_arr[$ck] = self::get_field_values($val, $cv, $ck);
              }
            }
          }
          else {
            foreach ($cv as $sub_ck => $sub_cv) {
              if ($sub_cv == "rel_type") {
                $sub_cv = $rel_type;
              }
              if (is_array($val)) {
                $temp_val = $data;
              }
              else {
                $temp_val = $val;
              }
              if (is_array($sub_cv)) {
                $arr_cv = $sub_cv;
              }
              $field_arr[$ck][$sub_ck] = self::get_field_values($temp_val, $sub_cv, $sub_ck);
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
      elseif (is_array($val) && is_array($val[0])) {
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
      if (isset($sub_part)) {
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
   * Processes the data of a single node.
   */
  public function process_node($mods_config, $node) {
    $new_data = [];
    foreach ($mods_config->getRawData() as $field_name => $field_config) {
      if (!is_array($field_config)) {
        if (is_array($field_name)) {
          $arr_field = $field_name;
        }
        $simple_data = $this->get_field_values($node, $field_name, $field_config);
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
        if (is_array($field_name)) {
          $arr_field = $field_name;
        }

        $complex_data = $this->get_field_values($node, $field_name, $field_config);
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
    return $new_data;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    // TODO set mods namespaces.
    $mods_config = \Drupal::config('asu_mods.asu_repository_item');
    $all_records = [];
    if (is_array($data)) {
      $context[self::ROOT_NODE_NAME] = 'modsCollection';
      foreach ($data as $node) {
        $new_data = $this->process_node($mods_config, $node);
        $all_records['mods'][] =
        [
          '#' => $new_data,
        ];
      }
    }
    else {
      $context[self::ROOT_NODE_NAME] = 'mods';
      $new_data = $this->process_node($mods_config, $data);
      $all_records[] = $new_data;
    }

    $xml = parent::encode($all_records, $format, $context);
    if (is_array($data)) {
      $xml = str_replace("<modsCollection>", '<modsCollection xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.loc.gov/mods/v3" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-3.xsd">', $xml);
    }
    else {
      $xml = str_replace("<mods>", '<mods xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.loc.gov/mods/v3" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-3.xsd">', $xml);
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
