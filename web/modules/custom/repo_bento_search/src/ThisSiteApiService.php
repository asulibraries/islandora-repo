<?php

namespace Drupal\repo_bento_search;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * Class ThisSiteApiService.
 */
class ThisSiteApiService implements BentoApiInterface {

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
   * Constructs a new ThisSiteApiService object.
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
      $request_url = \Drupal::request()->getSchemeAndHttpHost() .
        '/api/search?search_api_fulltext=' . $term . '&q=' . $term . '&format=json';

      $request = $this->httpClient->request('GET', $request_url);
      if ($request->getStatusCode() == 200) {
        $body = $request->getBody()->getContents();
        $this->logger->info(print_r($body, TRUE));
        // dsm(print_r($body, TRUE));
        return $body;
      }
      else {
        $this->logger->warning("Unable to reach the site with response: " . print_r($request, TRUE));
      }
    }
    catch (ClientException $e) {
      $this->logger->warning("Unable to reach the site with response: " . $e);
    }
  }

}
