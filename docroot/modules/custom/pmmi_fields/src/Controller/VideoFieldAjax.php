<?php

namespace Drupal\pmmi_fields\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class VideoFieldAjax.
 *
 * @package Drupal\pmmi_fields\Controller
 */
class VideoFieldAjax extends ControllerBase {

  /**
   * Replace main video on Video CT.
   */
  public function replaceVideo($node, $js = 'nojs') {
    if ($js == 'nojs') {
      return new RedirectResponse('/node/' . $node);
    }

    $response = new AjaxResponse();
    $entity = Node::load($node);

    if ($entity->access('view')) {
      $node_render = \Drupal::entityTypeManager()
        ->getViewBuilder($entity->getEntityTypeId())
        ->view($entity, 'video_ajax_mode');;
      $node_rendered = render($node_render);
      $response->addCommand(new ReplaceCommand('#video-node-info', $node_rendered));
    }
    return $response;
  }

}
