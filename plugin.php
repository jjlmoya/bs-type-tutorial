<?php
/**
 * Plugin Name: Tutorial Model [Post Type]
 * Plugin URI: https://www.bonseo.es/
 * Description: Modelo de Cursos
 * Author: jjlmoya
 * Author URI: https://www.bonseo.es/
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * @package BS
 */

if (!defined('ABSPATH')) {
	exit;
}
if (!in_array('bs-core/plugin.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	add_action('admin_notices', function () {
		global $pagenow;
		if ($pagenow == "plugins.php") {
			?>
			<div id="updated" class="error notice is-dismissible">
				<p> Puede que algunos plugins vean afectados su comportamiento y estilo debido a que no se ha instalado
					la dependencia con el Plugin "BS-CORE" disponible gratuitamente en https://bonseo.es/plugins</p>
			</div>
			<?php
		}
	});
	return;
}

/** MODEL CONFIGURATION **/
require_once plugin_dir_path(__FILE__) . '/Tutorial.php';
function bs_tutorial_get_post_type()
{
	return Tutorial::getInstance('Guía', 'Guías', "guia",
		array(
			"youtube" => array(
				"name" => "Enlace a Youtube",
				"value" => "youtube",
				"input" => "text"
			)
		)
	);
}

/** END MODEL CONFIGURATION */

/** REGISTER CORE FUNCTIONS **/
function bs_tutorial_register_post_type()
{
	$model = bs_tutorial_get_post_type();
	$labels = array(
		"name" => __($model->plural, "custom-post-type-ui"),
		"singular_name" => __($model->singular, "custom-post-type-ui"),
	);

	$args = array(
		"label" => __($model->plural, "custom-post-type-ui"),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"delete_with_user" => false,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"exclude_from_search" => false,
		'menu_icon' => $model->icon,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array("slug" => $model->path, "with_front" => true),
		"query_var" => true,
		"supports" =>
			array("title",
				"editor",
				"thumbnail",
				"custom-fields",
				"excerpt"),
	);

	register_post_type($model->db, $args);
}

function bs_tutorial_create_custom_params()
{
	$model = bs_tutorial_get_post_type();
	foreach ($model->customFields as $customField) {
		add_action('add_meta_boxes', $model->nameSpace . '_' . $customField["value"] . '_register');
	}
}

function bs_tutorial_register($customType)
{
	$model = bs_tutorial_get_post_type();
	$customField = $model->customFields;
	$customField = $customField[$customType];
	add_meta_box(
		$model->db . '_' . $customField['value'],
		$customField['name'],
		$model->nameSpace . '_' . $customField['value'] . '_callback',
		$model->db,
		'side',
		'high'
	);

}

function bs_tutorial_callback($fieldType)
{
	$model = bs_tutorial_get_post_type();
	$customField = $model->customFields;
	$customField = $customField[$fieldType];
	$dbEntry = $model->db . '_' . $customField['value'];
	global $post;
	wp_nonce_field(basename(__FILE__), $dbEntry);
	$value = get_post_meta($post->ID, $dbEntry, true);
	echo '<input type="' . $customField['input'] . '" name="' . $dbEntry . '" value="' . esc_textarea($value) . '" class="widefat">';
}

function bs_tutorial_on_save($post_id)
{

	$model = bs_tutorial_get_post_type();

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (isset($_POST['post_type']) && $_POST['post_type'] == $model->db) {
		if (!current_user_can('edit_page', $post_id)) {
			return;
		}
	} else {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}
	foreach ($model->customFields as $customField) {
		$customFieldEntry = $model->db . '_' . $customField['value'];
		if (!isset($_POST[$customFieldEntry])) {
			return;
		}
		$myValue = sanitize_text_field($_POST[$customFieldEntry]);
		update_post_meta($post_id, $customFieldEntry, $myValue);
	}
}

add_action('init', 'bs_tutorial_register_post_type');
add_action('save_post', 'bs_tutorial_on_save');
bs_tutorial_create_custom_params();

/** END CORE FUNCTIONS **/


/** ADD THESE 2 METHODS FOR NEW PARAMETER **/
function bs_tutorial_youtube_register()
{
	bs_tutorial_register('youtube');
}

function bs_tutorial_youtube_callback()
{
	bs_tutorial_callback('youtube');
}
/** END GENERIC ADD */
