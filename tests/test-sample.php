<?php
/**
 * Class SampleTest
 *
 * @package Beyonic_Payment_Gateway
 */

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	
}
function test_sample_string() {

		$string = 'Unit tests are sweet1';

		$this->assertEquals( 'Unit tests are sweet', $string );
		$this->assertNotEquals( 'Unit tests suck', $string );
	}

}
