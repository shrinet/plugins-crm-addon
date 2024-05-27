<?php
namespace MacleanCustomCode\BackgroundProcess;

include_once __DIR__ . "/wp-background-process.php";

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCBackgroundProcessEmail extends MacleanCCWPBackgroundProcess {
	
	//$this->save()->dispatch();
	//$this->push_to_queue( $task_item );
	/**
	 * @var string
	 */
	protected $action = 'maclean_background_process_email';

	public function __construct( $master ) {
		parent::__construct();
		$this->master = $master;
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $task_item ) {
		$class = $task_item[ "class" ];
		if ( class_exists( $class ) ) {
			$class_obj = new $class;
			$class_obj->set_master( $this->master );
			call_user_func( array( $class_obj, $task_item[ "method" ] ), $task_item );
		}	
		return false;	
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		// Show notice to user or perform some other arbitrary task...
	}

	public function add_to_queue( $class, $method, $value ) {
		$task_item = array( "value" => $value );
		$task_item[ "class" ] = $class;
		$task_item[ "method" ] = $method;
		$this->push_to_queue( $task_item );
	}

	public function send_queue() {
		$this->save()->dispatch();
	}
}