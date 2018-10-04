<?php

namespace Drupal\pmmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Copyright' Block
 *
 * @Block(
 *   id = "copyright_block",
 *   admin_label = @Translation("Copyright block"),
 * )
 */
class CopyrightBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $year = date('Y');
    $output = t('&copy; Copyright ');
    $output .= t($year);
    $output .= '<a href="http://www.pmmi.org" target=_blank>';
    $output .= t(' PMMI');
    $output .= '</a>';

    return array(
      '#markup' => $output,
    );
  }
}
