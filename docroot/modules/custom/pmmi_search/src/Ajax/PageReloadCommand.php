<?php

namespace Drupal\pmmi_search\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an Ajax command for refreshing page.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.pageReload.
 */
class PageReloadCommand implements CommandInterface {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'pageReload',
    ];
  }

}
