<?php

/**
 * Testcase for the WordPoints_Importer class.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * Tests for the WordPoints_Importer class.
 *
 * @since 1.0.0
 *
 * @group importers
 */
class WordPoints_Importer_Importer_Test extends WordPoints_PHPUnit_TestCase {

	/**
	 * The mock importer used in the tests.
	 *
	 * @since 1.0.0
	 *
	 * @var WordPoints_Importer_Mock
	 */
	protected $importer;

	/**
	 * The components assigned to the mock importer.
	 *
	 * @since 1.0.0
	 *
	 * @var array[]
	 */
	protected $importer_components;

	/**
	 * @since 1.0.0
	 */
	public function setUp() {

		parent::setUp();

		$this->importer = new WordPoints_Importer_Mock( 'Mock' );
		$this->importer->components = array(
			'points' => array(
				'user_points' => array(
					'label' => 'User points',
					'function' => array( $this->importer, 'do_an_import' ),
					'can_import' => array( $this->importer, 'can_import' ),
				),
			),
		);

		$this->importer_components = $this->importer->components;

		remove_action(
			'wordpoints_import_settings_valid-points'
			, 'wordpoints_importer_validate_points_type_setting'
		);
	}

	/**
	 * Test that it returns true when a component is supported.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::supports_component
	 */
	public function test_supports_supported_component() {

		$this->assertTrue( $this->importer->supports_component( 'points' ) );
	}

	/**
	 * Test that it returns false when a component isn't supported.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::supports_component
	 */
	public function test_supports_unsupported_component() {

		$this->assertFalse( $this->importer->supports_component( 'unsupported' ) );
	}

	/**
	 * Test that it returns the settings for a component.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::get_options_for_component
	 */
	public function test_get_options_for_component() {

		$this->assertSame(
			$this->importer_components['points']
			, $this->importer->get_options_for_component( 'points' )
		);
	}

	/**
	 * Test that it returns the settings for an unsupported component.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::get_options_for_component
	 */
	public function test_get_options_for_unsupported_component() {

		$this->assertSame(
			array()
			, $this->importer->get_options_for_component( 'unsupported' )
		);
	}

	/**
	 * Test that it gives a warning for uninstalled components.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_not_installed() {

		$this->importer->components = array(
			'uninstalled' => array( 'method' => 'do_an_import' ),
		);

		$feedback = new WordPoints_Importer_Tests_Feedback();
		$this->importer->do_import(
			array( 'uninstalled' => array( 'do' => 'yes' ) )
			, $feedback
		);

		$this->assertCount( 1, $feedback->messages['warning'] );

		// The import shouldn't have been performed.
		$this->assertSame( array(), $this->importer->imports );
	}

	/**
	 * Test that it gives a warning for unsupported components.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_not_supported() {

		$this->importer->components = array();

		$feedback = new WordPoints_Importer_Tests_Feedback();
		$this->importer->do_import(
			array( 'points' => array( 'do' => 'yes' ) )
			, $feedback
		);

		$this->assertCount( 1, $feedback->messages['warning'] );

		// The import shouldn't have been performed.
		$this->assertSame( array(), $this->importer->imports );
	}

	/**
	 * Test that it skips a component if validation fails.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_validates_settings() {

		$this->listen_for_filter( 'wordpoints_import_settings_valid-points' );

		add_filter( 'wordpoints_import_settings_valid-points', '__return_false' );

		$this->importer->do_import(
			array( 'points' => array( 'do' => 'yes' ) )
			, new WordPoints_Importer_Tests_Feedback()
		);

		$this->assertSame(
			1
			, $this->filter_was_called( 'wordpoints_import_settings_valid-points' )
		);

		// The import shouldn't have been performed.
		$this->assertSame( array(), $this->importer->imports );
	}

	/**
	 * Test that it skips an unsupported option.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_invalid_option() {

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->do_import(
			array( 'points' => array( 'do' => 'yes' ) )
			, $feedback
		);

		$this->assertCount( 1, $feedback->messages['warning'] );

		// The import shouldn't have been performed.
		$this->assertSame( array(), $this->importer->imports );
	}

	/**
	 * Test that it skips a disabled option.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_disabled_option() {

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->components['points']['user_points']['can_import'] =
			array( $this->importer, 'cant_import' );

		$this->importer->do_import(
			array( 'points' => array( 'user_points' => '1' ) )
			, $feedback
		);

		$this->assertCount( 1, $this->importer->can_imports );
		$this->assertCount( 1, $feedback->messages['warning'] );

		// The import shouldn't have been performed.
		$this->assertSame( array(), $this->importer->imports );
	}

	/**
	 * Test that the can_import function is passed any settings.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_can_import_passed_settings() {

		$this->importer->do_import(
			array(
				'points' => array(
					'user_points' => '1',
					'_data' => array( 'testing' => 1 ),
				),
			)
			, new WordPoints_Importer_Tests_Feedback()
		);

		$this->assertCount( 1, $this->importer->can_imports );
		$this->assertSame(
			array( 'testing' => 1 )
			, $this->importer->can_imports[0]
		);
	}

	/**
	 * Test that it calls the importer function.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import() {

		$this->importer->do_import(
			array( 'points' => array( 'user_points' => '1' ) )
			, new WordPoints_Importer_Tests_Feedback()
		);

		$this->assertCount( 1, $this->importer->imports );
	}

	/**
	 * Test that the import function is passed any settings.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_Importer::do_import
	 */
	public function test_do_import_passed_settings() {

		$this->importer->do_import(
			array(
				'points' => array(
					'user_points' => '1',
					'_data' => array( 'testing' => 1 ),
				),
			)
			, new WordPoints_Importer_Tests_Feedback()
		);

		$this->assertCount( 1, $this->importer->imports );
		$this->assertSame(
			array( 'testing' => 1 )
			, $this->importer->imports[0]
		);
	}
}

// EOF
