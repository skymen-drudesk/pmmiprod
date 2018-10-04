<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use phpseclib\Crypt\Rijndael;

/**
 * Class PMMISSOCrypt.
 *
 * @package Drupal\pmmi_sso
 */
class PMMISSOCrypt {

  /**
   * Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Pure-PHP implementation of Rijndael.
   *
   * @var \phpseclib\Crypt\Rijndael
   */
  protected $cipher;

  /**
   * Constructor for Personify SSO Crypt service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('pmmi_sso.settings');
    $this->cipher = new Rijndael();
    $this->cipher->setBlockLength(128);
    $this->cipher->setKeyLength(128);
    $this->cipher->setKey(hex2bin($this->config->get('vp')));
    $this->cipher->setIV(hex2bin($this->config->get('vib')));
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($string) {
    return bin2hex($this->cipher->encrypt($string));
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($string) {
    return $this->cipher->decrypt(hex2bin($string));
  }

}
