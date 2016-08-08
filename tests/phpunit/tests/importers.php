<?php

/**
 * Testcase for the WordPoints_Importers class.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * Tests for the WordPoints_Importers class.
 *
 * @since 1.0.0
 *
 * @group importers
 */
class WordPoints_Importers_Test extends WP_UnitTestCase {

	/**
	 * Backup of the importers when the test begins.
	 *
	 * @since 1.0.0
	 *
	 * @var array[]
	 */
	protected $_backup_importers;

	/**
	 * @since 1.0.0
	 */
	public function setUp() {

		parent::setUp();

		$this->_backup_importers = WordPoints_Importers::get();

		WordPoints_Importers::register(
			'test'
			, array( 'class' => 'WordPoints_Importer_Mock', 'name' => 'Test' )
		);

	}

	/**
	 * @since 1.0.0
	 */
	public function tearDown() {

		$importers = WordPoints_Importers::get();

		foreach ( $this->_backup_importers as $slug => $args ) {

			if ( ! isset( $importers[ $slug ] ) ) {
				WordPoints_Importers::register( $slug, $args );
			}

			unset( $importers[ $slug ] );
		}

		foreach ( $importers as $slug => $args ) {
			WordPoints_Importers::deregister( $slug );
		}

		parent::tearDown();
	}

	/**
	 * Test registration.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::register
	 * @covers WordPoints_Importers::get
	 */
	public function test_register() {

		WordPoints_Importers::register(
			__METHOD__
			, array( 'class' => 'WordPoints_Importer_Mock', 'name' => 'Test' )
		);

		$importers = WordPoints_Importers::get();

		$this->assertArrayHasKey( 'test', $importers );
		$this->assertEquals(
			array( 'class' => 'WordPoints_Importer_Mock', 'name' => 'Test' )
			, $importers['test']
		);
	}

	/**
	 * Test that deregister() deregisters the importer.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::register
	 * @covers WordPoints_Importers::get
	 */
	public function test_deregister() {

		WordPoints_Importers::deregister( 'test' );

		$importers = WordPoints_Importers::get();

		$this->assertArrayNotHasKey( 'test', $importers );
	}

	/**
	 * Test that is_registered() returns true for a registered importer.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::is_registered
	 */
	public function test_is_registered() {

		$this->assertTrue( WordPoints_Importers::is_registered( 'test' ) );
	}

	/**
	 * Test that is_registered() returns false for an unregistered importer.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::is_registered
	 */
	public function test_is_registered_unregistered() {

		WordPoints_Importers::deregister( 'test' );

		$this->assertFalse( WordPoints_Importers::is_registered( 'test' ) );
	}

	/**
	 * Test that get_importer() returns false for an unregistered importer.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::get_importer
	 */
	public function test_get_unregistered_importer() {

		WordPoints_Importers::deregister( 'test' );

		$this->assertFalse( WordPoints_Importers::get_importer( 'test' ) );
	}

	/**
	 * Test that get_importer() returns an importer object.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importers::get_importer
	 */
	public function test_get_importer() {

		$this->assertInstanceOf(
			'WordPoints_Importer_Mock'
			, WordPoints_Importers::get_importer( 'test' )
		);
	}

}

// EOF
