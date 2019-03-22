<?php
/**
 * Plugin Name: Debug - WP Redirect
 * Description: Adds debugging output to the HTTP header response to find out which file/function triggered a redirect.
 * Author:      Philipp Stracker
 * Author URI:  http://www.stracker.net/
 * Created:     15.09.2015
 * Version:     1.0.0
 *
 * Simply activate the plugin and it will add debugging information to all
 * WordPress redirects.
 *
 * Inspect the HTTP response in your browsers developer console to see the
 * debug information.
 * ----------------------------------------------------------------------------
 */

class WDevDebug_Redirect {

	/**
	 * Returns the singleton instance.
	 *
	 * @since  1.0.0
	 * @return WDevDebug_Redirect
	 */
	static public function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new WDevDebug_Redirect();
		}

		return $Inst;
	}

	/**
	 * Constructor.
	 * Hooks up this module.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		add_filter(
			'wp_redirect',
			array( $this, 'redirect_headers' ),
			9999
		);
	}

	/**
	 * Adds some redirect headers with debugging information to the response.
	 *
	 * @since  1.0.0
	 */
	public function redirect_headers( $location ) {
		if ( $location ) {
			$trace = $this->get_trace();

			if ( ! headers_sent() ) {
				foreach ( $trace as $ind => $line ) {
					header( "WPDev-Redirect-Trace-$ind: $line", false );
				}
			} else {
				echo "\n";
				foreach ( $trace as $ind => $line ) {
					echo "<!-- wdpu-redirect-trace-$ind: $line -->\n";
				}
			}
		}

		return $location;
	}

	/**
	 * Generates an array of stack-trace information. Each array item is a
	 * simple string that can be directly output.
	 *
	 * @since  1.0.0
	 * @return array Trace information
	 */
	public function get_trace() {
		$result = array();

		$trace = debug_backtrace();
		$trace_count = count( $trace );
		$_num = 0;
		$start_at = 0;

		// Skip the first 4 trace lines (filter call inside wp_redirect)
		if ( $trace_count > 4 ) { $start_at = 4; }

		for ( $i = $start_at; $i < $trace_count; $i += 1 ) {
			$trace_info = $trace[$i];
			$line_info = $trace_info;
			$j = $i;

			while ( empty( $line_info['line'] ) && $j < $trace_count ) {
				$line_info = $trace[$j];
				$j += 1;
			}

			$_file = empty( $line_info['file'] ) ? '' : $line_info['file'];
			$_line = empty( $line_info['line'] ) ? '' : $line_info['line'];
			$_args = empty( $trace_info['args'] ) ? array() : $trace_info['args'];
			$_class = empty( $trace_info['class'] ) ? '' : $trace_info['class'];
			$_type = empty( $trace_info['type'] ) ? '' : $trace_info['type'];
			$_function = empty( $trace_info['function'] ) ? '' : $trace_info['function'];

			$_num += 1;
			$_arg_string = '';
			$_args_arr = array();

			if ( $i > 0 && is_array( $_args ) && count( $_args ) ) {
				foreach ( $_args as $arg ) {
					if ( is_scalar( $arg ) ) {
						if ( is_bool( $arg ) ) {
							$_args_arr[] = ( $arg ? 'true' : 'false' );
						} elseif ( is_string( $arg ) ) {
							$_args_arr[] = '"' . $arg . '"';
						} else {
							$_args_arr[] = $arg;
						}
					} elseif ( is_array( $arg ) ) {
						$_args_arr[] = '[Array]';
					} elseif ( is_object( $arg ) ) {
						$_args_arr[] = '[' . get_class( $arg ) . ']';
					} elseif ( is_null( $arg ) ) {
						$_args_arr[] = 'NULL';
					} else {
						$_args_arr[] = '[?]';
					}
				}

				$_arg_string = implode( ',', $_args_arr );
			}

			if ( strlen( $_file ) > 80 ) {
				$_file = '...' . substr( $_file, -77 );
			} else {
				$_file = str_pad( $_file, 80, ' ', STR_PAD_RIGHT );
			}

			$result_item = sprintf(
				'%s:%s %s(%s)',
				$_file,
				str_pad( $_line, 5, ' ', STR_PAD_LEFT ),
				$_class . $_type . $_function,
				$_arg_string
			);

			$_num_str = str_pad( $_num, 2, '0', STR_PAD_LEFT );
			$result[$_num_str] = $result_item;
		}

		return $result;
	}

}
WDevDebug_Redirect::instance();