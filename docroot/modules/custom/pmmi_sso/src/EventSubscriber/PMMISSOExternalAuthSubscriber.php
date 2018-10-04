<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOExternalAuthSubscriber.
 */
class PMMISSOExternalAuthSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::AUTHMAP_ALTER][] = ['stripSsoPrefix'];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Externalauth module will add a "pmmi_sso_" prefix to usernames that are
   * registered using the externalauth module. We don't want that, so remove
   * the prefix.
   *
   * @param ExternalAuthAuthmapAlterEvent $event
   *   The authmap alter event from the externalauth module.
   *
   * @see https://www.drupal.org/node/2798323
   */
  public function stripSsoPrefix(ExternalAuthAuthmapAlterEvent $event) {
    if (strpos($event->getUsername(), 'pmmi_sso_') === 0) {
      $event->setUsername(substr($event->getUsername(), 9));
    }
    if (strpos($event->getAuthname(), 'pmmi_sso_') === 0) {
      $event->setAuthname(substr($event->getAuthname(), 9));
    }
  }

}
