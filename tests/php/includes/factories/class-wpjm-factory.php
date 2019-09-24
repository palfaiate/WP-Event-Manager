<?php
/**
 * Class WPJM Factory
 *
 * This class takes care of creating testing data for the WPJM Unit tests
 *
 * @since 1.26.0
 */
class WPJM_Factory extends WP_UnitTest_Factory {
	public $event_listing;

	/**
	 * Constructor
	 */
	public function __construct() {
		// construct the parent.
		parent::__construct();
		require_once dirname( __FILE__ ) . '/class-wp-unittest-factory-for-event-listing.php';
		$this->event_listing = new WP_UnitTest_Factory_For_event_Listing( $this );
	}
}
