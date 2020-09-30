<?php

namespace Drupal\repo_bento_search;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

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
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new LegacyRepoApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactory $configFactory, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Gets search results from the API.
   *
   * @param string $term
   *   The query string.
   * @param int $limit
   *   The number of results to limit to.
   */
  public function getSearchResults(string $term, int $limit = 10) {
    try {
      // Note: the API is hard-coded to 10 results as a time.
      $config = $this->configFactory->get('repo_bento_search.bentosettings');
      $base_url = $config->get('legacy_repository_api_url');
      if (!trim($base_url)) {
        $this->logger->warning("No URL set for Legacy Repository: see /admin/config/bento_search/settings");
        return;
      }
      $token = $config->get('legacy_repository_api_key');
      if (!$token) {
        $this->logger->warning("No API Key set for Legacy Repository: see /admin/config/bento_search/settings");
        return;
      }

      if (trim($base_url) <> '') {
        $request = $this->httpClient->request('GET', $base_url . "?q=" . $term .
            "&count=" . $limit, [
          'headers' => [
            'Authorization' => 'Token ' . $token,
          ],
        ]);
        if ($request->getStatusCode() == 200) {
          $body = $request->getBody()->getContents();
          $this->logger->info(print_r($body, TRUE));
          // dsm(print_r($body, TRUE));
          return $body;
        }
        else {
          $this->logger->warning("Unable to reach legacy repository with response: " . print_r($request, TRUE));
        }
      }
    }
    catch (ClientException $e) {
      $this->logger->warning("Unable to reach legacy repository with response: " . $e);
    }
  }

}
