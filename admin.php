<?php
function sportLeagueAdminPage(){
	$page = isset($_GET['page']) ? $_GET['page'] : SPORT_LEAGUE_VAR_NAME;

	switch($page){
		case SPORT_LEAGUE_VAR_NAME:
		case SPORT_LEAGUE_VAR_NAME . '_fixtures':
			// fixtures page
			require(SPORT_LEAGUE_DIR . 'admin/fixtures/fixtures.php');
		break;
		case SPORT_LEAGUE_VAR_NAME . '_seasons':
			// fixtures page
			require(SPORT_LEAGUE_DIR . 'admin/seasons/seasons.php');
		break;
		case SPORT_LEAGUE_VAR_NAME . '_teams':
			// teams page
			require(SPORT_LEAGUE_DIR . 'admin/teams/teams.php');
		break;
		case SPORT_LEAGUE_VAR_NAME . '_settings':
			// teams page
			require(SPORT_LEAGUE_DIR . 'admin/settings.php');
		break;
		default:
			// un-recognised page - throw 404
			sportLeagueHTTPStatus(404, true);
		break;
	}
}

function sportLeagueAdminMenu(){
	add_menu_page(
		SPORT_LEAGUE_NAME,
		SPORT_LEAGUE_NAME,
		'publish_pages',
		SPORT_LEAGUE_VAR_NAME,
		'sportLeagueAdminPage',
		SPORT_LEAGUE_PLUGIN_URL . 'images/icn-menu.png'
	);

	add_submenu_page(
		SPORT_LEAGUE_VAR_NAME,
		'Fixtures',
		'Fixtures',
		'publish_pages',
		SPORT_LEAGUE_VAR_NAME,
		'sportLeagueAdminPage'
	);

	add_submenu_page(
		SPORT_LEAGUE_VAR_NAME,
		'Seasons',
		'Seasons',
		'publish_pages',
		SPORT_LEAGUE_VAR_NAME . '_seasons',
		'sportLeagueAdminPage'
	);

	add_submenu_page(
		SPORT_LEAGUE_VAR_NAME,
		'Teams',
		'Teams',
		'publish_pages',
		SPORT_LEAGUE_VAR_NAME . '_teams',
		'sportLeagueAdminPage'
	);

	add_submenu_page(
		SPORT_LEAGUE_VAR_NAME,
		'Settings',
		'Settings',
		'manage_options',
		SPORT_LEAGUE_VAR_NAME . '_settings',
		'sportLeagueAdminPage'
	);
}

add_action('admin_menu', 'sportLeagueAdminMenu');


function sportLeagueEnqueueAdminScripts(){
	// only enqueue scripts if we aer on a sport league page
	if(isset($_GET['page']) && (0 === strpos($_GET['page'], SPORT_LEAGUE_VAR_NAME))){
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_register_script('my-upload', SPORT_LEAGUE_PLUGIN_URL.'/js/admin.js', array('jquery', 'media-upload', 'thickbox', 'jquery-ui-core', 'jquery-ui-datepicker'), SPORT_LEAGUE_VERSION . time());
		wp_enqueue_script('my-upload');

		wp_enqueue_style('thickbox');

		wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/cupertino/jquery-ui.css');
		wp_enqueue_style('jquery-ui');

		wp_register_style(SPORT_LEAGUE_VAR_NAME . '-admin', SPORT_LEAGUE_PLUGIN_URL.'/css/admin.css');
		wp_enqueue_style(SPORT_LEAGUE_VAR_NAME . '-admin');
	}
}

add_action('admin_print_scripts', 'sportLeagueEnqueueAdminScripts');
?>