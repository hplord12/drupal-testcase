<?php
namespace Drupal\Tests\testing_example\Unit;
use Drupal\Tests\UnitTestCase;
use Drupal\testing_example\Unit;
/**
 * Simple test to ensure that asserts pass.
 *
 * @group testing_example
 */
class UnitTest extends UnitTestCase {
  protected $unit;
  /**
   * Before a test method is run, setUp() is invoked.
   * Create new unit object.
   */
  public function setUp() {
    $this->unit = new Unit();
  }
  /**
   * @covers Drupal\testing_example\Unit::setLength
   */
  public function testSetLength() {
    $this->assertEquals(0, $this->unit->getLength());
    $this->unit->setLength(9);
    $this->assertEquals(9, $this->unit->getLength());
  }
  /**
   * @covers Drupal\testing_example\Unit::getLength
   */
  public function testGetLength() {
    $this->unit->setLength(9);
    $this->assertNotEquals(10, $this->unit->getLength());
  }
  /**
   * Once test method has finished running, whether it succeeded or failed, tearDown() will be invoked.
   * Unset the $unit object.
   */
  public function tearDown() {
    unset($this->unit);
  }
}