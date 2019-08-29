<?php

/**
 * @file
 * Contains \Drupal\flysystem_Magiq\Flysystem\Magiq.
 */

namespace Drupal\flysystem_magiq\Flysystem;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;
use Drupal\flysystem_magiq\Flysystem\MagiqAdapter;

/**
 * Drupal plugin for the "Magiq" Flysystem adapter.
 *
 * @Adapter(id = "Magiq")
 */
class Magiq implements FlysystemPluginInterface {

  use FlysystemUrlTrait {
    getExternalUrl as getDownloadlUrl;
  }

  use ImageStyleGenerationTrait;

  /**
   * The Magiq client.
   *
   * @var \Magiq\Client
   */
  protected $client;

  /**
 * The Magiq client ID.
 *
 * @var string
 */
  protected $username;
  /**
   * The Magiq client ID.
   *
   * @var string
   */
  protected $password;

  /**
   * The path prefix inside the Magiq folder.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The Magiq API token.
   *
   * @var string
   */
  protected $token;

  /**
   * Whether to serve files via Magiq.
   *
   * @var bool
   */
  protected $usePublic;

  /**
   * Constructs a Magiq object.
   *
   * @param array $configuration
   *   Plugin configuration array.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration) {
    $this->username = $configuration['username'];
    $this->password = $configuration['password'];
    $this->usePublic = !empty($configuration['public']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    try {
      $adapter = new MagiqAdapter($this->getClient(), $this->prefix);
    }

    catch (\Exception $e) {
      $adapter = new MissingAdapter();
    }

    return $adapter;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl($uri) {
    if ($this->usePublic) {
      return $this->getPublicUrl($uri);
    }

    return $this->getDownloadlUrl($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    try {
      $info = $this->getClient()->getAccountInfo();
    }
    catch (\Exception $e) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => 'The Magiq client failed with: %error.',
        'context' => ['%error' => $e->getMessage()],
      ]];
    }

    return [];
  }

  /**
   * Returns the public Magiq URL.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return string|false
   *   The public URL, or false on failure.
   */
  protected function getPublicUrl($uri) {
    $target = $this->getTarget($uri);

    // Quick exit for existing files.
    if ($link = $this->getSharableLink($target)) {
      return $link;
    }

    // Support image style generation.
    if ($this->generateImageStyle($target)) {
      return $this->getSharableLink($target);
    }

    return FALSE;
  }

  /**
   * Returns the Magiq sharable link.
   *
   * @param string $target
   *   The file target.
   *
   * @return string|bool
   *   The sharable link, or false on failure.
   */
  protected function getSharableLink($target) {
    try {
      $link = $this->getClient()->createShareableLink('/' . $target);
    }
    catch (\Exception $e) {}

    if (empty($link)) {
      return FALSE;
    }

    $uri = (new Uri($link))->withHost('dl.Magiqusercontent.com');

    return (string) Uri::withoutQueryValue($uri, 'dl');
  }

  /**
   * Returns the Magiq client.
   *
   * @return \Magiq\Client
   *   The Magiq client.
   */
  protected function getClient() {
    if (!isset($this->client)) {
      $this->client = new Client($this->password, $this->username);
    }

    return $this->client;
  }

}
