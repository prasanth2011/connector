<?php

/**
 * @file
 * Contains \Drupal\flysystem_dropbox\Flysystem\Dropbox.
 */

namespace Drupal\magiq\Flysystem;

use \SoapClient;
use Drupal\magiq\Adapter\MagiqAdapter as MagiqAdapter;
use Drupal\magiq\Client\MagiqClient;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;


/**
 * Drupal plugin for the "Dropbox" Flysystem adapter.
 *
 * @Adapter(id = "dropbox")
 */
class Magiq implements FlysystemPluginInterface {

  use FlysystemUrlTrait {
    getExternalUrl as getDownloadlUrl;
  }

  use ImageStyleGenerationTrait;

  /**
   * The Dropbox client.
   *
   * @var \Dropbox\Client
   */
  protected $client;

  /**
   * The Dropbox client ID.
   *
   * @var string
   */
  protected $username;

  /**
   * The path prefix inside the Dropbox folder.
   *
   * @var string
   */
  protected $password;

  /**
   * The Dropbox API token.
   *
   * @var string
   */
  protected $token;

  /**
   * Whether to serve files via Dropbox.
   *
   * @var bool
   */
  protected $usePublic;
    /**
     * Whether to serve files via Dropbox.
     *
     * @var bool
     */
    protected $apiUrl;


    protected $configuration;
  /**
   * Constructs a Dropbox object.
   *
   * @param array $configuration
   *   Plugin configuration array.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
    public function __construct(array $configuration) {

        \Drupal::logger('magique')->error('reached');
        $this->configuration = $configuration;

        $this->user = $configuration['username'];
        $this->password = $configuration['password'];
        $this->apiUrl = $configuration['apiUrl'];
        $this->setClient( $this->apiUrl);
     //   $this->client = new SoapClient("https://docs.narrandera.nsw.gov.au/srv.asmx?WSDL");
      //  $this->authenticate();
       // watchdog('reached','reached');
    }

    /**
     * Set Http Client.
     *
     * @param \GuzzleHttp\Client $client
     */
    protected function setClient(string $api)
    {
        $this->client = new SoapClient($api);
        $params = array('UID'=> $this->username, 'PWD'=> $this->password);
        $token = $this->client->__soapCall('AuthenticateUser', array($params));
    }
     protected function authenticate(){
        $params = array('UID'=> $this->username, 'PWD'=> $this->password);
        $token = $this->client->__soapCall('AuthenticateUser', array($params));

     }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
      \Drupal::logger('magiq')->error('reached2');
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
        'message' => 'The Dropbox client failed with: %error.',
        'context' => ['%error' => $e->getMessage()],
      ]];
    }

    return [];
  }

  /**
   * Returns the public Dropbox URL.
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
   * Returns the Dropbox sharable link.
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

    $uri = (new Uri($link))->withHost('dl.dropboxusercontent.com');

    return (string) Uri::withoutQueryValue($uri, 'dl');
  }

  /**
   * Returns the Dropbox client.
   *
   * @return \Dropbox\Client
   *   The Dropbox client.
   */
  protected function getClient() {
    if (!isset($this->client)) {
      $this->client = new MagiqClient($this->token, null);
    }
    return $this->client;
  }

}
