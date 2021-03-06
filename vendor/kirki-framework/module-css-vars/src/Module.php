<?php
/**
 * Handles the CSS-variables of fields.
 *
 * @package     Kirki
 * @category    Modules
 * @author      Ari Stathopoulos (@aristath)
 * @copyright   Copyright (c) 2019, Ari Stathopoulos (@aristath)
 * @license    https://opensource.org/licenses/MIT
 * @since       3.0.28
 */

namespace Kirki\Modules\CSS_Vars;

use Kirki\Core\Values;
use Kirki\Core\Kirki;

/**
 * The Module object.
 *
 * @since 3.0.28
 */
class Module {

	/**
	 * The object instance.
	 *
	 * @static
	 * @access private
	 * @since 3.0.28
	 * @var object
	 */
	private static $instance;

	/**
	 * CSS-variables array [var=>val].
	 *
	 * @access private
	 * @since 3.0.35
	 * @var array
	 */
	private $vars = [];

	/**
	 * Constructor
	 *
	 * @access protected
	 * @since 3.0.28
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'populate_vars' ] );
		add_action( 'wp_head', [ $this, 'the_style' ], 999 );
		add_action( 'admin_head', [ $this, 'the_style' ], 999 );
		add_action( 'customize_preview_init', [ $this, 'postmessage' ] );
	}

	/**
	 * Gets an instance of this object.
	 * Prevents duplicate instances which avoid artefacts and improves performance.
	 *
	 * @static
	 * @access public
	 * @since 3.0.28
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Populates the $vars property of this object.
	 *
	 * @access public
	 * @since 3.0.35
	 * @return void
	 */
	public function populate_vars() {

		// Get an array of all fields.
		$fields = Kirki::$fields;
		foreach ( $fields as $id => $args ) {
			if ( ! isset( $args['css_vars'] ) || empty( $args['css_vars'] ) ) {
				continue;
			}
			$val = Values::get_value( $args['kirki_config'], $id );
			foreach ( $args['css_vars'] as $css_var ) {
				if ( isset( $css_var[2] ) && is_array( $val ) && isset( $val[ $css_var[2] ] ) ) {
					$this->vars[ $css_var[0] ] = str_replace( '$', $val[ $css_var[2] ], $css_var[1] );
				} else {
					$this->vars[ $css_var[0] ] = str_replace( '$', $val, $css_var[1] );
				}
			}
		}
	}

	/**
	 * Add styles in <head>.
	 *
	 * @access public
	 * @since 3.0.28
	 * @return void
	 */
	public function the_style() {
		if ( empty( $this->vars ) ) {
			return;
		}

		echo '<style id="kirki-css-vars">';
		echo ':root{';
		foreach ( $this->vars as $var => $val ) {
			echo esc_html( $var ) . ':' . esc_html( $val ) . ';';
		}
		echo '}';
		echo '</style>';
	}

	/**
	 * Get an array of all the variables.
	 *
	 * @access public
	 * @since 3.0.35
	 * @return array
	 */
	public function get_vars() {
		return $this->vars;
	}

	/**
	 * Enqueues the script that handles postMessage
	 * and adds variables to it using the wp_localize_script function.
	 * The rest is handled via JS.
	 *
	 * @access public
	 * @since 3.0.28
	 * @return void
	 */
	public function postmessage() {
		wp_enqueue_script( 'kirki_auto_css_vars', trailingslashit( Kirki::$url ) . 'vendor/kirki-framework/module-css-vars/src/script.js', [ 'jquery', 'customize-preview' ], KIRKI_VERSION, true );
		$fields = Kirki::$fields;
		$data   = [];
		foreach ( $fields as $field ) {
			if ( isset( $field['transport'] ) && 'postMessage' === $field['transport'] && isset( $field['css_vars'] ) && ! empty( $field['css_vars'] ) ) {
				$data[] = $field;
			}
		}
		wp_localize_script( 'kirki_auto_css_vars', 'kirkiCssVarFields', $data );
	}
}
