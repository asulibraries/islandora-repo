<?php

namespace Drupal\repo_bento_search;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class DataverseApiService.
 */
class DataverseApiService implements BentoApiInterface {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new DataverseApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactory $configFactory) {
    $this->httpClient = $http_client;
    $this->configFactory = $configFactory;
  }


  // https://dataverse-test.lib.asu.edu/

  /**
   * Gets search results from the API.
   *
   * @param string $term
   *   The query string.
   *
   * @param string $limit
   *   The number of results to limit to.
   */
  public function getSearchResults(string $term, int $limit = 10) {
    try {
      $config = $this->configFactory->get('repo_bento_search.bentosettings');
      $base_url = $config->get('dataverse_api_url');
      if (!$base_url) {
        \Drupal::logger('dataverse api service')->warning("No URL set for Dataverse: see /admin/config/bento_search/settings");
        return;
      }

      $request = $this->httpClient->request('GET', $base_url . "?q=" . $term . "&per_page=" . $limit);
      if ($request->getStatusCode() == 200) {
      } else {
        \Drupal::logger('dataverse api service')->warning("Unable to reach dataverse with response: " . print_r($request, TRUE));
      }
      // \Drupal::logger('repo api')->info(print_r($request, TRUE));
      $body = $request->getBody()->getContents();
      dsm(print_r($body, TRUE));
    } catch (ClientException $e) {
      \Drupal::logger('dataverse api service')->warning("Unable to reach dataverse with response: " . $e);
    }
  }
}
