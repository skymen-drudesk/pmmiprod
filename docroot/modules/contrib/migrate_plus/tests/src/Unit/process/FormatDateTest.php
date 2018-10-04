<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_plus\Unit\process\SkipOnValueTest.
 */

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate_plus\Plugin\migrate\process\FormatDate;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the format date process plugin.
 *
 * @group migrate_plus
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\FormatDate
 */
class FormatDateTest extends MigrateProcessTestCase {

  /**
   * Tests that missing configuration will throw an exception.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage Format date plugin is missing from_format configuration.
   */
  public function testMigrateExceptionMissingFromFormat() {
    $configuration = [
      'from_format' => '',
      'to_format' => 'Y-m-d',
    ];

    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that missing configuration will throw an exception.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage Format date plugin is missing to_format configuration.
   */
  public function testMigrateExceptionMissingToFormat() {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => '',
    ];

    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests transformation.
   *
   * @covers ::transform
   *
   * @dataProvider datesDataProvider
   */
  public function testTransform($fromFormat, $toFormat, $value, $expected) {
    $configuration = [
      'from_format' => $fromFormat,
      'to_format' => $toFormat,
    ];

    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $actual = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'field_date');

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider of test dates.
   *
   * @return array
   *   Array of date formats and actual/expected values.
   */
  public function datesDataProvider() {
    return [
      ['m/d/Y', 'Y-m-d', '01/05/1955', '1955-01-05'],
      ['m/d/Y H:i:s', 'Y-m-d\TH:i:s', '01/05/1955 10:43:22', '1955-01-05T10:43:22'],
    ];
  }

}
