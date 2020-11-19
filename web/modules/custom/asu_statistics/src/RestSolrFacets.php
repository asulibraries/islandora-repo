<?php

namespace Drupal\asu_statistics;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class DataverseApiService.
 */
class RestSolrFacets implements RESTApiInterface {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new DataverseApiService object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  function call_REST($collection_node_id, $type) {
    $request_url = 'http://localhost:8000/api/';
//    if ($type == 'month_facets') {
//      $request_url .= 'collection_search/' . $collection_node_id .
//          '?format=json&facet=true&facet.field=created';
//    } else
    if ($type == 'sum_filesize') {
      $request_url .= 'collection_sum_filesize/' . $collection_node_id;
    }
    try {
      $response = $this->httpClient->get($request_url);
      $response_body = $response->getBody();
      $status_code = $response->getStatusCode();
      if ($status_code != 200) {
        \Drupal::logger('asu_statistics')->warning($status_code . " returned from Solr : <pre>" . print_r($response, TRUE) . "</pre>");
      }
      else {
        $resource = json_decode($response_body, TRUE);
        if (array_key_exists('result', $resource) && $resource['result'] == 'error') {
          \Drupal::logger('asu_statistics')->warning("Error returned from Solr : <pre>" . print_r($resource, TRUE) . "</pre>");
          $result = 0;
        }
        else {
          $result = (array_key_exists(0, $resource) ? (int) $resource[0][$matomo_metric] : 0);
        }
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('asu_statistics')->warning("Unable to return data from Solr : <pre>" . $e->getMessage() . "</pre>");
    }
    return $result;
  }
}