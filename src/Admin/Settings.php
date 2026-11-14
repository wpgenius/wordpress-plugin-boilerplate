<?php
/**
 * Settings page built from one array.
 *
 * To add a setting, add one entry to fields(). It is then registered,
 * sanitised, shown on the page and readable with Api::get_setting( 'key' ).
 * All values are stored together in one option (Api::OPTION).
 *
 * Field keys: type, label, default, and optionally description, options
 * (select/radio), min/max/step (number), placeholder, vars (placeholders
 * shown under email fields).
 *
 * Types: text, textarea, editor, number, checkbox, select, radio, email,
 * url, color, time, password.
 *
 * @package WPGenius\MyPlugin
 */

namespace WPGenius\MyPlugin\Admin;

use WPGenius\MyPlugin\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page.
 */
class Settings extends Api {

	/**
	 * Page slug (Settings → <plugin name>) and settings group.
	 */
	const PAGE = 'myplugin_settings';

	/**
	 * Register the page and its settings.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * The settings, grouped in sections. Edit this to add your own.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields() {
		$sections = array(
			'general'  => array(
				'title'       => __( 'General', 'wordpress-plugin-boilerplate' ),
				'description' => __( 'How items are listed by the shortcode and the block.', 'wordpress-plugin-boilerplate' ),
				'fields'      => array(
					'items_per_page' => array(
						'type'    => 'number',
						'label'   => __( 'Items per page', 'wordpress-plugin-boilerplate' ),
						'default' => 6,
						'min'     => 1,
						'max'     => 50,
					),
					'layout'         => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'wordpress-plugin-boilerplate' ),
						'default' => 'grid',
						'options' => array(
							'grid' => __( 'Grid', 'wordpress-plugin-boilerplate' ),
							'list' => __( 'List', 'wordpress-plugin-boilerplate' ),
						),
					),
					'accent_color'   => array(
						'type'    => 'color',
						'label'   => __( 'Accent colour', 'wordpress-plugin-boilerplate' ),
						'default' => '#2271b1',
					),
					'show_load_more' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Load more button', 'wordpress-plugin-boilerplate' ),
						'description' => __( 'Show a button that loads the next page without reloading (REST API).', 'wordpress-plugin-boilerplate' ),
						'default'     => 1,
					),
				),
			),
			'report'   => array(
				'title'       => __( 'Daily report email', 'wordpress-plugin-boilerplate' ),
				'description' => __( 'A scheduled (cron) email with the number of items published in the last day.', 'wordpress-plugin-boilerplate' ),
				'fields'      => array(
					'report_enabled' => array(
						'type'    => 'checkbox',
						'label'   => __( 'Send the report', 'wordpress-plugin-boilerplate' ),
						'default' => 0,
					),
					'report_time'    => array(
						'type'        => 'time',
						'label'       => __( 'Send at', 'wordpress-plugin-boilerplate' ),
						'description' => __( 'Site time, 24-hour.', 'wordpress-plugin-boilerplate' ),
						'default'     => '08:00',
					),
					'report_to'      => array(
						'type'        => 'email',
						'label'       => __( 'Send to', 'wordpress-plugin-boilerplate' ),
						'description' => __( 'Leave empty to use the site admin email.', 'wordpress-plugin-boilerplate' ),
						'default'     => '',
					),
					'report_subject' => array(
						'type'    => 'text',
						'label'   => __( 'Subject', 'wordpress-plugin-boilerplate' ),
						'default' => __( '{site_name}: {count} new items', 'wordpress-plugin-boilerplate' ),
						'vars'    => array( '{site_name}', '{count}', '{date}' ),
					),
					'report_body'    => array(
						'type'    => 'editor',
						'label'   => __( 'Message', 'wordpress-plugin-boilerplate' ),
						'default' => __( "Hello,\n\n{count} items were published on {site_name} since {date}.", 'wordpress-plugin-boilerplate' ),
						'vars'    => array( '{site_name}', '{site_url}', '{count}', '{date}' ),
					),
				),
			),
			'advanced' => array(
				'title'  => __( 'Advanced', 'wordpress-plugin-boilerplate' ),
				'fields' => array(
					'delete_data' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Delete data on uninstall', 'wordpress-plugin-boilerplate' ),
						'description' => __( 'When the plugin is deleted, also delete its settings, items and database table.', 'wordpress-plugin-boilerplate' ),
						'default'     => 0,
					),
				),
			),
		);

		/**
		 * Filters the settings sections and fields, e.g. to add a field from an add-on.
		 *
		 * @param array $sections Sections with their fields.
		 */
		return apply_filters( 'myplugin_settings_fields', $sections );
	}

	/**
	 * Every field, keyed by field key, without the sections.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function flat_fields() {
		$fields = array();
		foreach ( self::fields() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				$fields[ $key ] = $field;
			}
		}
		return $fields;
	}

	/**
	 * Default value of every field.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array_map(
			static function ( $field ) {
				return isset( $field['default'] ) ? $field['default'] : '';
			},
			self::flat_fields()
		);
	}

	/**
	 * Add Settings → <plugin name>.
	 *
	 * @return void
	 */
	public function add_page() {
		add_options_page(
			__( 'Plugin boilerplate for WordPress', 'wordpress-plugin-boilerplate' ),
			__( 'Plugin boilerplate for WordPress', 'wordpress-plugin-boilerplate' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option, the sections and the fields from fields().
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		foreach ( self::fields() as $section_id => $section ) {
			add_settings_section(
				'myplugin_' . $section_id,
				$section['title'],
				static function () use ( $section ) {
					if ( ! empty( $section['description'] ) ) {
						echo '<p>' . esc_html( $section['description'] ) . '</p>';
					}
				},
				self::PAGE
			);

			foreach ( $section['fields'] as $key => $field ) {
				$field['key'] = $key;
				add_settings_field(
					'myplugin_' . $key,
					esc_html( $field['label'] ),
					array( $this, 'render_field' ),
					self::PAGE,
					'myplugin_' . $section_id,
					array(
						'field'     => $field,
						'label_for' => in_array( $field['type'], array( 'radio', 'editor' ), true ) ? '' : 'myplugin_' . $key,
					)
				);
			}
		}
	}

	/**
	 * Sanitise the whole settings array before it is saved.
	 * A field missing from the input keeps its saved value.
	 *
	 * @param mixed $input Submitted values.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$current = self::get_settings();
		$clean   = array();

		foreach ( self::flat_fields() as $key => $field ) {
			$clean[ $key ] = array_key_exists( $key, $input )
				? self::sanitize_value( $field, $input[ $key ] ) // options.php has already unslashed the input.
				: $current[ $key ];
		}

		return $clean;
	}

	/**
	 * Sanitise one value by its field type. Invalid values fall back to the default.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return mixed
	 */
	public static function sanitize_value( array $field, $value ) {
		$default = isset( $field['default'] ) ? $field['default'] : '';
		$value   = is_scalar( $value ) ? (string) $value : '';

		switch ( $field['type'] ) {
			case 'checkbox':
				return '1' === $value ? 1 : 0;

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return $default;
				}
				$number = ( isset( $field['step'] ) && floor( $field['step'] ) !== (float) $field['step'] ) ? (float) $value : (int) $value;
				if ( isset( $field['min'] ) ) {
					$number = max( $field['min'], $number );
				}
				if ( isset( $field['max'] ) ) {
					$number = min( $field['max'], $number );
				}
				return $number;

			case 'select':
			case 'radio':
				return isset( $field['options'][ $value ] ) ? $value : $default;

			case 'email':
				return '' === $value ? '' : sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'color':
				$color = sanitize_hex_color( $value );
				return $color ? $color : $default;

			case 'time':
				return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : $default;

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'editor':
				return wp_kses_post( $value );

			default: // text, password.
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Output the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap myplugin-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Test the report email', 'wordpress-plugin-boilerplate' ); ?></h2>
			<p>
				<button type="button" class="button myplugin-test-report" data-nonce="<?php echo esc_attr( wp_create_nonce( \WPGenius\MyPlugin\Ajax::NONCE ) ); ?>">
					<?php esc_html_e( 'Send a test report to me', 'wordpress-plugin-boilerplate' ); ?>
				</button>
				<span class="myplugin-test-report__result" role="status"></span>
			</p>
			<p class="description"><?php esc_html_e( 'Uses the saved settings: save your changes first.', 'wordpress-plugin-boilerplate' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output one field. Called by add_settings_field() with array( 'field' => ... ).
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_field( $args ) {
		$field = $args['field'];
		$key   = $field['key'];
		$id    = 'myplugin_' . $key;
		$name  = self::OPTION . '[' . $key . ']';
		$value = self::get_setting( $key );
		$ph    = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		switch ( $field['type'] ) {
			case 'checkbox':
				printf( '<input type="hidden" name="%s" value="0">', esc_attr( $name ) );
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( 1, (int) $value, false ),
					isset( $field['description'] ) ? esc_html( $field['description'] ) : ''
				);
				unset( $field['description'] ); // Already shown next to the box.
				break;

			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'radio':
				echo '<fieldset>';
				foreach ( $field['options'] as $option => $label ) {
					printf(
						'<label><input type="radio" name="%s" value="%s" %s> %s</label><br>',
						esc_attr( $name ),
						esc_attr( $option ),
						checked( $value, $option, false ),
						esc_html( $label )
					);
				}
				echo '</fieldset>';
				break;

			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="5" class="large-text" placeholder="%s">%s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $ph ),
					esc_textarea( (string) $value )
				);
				break;

			case 'editor':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 8,
						'media_buttons' => false,
					)
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" class="small-text" %s %s %s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					isset( $field['min'] ) ? 'min="' . esc_attr( $field['min'] ) . '"' : '',
					isset( $field['max'] ) ? 'max="' . esc_attr( $field['max'] ) . '"' : '',
					isset( $field['step'] ) ? 'step="' . esc_attr( $field['step'] ) . '"' : ''
				);
				break;

			case 'color':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="myplugin-color-field" data-default-color="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( (string) $field['default'] )
				);
				break;

			default: // text, email, url, time, password.
				printf(
					'<input type="%s" id="%s" name="%s" value="%s" class="%s" placeholder="%s">',
					esc_attr( $field['type'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					'time' === $field['type'] ? '' : 'regular-text',
					esc_attr( $ph )
				);
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}

		if ( ! empty( $field['vars'] ) ) {
			echo '<p class="description">' . esc_html__( 'You can use:', 'wordpress-plugin-boilerplate' ) . ' <code>' . implode( '</code> <code>', array_map( 'esc_html', $field['vars'] ) ) . '</code></p>';
		}
	}
}
