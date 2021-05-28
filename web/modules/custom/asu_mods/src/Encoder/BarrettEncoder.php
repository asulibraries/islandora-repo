<?php

namespace Drupal\asu_mods\Encoder;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

class BarrettEncoder extends XmlEncoder {

  const ROOT_NODE_NAME = 'xml_root_node_name';
  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected $format = 'barrett';

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
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    $context[self::ROOT_NODE_NAME] = 'xml';
    $new_data = [];
    foreach($data as $k=>$v) {
      array_push($new_data, ['user' => $v]);
    }
    $nd = ['submitted_list' => $new_data];
    $xml = parent::encode($nd, $format, $context);



    $search = [
      '<metadata-xml>',
      ']]></metadata-xml>',
      '<item>',
      '</item>',
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
