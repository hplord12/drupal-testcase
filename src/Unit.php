<?php
namespace Drupal\testing_example;
/**
 * Defines a Unit class.
 */
class Unit {
  private $length = 0;
  /**
   * @param int $length
   */
  public function setLength(int $length) {
    $this->length = $length;
  }
  /**
   * @return int
   *   The length of the unit.
   */
  public function getLength() {
    return $this->length;
  }
}