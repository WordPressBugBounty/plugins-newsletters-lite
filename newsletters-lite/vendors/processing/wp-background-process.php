<?php
/**
 * WP Background Process
 *
 * @package WP-Background-Processing
 */

if ( ! class_exists( 'WPML_WP_Background_Process' ) ) {

	/**
	 * Abstract WPML_WP_Background_Process class.
	 *
	 * @abstract
	 * @extends WPML_WPML_WP_Async_Request
	 */
	abstract class WPML_WP_Background_Process extends WPML_WPML_WP_Async_Request {

		/**
		 * Action
		 *
		 * (default value: 'background_process')
		 *
		 * @var string
		 * @access protected
		 */
		protected $action = 'background_process';

		/**
		 * Start time of current process.
		 *
		 * (default value: 0)
		 *
		 * @var int
		 * @access protected
		 */
		protected $start_time = 0;

		/**
		 * Cron_hook_identifier
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $cron_hook_identifier;

		/**
		 * Cron_interval_identifier
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $cron_interval_identifier;

		/**
		 * Initiate new background process
		 */
		public function __construct() {
			parent::__construct();

            $this->cron_hook_identifier     = $this->identifier . '_cron';
            $this->cron_interval_identifier = $this->identifier . '_cron_interval';

            global ${'action_added_' . $this->cron_hook_identifier};
            if (empty(${'action_added_' . $this->cron_hook_identifier})) {
                add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
                ${'action_added_' . $this->cron_hook_identifier} = true;
            }

            add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ));
		}

		/**
		 * Executes a closure in try/catch, re-queues the job and unlocks the
		 * worker if anything – Exception **or** Error – is thrown.
		 *
		 * @param  callable $callback The original task body.
		 * @param  array    $item     The current queue item.
		 * @param  object   $worker   $this from inside task().
		 * @return mixed             The callback’s return value or false on error.
		 */
		function wpml_safe_task( callable $callback, $item, $worker ) {
			try {
				return $callback();
			} catch ( Exception $e ) {
			} catch ( Error $e ) {     // PHP-7 "Error" objects
				// Record the reason for later inspection.
				$item['error'] = $e->getMessage();
	
				// Put the job at the tail of the queue.
				$worker->push_to_queue( $item );
	
				// **Very** important – otherwise the transient lock lives for
				// queue_lock_time seconds and the whole queue looks “stuck”.
				$worker->unlock_process();
	
				// Let the batch continue with the next job.
				return false;
			}
		}
		
		/**
		 * Dispatch
		 *
		 * @access public
		 * @return void
		 */
		public function dispatch() {
			// Schedule the cron healthcheck.
			$this->schedule_event();

			// Perform remote post.
			return parent::dispatch();
		}

		/**
		 * Push to queue
		 *
		 * @param mixed $data Data.
		 *
		 * @return $this
		 */
		public function push_to_queue( $data ) {
			$this -> data[] = $data;

			return $this;
		}

		/**
		 * Save queue
		 *
		 * @return $this
		 */
		public function save() {
			$key = $this -> generate_key();

			if ( ! empty( $this->data ) ) {				
				update_option( $key, $this->data, 'no');
			}

			return $this;
		}

		/**
		 * Update queue
		 *
		 * @param string $key Key.
		 * @param array  $data Data.
		 *
		 * @return $this
		 */
		public function update( $key, $data ) {
			if ( ! empty( $data ) ) {
				update_option( $key, $data , 'no');
			}

			return $this;
		}

		/**
		 * Delete queue
		 *
		 * @param string $key Key.
		 *
		 * @return $this
		 */
		public function delete( $key ) {
			delete_option( $key );

			return $this;
		}

		/**
		 * Generate key
		 *
		 * Generates a unique key based on microtime. Queue items are
		 * given a unique key so that they can be merged upon save.
		 *
		 * @param int $length Length.
		 *
		 * @return string
		 */
		protected function generate_key( $length = 64 ) {
			$unique  = md5(microtime() . rand());
			$prepend = $this->identifier . '_batch_';

			return substr( $prepend . $unique, 0, $length );
		}

		/**
		 * Maybe process queue
		 *
		 * Checks whether data exists within the queue and that
		 * the process is not already running.
		 */
		public function maybe_handle() {
			// Don't lock up other requests while processing
			session_write_close();

			if ( $this->is_process_running() ) {
				// Background process already running.
				wp_die();
			}

			if ( $this->is_queue_empty() ) {
				// No data to process.
				wp_die();
			}

			check_ajax_referer( $this->identifier, 'nonce' );

			$this->handle();

			wp_die();
		}

		/**
		 * Is queue empty
		 *
		 * @return bool
		 */
		protected function is_queue_empty() {
			global $wpdb;

			$table  = $wpdb->options;
			$column = 'option_name';

			$key = $this->identifier . '_batch_%';

			$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

			return ( $count > 0 ) ? false : true;
		}

		/**
		 * Is process running
		 *
		 * Check whether the current process is already running
		 * in a background process.
		 */
		protected function is_process_running() {
			if (get_transient( $this->identifier . '_process_lock' ) ) {
				// Process already running.
				return true;
			}

			return false;
		}

		/**
		 * Lock process
		 *
		 * Lock the process so that multiple instances can't run simultaneously.
		 * Override if applicable, but the duration should be greater than that
		 * defined in the time_exceeded() method.
		 */
		protected function lock_process() {
			$this->start_time = time(); // Set start time of current process.

			$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
			$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

			set_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
		}

		/**
		 * Unlock process
		 *
		 * Unlock the process so that other instances can spawn.
		 *
		 * @return $this
		 */
		protected function unlock_process() {
			delete_transient( $this->identifier . '_process_lock' );

			return $this;
		}

		/**
		 * Get batch
		 *
		 * @return stdClass Return the first batch from the queue
		 */
		protected function get_batch() {
			global $wpdb;

			$table        = $wpdb->options;
			$column       = 'option_name';
			$key_column   = 'option_id';
			$value_column = 'option_value';

			$key = $this->identifier . '_batch_%';

			$query = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			ORDER BY {$key_column} ASC
			LIMIT 1
		", $key ) );

			$batch       = new stdClass();
			$batch->key  = $query->$column;
			$batch->data = maybe_unserialize( $query->$value_column );

			return $batch;
		}

		/**
		 * Handle
		 *
		 * Pass each queue item to the task handler, while remaining
		 * within server memory and time limit constraints.
		 */
		protected function handle() {
			$this->lock_process();

			do {
				$batch = $this->get_batch();

				foreach ( $batch->data as $key => $value ) {
					$task = $this->task( $value );

					if ( false !== $task ) {
						$batch->data[ $key ] = $task;
					} else {
						unset( $batch->data[ $key ] );
					}

					if ( $this->time_exceeded() || $this->memory_exceeded() ) {
						// Batch limits reached.
						break;
					}
				}

				// Update or delete current batch.
				if ( ! empty( $batch->data ) ) {
					$this->update( $batch->key, $batch->data );
				} else {
					$this->delete( $batch->key );
				}
			} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

			$this->unlock_process();

			// Start next batch or complete process.
			if ( ! $this->is_queue_empty() ) {
				$this->dispatch();
			} else {
				$this->complete();
			}

			wp_die();
		}

		/**
		 * Memory exceeded
		 *
		 * Ensures the batch process never exceeds 90%
		 * of the maximum WordPress memory.
		 *
		 * @return bool
		 */
		protected function memory_exceeded() {
			$memory_limit   = $this -> get_memory_limit() * 0.9; // 90% of max memory
			$current_memory = memory_get_usage( true );
			$return         = false;

			if ( $current_memory >= $memory_limit ) {
				$return = true;
			}

			return apply_filters( $this->identifier . '_memory_exceeded', $return );
		}

		/**
		 * Get memory limit
		 *
		 * @return int
		 */
		protected function get_memory_limit() {
			if ( function_exists( 'ini_get' ) ) {
				$memory_limit = ini_get( 'memory_limit' );
			} else {
				// Sensible default.
				$memory_limit = '1024M';
			}

			if (empty($memory_limit) || -1 == (int) $memory_limit ) {
				// Unlimited, set to 32GB.
				$memory_limit = '32000M';
			}

			return intval( $memory_limit ) * 1024 * 1024;
		}

		/**
		 * Time exceeded.
		 *
		 * Ensures the batch never exceeds a sensible time limit.
		 * A timeout limit of 30s is common on shared hosting.
		 *
		 * @return bool
		 */
		protected function time_exceeded() {
			$finish = $this -> start_time + apply_filters( $this->identifier . '_default_time_limit', MINUTE_IN_SECONDS); // 20 seconds
			$return = false;

			if (time() >= $finish ) {
				$return = true;
			}

			return apply_filters( $this->identifier . '_time_exceeded', $return );
		}

		/**
		 * Complete.
		 *
		 * Override if applicable, but ensure that the below actions are
		 * performed, or, call parent::complete().
		 */
		protected function complete() {
			// Unschedule the cron healthcheck.
			$this->clear_scheduled_event();
		}

		/**
		 * Schedule cron healthcheck
		 *
		 * @access public
		 * @param mixed $schedules Schedules.
		 * @return mixed
		 */
		public function schedule_cron_healthcheck( $schedules ) {
			$interval = apply_filters( $this->identifier . '_cron_interval', 2);

			if ( property_exists( $this, 'cron_interval' ) ) {
				$interval = apply_filters( $this->identifier . '_cron_interval', $this->cron_interval_identifier );
			}

			// Adds every 2 minutes to the existing schedules.
			$schedules[ $this->identifier . '_cron_interval' ] = array(
				'interval' => MINUTE_IN_SECONDS * $interval,
				'display'  => sprintf( __( 'Every %d Minutes' ), $interval ),
			);

			return $schedules;
		}

		/**
		 * Handle cron healthcheck
		 *
		 * Restart the background process if not already running
		 * and data exists in the queue.
		 */
		public function handle_cron_healthcheck() {
			if ( $this->is_process_running() ) {
				// Background process already running.
				exit;
			}

			if ( $this->is_queue_empty() ) {
				// No data to process.
				$this->clear_scheduled_event();
				exit;
			}

			$this->handle();

		}

		/**
		 * Schedule event
		 */
		protected function schedule_event() {			
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event(time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		/**
		 * Clear scheduled event
		 */
		protected function clear_scheduled_event() {
			$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
			}
		}

		/**
		 * Cancel Process
		 *
		 * Stop processing queue items, clear cronjob and delete batch.
		 *
		 */
		public function cancel_process() {
			if ( ! $this->is_queue_empty() ) {
				$batch = $this->get_batch();

				$this->delete( $batch->key );

				wp_clear_scheduled_hook( $this->cron_hook_identifier );
			}

		}

		/**
		 * Task
		 *
		 * Override this method to perform any actions required on each
		 * queue item. Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @param mixed $item Queue item to iterate over.
		 *
		 * @return mixed
		 */
		abstract protected function task( $item );
		
		public function debug($var = array()) {
		    echo '<pre>' . print_r($var, true) . '</pre>';
	    }

        public function translate_scheule_interval ($schedule2 = null)
        {
            if(empty($schedule2))
            {
                return  array(
                    "interval" => 0,
                    "display" => "Invalid interval. Please set  through Newsletters configurations."
                );
            }

            $schedules = array(
                "1minutes" => array(
                    "interval" => 60,
                    "display" => "Every Minute"
                ),
                "2minutes" => array(
                    "interval" => 120,
                    "display" => "Every 2 Minutes"
                ),
                "5minutes" => array(
                    "interval" => 300,
                    "display" => "Every 5 Minutes"
                ),
                "10minutes" => array(
                    "interval" => 600,
                    "display" => "Every 10 Minutes"
                ),
                "20minutes" => array(
                    "interval" => 1200,
                    "display" => "Every 20 Minutes"
                ),
                "30minutes" => array(
                    "interval" => 1800,
                    "display" => "Every 30 Minutes"
                ),
                "40minutes" => array(
                    "interval" => 2400,
                    "display" => "Every 40 Minutes"
                ),
                "50minutes" => array(
                    "interval" => 3000,
                    "display" => "Every 50 minutes"
                ),
                "hourly" => array(
                    "interval" => 3600,
                    "display" => "Once Hourly"
                ),
                "twicedaily" => array(
                    "interval" => 43200,
                    "display" => "Twice Daily"
                ),
                "daily" => array(
                    "interval" => 86400,
                    "display" => "Once Daily"
                ),
                "weekly" => array(
                    "interval" => 604800,
                    "display" => "Once Weekly"
                ),
                "monthly" => array(
                    "interval" => 2664000,
                    "display" => "Once Monthly"
                )

            );

            return $schedules[$schedule2];
        }

	}
}
