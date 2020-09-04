<?php

namespace Drupal\repo_bento_search;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class LegacyRepoApiService.
 */
class LegacyRepoApiService implements BentoApiInterface {

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
   * Constructs a new LegacyRepoApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactory $configFactory) {
    $this->httpClient = $http_client;
    $this->configFactory = $configFactory;
  }

  /**
   * Gets search results from the API.
   *
   * @param string $term
   *   The query string.
   *
   * @param string $limit
   *   The number of results to limit to.
   */
  public function getSearchResults(string $term, int $limit=10) {
    try {
      // Note: the API is hard-coded to 10 results as a time.
      $config = $this->configFactory->get('repo_bento_search.bentosettings');
      $base_url = $config->get('legacy_repository_api_url');
      if (!$base_url) {
        \Drupal::logger('legacy repository api service')->warning("No URL set for Legacy Repository: see /admin/config/bento_search/settings");
        return;
      }
      $token = $config->get('legacy_repository_api_key');
      if (!$token) {
        \Drupal::logger('legacy repository api service')->warning("No API Key set for Legacy Repository: see /admin/config/bento_search/settings");
        return;
      }

      $request = $this->httpClient->request('GET', $base_url . "?q=" . $term , [
        'headers' => [
          'Authorization' => 'Token ' . $token
        ]
      ]);
      if ($request->getStatusCode() == 200) {
      } else {
        \Drupal::logger('legacy repository api service')->warning("Unable to reach legacy repository with response: " . print_r($request, TRUE));
      }
      $body = $request->getBody()->getContents();
      dsm(print_r($body, TRUE));

    } catch (ClientException $e) {
      \Drupal::logger('legacy repository api service')->warning("Unable to reach legacy repository with response: " . $e);

    }

  }

}
