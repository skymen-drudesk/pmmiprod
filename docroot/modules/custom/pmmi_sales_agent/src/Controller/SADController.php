<?php

namespace Drupal\pmmi_sales_agent\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Controller routines for pmmi_sales_agent routes.
 */
class SADController extends ControllerBase {
  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(UserStorageInterface $user_storage, LoggerInterface $logger) {
    $this->userStorage = $user_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * One-time login to update a company.
   *
   * @param int $nid
   *   User ID of the node to get access.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the update a listing form if the information is
   *   correct. If the information is incorrect redirects to company view route
   *   with a message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function updateListingLogin(NodeInterface $node, $timestamp, $hash) {
    $current = \Drupal::time()->getRequestTime();
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $nid = $node->id();
    $last_revision_id = $moderation_info->getLatestRevisionId('node', $nid);
    // Time out, in seconds, until login URL expires.
    $timeout = \Drupal::config('pmmi_sales_agent.mail_settings')
      ->get('one_time_expiration');

    // If one-time update link is not valid - redirect back to a request form.
    if ($current - $timestamp > $timeout || !Crypt::hashEquals($hash, pmmi_sales_agent_hash($timestamp, $last_revision_id + $nid))) {
      drupal_set_message($this->t('You have tried to use a one-time update link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
      $url = Url::fromUri('internal:/sales-agent-directory/update');
      return $this->redirect($url->getRouteName());
    }

    $token = Crypt::randomBytesBase64(55);
    $_SESSION["sad_login_{$nid}"] = $token;
    return $this->redirect(
      'entity.node.edit_form',
      ['node' => $nid],
      [
        'query' => ['sad-login-token' => $token],
        'absolute' => TRUE,
      ]
    );
  }

}
