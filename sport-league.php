<?php
if(!function_exists('add_action')){ exit; }

/**
 * Author: Lee Langley
 * Date Created: 28/08/2012 11:13
 */
/*
Plugin Name: Sports League
Plugin URI:
Description: Enables built-in functionality for displaying sport fixtures and results (manually entered via CMS)
Author: Lee Langley
Version: 0.1
Author URI:
*/
global $wpdb;
define('SPORT_LEAGUE_VERSION', '0.2');
define('SPORT_LEAGUE_NAME', 'Sport League');
define('SPORT_LEAGUE_DIR', dirname(__FILE__) . '/');
define('SPORT_LEAGUE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPORT_LEAGUE_VAR_NAME', 'sportleague');
define('SPORT_LEAGUE_DB_PREFIX', $wpdb->prefix . SPORT_LEAGUE_VAR_NAME . '_');
define('SPORT_LEAGUE_CLASS_APPEND', 'sportLeague_');
define('SPORT_LEAGUE_QUERY_NAME', SPORT_LEAGUE_VAR_NAME);

define('SPORT_LEAGUE_REPORT_URL', rtrim(get_bloginfo('url'), '/') . '/' . SPORT_LEAGUE_QUERY_NAME . '/report/fixture-%s');


$uploadDir = wp_upload_dir();
define('SPORT_LEAGUE_UPLOAD_PATH', $uploadDir['path'] . '/sport-league/');
define('SPORT_LEAGUE_UPLOAD_URL', $uploadDir['url'] . '/sport-league/');


/**
 * Runs the initial installation functionality
 */
function sportLeagueInstall(){
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// add the version number
	add_option(SPORT_LEAGUE_VAR_NAME . '_version', SPORT_LEAGUE_VERSION);
	// add the feed options
	add_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish', 1);
	add_option(SPORT_LEAGUE_VAR_NAME . '_feeds_rss', 1);
	add_option(SPORT_LEAGUE_VAR_NAME . '_feeds_json', 1);

	/**
	 * Create the database tables
	 */
	// set up the table for storing seasons
	$seasonsTable = SPORT_LEAGUE_DB_PREFIX . 'seasons';
	$sql = "CREATE TABLE IF NOT EXISTS " . $seasonsTable . " (
		id smallint(4) unsigned NOT NULL AUTO_INCREMENT,
		date_start date NOT NULL,
		date_end date NOT NULL,
		active tinyint(1) unsigned NOT NULL DEFAULT '0',
		timestamp_created datetime NOT NULL,
		timestamp_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY date_start (date_start),
		UNIQUE KEY date_end (date_end),
		KEY active (active)
	);";
	dbDelta($sql);

	// set up the table for storing tournaments
	$tournamentsTable = SPORT_LEAGUE_DB_PREFIX . 'tournaments';
	$sql = "CREATE TABLE IF NOT EXISTS " . $tournamentsTable . " (
		id smallint(4) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(125) NOT NULL,
		timestamp_created datetime NOT NULL,
		timestamp_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	);";
	dbDelta($sql);

	// set up the table for storing rounds
	$roundsTable = SPORT_LEAGUE_DB_PREFIX . 'rounds';
	$sql = "CREATE TABLE IF NOT EXISTS " . $roundsTable . " (
		id smallint(4) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(125) NOT NULL,
		timestamp_created datetime NOT NULL,
		timestamp_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY name (name)
	);";
	dbDelta($sql);

	// remove any rounds from the table (in case the table already existed)
	$query = "TRUNCATE TABLE `" . SPORT_LEAGUE_DB_PREFIX . "rounds`";
	$wpdb->query($query);
	// now add the list of pre-defined rounds
	$query ="INSERT INTO
					`" . SPORT_LEAGUE_DB_PREFIX . "rounds`
				(
					`name`,
					`timestamp_created`,
					`timestamp_modified`
				)
				VALUES
				(
					'Round 1',
					NOW(),
					NOW()
				), (
					'Round 2',
					NOW(),
					NOW()
				), (
					'Round 3',
					NOW(),
					NOW()
				), (
					'Round 4',
					NOW(),
					NOW()
				), (
					'Round 5',
					NOW(),
					NOW()
				), (
					'Round 6',
					NOW(),
					NOW()
				), (
					'Quarter-Final',
					NOW(),
					NOW()
				), (
					'Semi-Final',
					NOW(),
					NOW()
				), (
					'Final',
					NOW(),
					NOW()
				)";
	$wpdb->query($query);


	// set up the table for holding teams
	$teamsTable = SPORT_LEAGUE_DB_PREFIX . 'teams';
	$sql = "CREATE TABLE IF NOT EXISTS " . $teamsTable . " (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(60) NOT NULL,
		logo varchar(255) NOT NULL,
		timestamp_created datetime NOT NULL,
		timestamp_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY name (name)
	);";
	dbDelta($sql);

	// set up the table for holding fixtures
	$fixturesTable = SPORT_LEAGUE_DB_PREFIX . 'fixtures';
	$sql = "CREATE TABLE IF NOT EXISTS " . $fixturesTable . " (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		season_id int(10) unsigned NOT NULL,
		tournament_id smallint(4) unsigned NOT NULL,
		round varchar(125) NOT NULL,
		team_1_id int(10) unsigned NOT NULL,
		team_2_id int(10) unsigned NOT NULL,
		timestamp_start datetime NOT NULL,
		timestamp_end datetime NOT NULL,
		tbc tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'flags whether the fixture times are TBC or not',
		team_1_score tinyint(2) unsigned NOT NULL DEFAULT '0',
		team_2_score tinyint(2) unsigned NOT NULL DEFAULT '0',
		match_report_pre longtext NOT NULL,
		match_report_post longtext NOT NULL,
		`ticket_url` VARCHAR(2000) NOT NULL,
		closed tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'flags whether the fixture has ended or not',
		timestamp_created datetime NOT NULL,
		timestamp_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY season_id (season_id),
		KEY tournament_id (tournament_id),
		KEY team_1_id (team_1_id),
		KEY team_2_id (team_2_id),
		KEY closed (closed)
	);";
	dbDelta($sql);


	/**
	 * Add the db constraints
	 */
	// TODO - adding constraints doesn't seem to work (They don't get added)
	// fixtures table constraints
	$query = "ALTER TABLE " . $fixturesTable . "
		ADD CONSTRAINT " . $fixturesTable . "_1 FOREIGN KEY (season_id) REFERENCES " . $seasonsTable . " (id) ON DELETE CASCADE ON UPDATE CASCADE,
		ADD CONSTRAINT " . $fixturesTable . "_1 FOREIGN KEY (tournament_id) REFERENCES " . $tournamentsTable . " (id) ON DELETE CASCADE ON UPDATE CASCADE,
		ADD CONSTRAINT " . $fixturesTable . "_2 FOREIGN KEY (team_1_id) REFERENCES " . $teamsTable . " (id) ON DELETE CASCADE ON UPDATE CASCADE,
		ADD CONSTRAINT " . $fixturesTable . "_3 FOREIGN KEY (team_2_id) REFERENCES " . $teamsTable . " (id) ON DELETE CASCADE ON UPDATE CASCADE;";
	$wpdb->query($query);


	// set up the rewrite rules
	sportLeagueAddRewriteRules();
	// flush the rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function sportLeagueUpdate(){
	// update the version number
	add_option(SPORT_LEAGUE_VAR_NAME . '_version', SPORT_LEAGUE_VERSION);

	// update the fixtures table
	$fixturesTable = SPORT_LEAGUE_DB_PREFIX . 'fixtures';
	$sql = "ALTER TABLE " . $fixturesTable . "
		ADD
			tbc tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'flags whether the fixture times are TBC or not'
	);";
	dbDelta($sql);
}

function sportLeagueActivate(){
	$version	= get_option(SPORT_LEAGUE_VAR_NAME . '_version');

	if(!!$version){
		// plugin not currently installed
		sportLeagueInstall();
	}elseif(version_compare($version, SPORT_LEAGUE_VERSION, '<')){
		// version requires update
		sportLeagueUpdate();
	}
}

// set up the activation hook to run the installation function
register_activation_hook(__FILE__, 'sportLeagueActivate');


function sportLeagueUninstall(){
	global $wpdb;

	// delete the version number
	delete_option(SPORT_LEAGUE_VAR_NAME . '_version');
	// delete the feed options
	delete_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish');
	delete_option(SPORT_LEAGUE_VAR_NAME . '_feeds_rss');
	delete_option(SPORT_LEAGUE_VAR_NAME . '_feeds_json');

	// TODO - uncomment below when going live
	/*$wpdb->query("DROP TABLE " . SPORT_LEAGUE_DB_PREFIX . "seasons");
	$wpdb->query("DROP TABLE " . SPORT_LEAGUE_DB_PREFIX . "tournaments");
	$wpdb->query("DROP TABLE " . SPORT_LEAGUE_DB_PREFIX . "rounds");
	$wpdb->query("DROP TABLE " . SPORT_LEAGUE_DB_PREFIX . "teams");
	$wpdb->query("DROP TABLE " . SPORT_LEAGUE_DB_PREFIX . "fixtures");*/

	// flush the rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

// set up the de-activation hook to run the un-installation function
register_deactivation_hook(__FILE__, 'sportLeagueUninstall');


/********************************************
 * Now start the actual plugin functionality
 *******************************************/
// include the relevant classes
require_once(SPORT_LEAGUE_DIR . 'classes/Pagination.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/Message.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/FormValidation.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/Team.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/Teams.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/Fixture.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/Fixtures.class.php');

require_once(SPORT_LEAGUE_DIR . 'classes/RSSFeed.class.php');
require_once(SPORT_LEAGUE_DIR . 'classes/SportFeeds.class.php');


Message::init();


/**
 * Handles all output functionality for a
 * widget style fixture display
 *
 * @param Fixture $fixture
 * @param string $type
 * @param int $location
 * @param string $title
 * @param bool $report
 * @param string|int $team
 * @return string
 */
function sportLeagueWidgetBox($fixture, $type = '', $location = -1, $title = '', $report = true, $team = ''){
	switch(strtolower($type)){
		case 'latest':
		case 'last':
			$type = 'latest';
		break;
		case 'next':
			$type = 'next';
		break;
		default:
			$type = '';
		break;
	}

	// get a textual representation of the location
	$strLocation = ($location === Fixtures::LOCATION_HOME) ? 'home' : (($location === Fixtures::LOCATION_AWAY) ? 'away' : '');

	$output = '<div class="' . SPORT_LEAGUE_CLASS_APPEND . 'matchWidget' . (($type != '') ? ' ' . $type : '') . (($strLocation != '') ? ' ' . $strLocation : '') . '">';

	if($title != ''){
		$output .= '<h2>' . htmlentities($title) . '</h2>';
	}elseif($type == 'latest'){
		$output .= '<h2>Latest Match Result</h2>';
	}elseif($type == 'next'){
		if($team == 'feature'){
			// team specified as 'feature' - set the ID to the featured team
			$team = Teams::getInstance()->getFeaturedID();
		}

		if(is_numeric($team) && ($team > 0)){
			// team ID defined - get the team
			if(!is_null($team = Teams::getInstance()->getTeam($team))){
				// team found
				$team = $team->getName();
			}else{
				// team not found
				$team = '';
			}
		}

		if($team != ''){
			// ensure no html is in the team name
			$team = htmlentities($team);
			// append an apostrophe
			$team = ' ' . $team . ((substr($team, strlen($team)-1) == 's') ? "'" : "'s");
		}

		$output .= '<h2>Next' . $team . (($strLocation != '') ? ' ' . ucwords($strLocation) : '') . ' Fixture</h2>';
	}

	if(is_a($fixture, 'Fixture')){
		$team1 = $fixture->getTeam(1);
		$team2 = $fixture->getTeam(2);

		$output .= '<h3>Team 1</h3>' .
		'<dl class="team1">' .
			'<dt>Team Icon</dt>' .
			'<dd class="logo">' . (is_null($team1) ? '' : $team1->getLogo(46)) . '</dd>' .

			'<dt>Team Name</dt>' .
			'<dd class="name">' . (is_null($team1) ? '<span title="To Be Confirmed">TBC</span>' : $team1->getName()) . '</dd>' .

			(!$fixture->isOpen() ?
			'<dt>Team Score</dt>' .
			'<dd class="score">' . $fixture->getTeamScore(1) . '</dd>'
			: '') .
		'</dl>' .

		'<h3>Team 2</h3>' .
		'<dl class="team2">' .
			'<dt>Team Icon</dt>' .
			'<dd class="logo">' . (is_null($team2) ? '' : $team2->getLogo(46)) . '</dd>' .

			'<dt>Team Name</dt>' .
			'<dd class="name">' . (is_null($team2) ? '<span title="To Be Confirmed">TBC</span>' : $team2->getName()) . '</dd>' .

			(!$fixture->isOpen() ?
			'<dt>Team Score</dt>' .
			'<dd class="score">' . $fixture->getTeamScore(2) . '</dd>'
			: '') .
		'</dl>';


		if($report){
			// get the fixture report
			if(!$fixture->isOpen()){
				if(($report = $fixture->getPostMatchReport()) != ''){
					$output .= '<a href="' . sprintf(SPORT_LEAGUE_REPORT_URL, $fixture->getID()) . '" title="view post-match report" class="reportBtn">View match report</a>';
				}
			}else{
				$dateTime = strtotime($fixture->getStartDateTime());
				$hours = date('g', $dateTime);
				$minutes = date('i', $dateTime);
				$amPm = date('a', $dateTime);
				if(($hours == '12') && ($minutes == 0) && ($amPm == 'am')){
					$kickOff = 'TBC';
				}else{
					$kickOff = $hours . (($minutes > 0) ? ':' . $minutes : '') . $amPm;
				}

				$output .= '<div class="date">' . date('l jS F', $dateTime) . ' - ' . $kickOff . ($fixture->isTBC() ? ' (TBC)' : '') . '</div>';

				if(($report = $fixture->getPreMatchReport()) != ''){
					$output .= '<a href="' . sprintf(SPORT_LEAGUE_REPORT_URL, $fixture->getID()) . '" title="view pre-match report" class="reportBtn">View pre-match information</a>';
				}
			}
		}
	}elseif($type == 'latest'){
		$output .= '<div class="emptyMsg">No matches played</div>';
	}elseif($type == 'next'){
		$output .= '<div class="emptyMsg">No upcoming matches</div>';
	}else{
		$output .= '<div class="emptyMsg">No matches found</div>';
	}

	$output .= '</div>'.PHP_EOL;

	return $output;
}


/**
 * Returns the HTML output for the given Fixture ID
 *
 * @param int $fixtureID
 * @param bool $report
 * @return string
 */
function sportLeagueGetMatch($fixtureID = -1, $report = true){
	return sportLeagueWidgetBox(Fixtures::getInstance()->getFixture($fixtureID), '', '', '', $report);
}

/**
 * Returns the HTML output for last played fixture
 *
 * @param int $teamID
 * @param bool $report
 * @return string
 */
function sportLeagueLatestMatch($teamID = 1, $report = true){
	return sportLeagueWidgetBox(Fixtures::getInstance()->getLatestFixture($teamID), 'latest', '', '', $report, $teamID);
}

add_action(SPORT_LEAGUE_VAR_NAME .'_latest_match', 'sportLeagueLatestMatch');


/**
 * Returns the HTML output for the next fixture
 *
 * @param $teamID
 * @param $location
 * @param bool $report
 * @return string
 */
function sportLeagueNextMatch($teamID = -1, $location = -1, $report = true){
	return sportLeagueWidgetBox(Fixtures::getInstance()->getNextFixture($teamID, $location), 'next', $location, '', $report, $teamID);
}

add_action(SPORT_LEAGUE_VAR_NAME .'_next_match', 'sportLeagueNextMatch');


/**
 * Returns the HTML output for the next home fixture
 *
 * @param int $teamID
 * @param bool $report
 * @return string
 */
function sportLeagueNextHomeMatch($teamID = -1, $report = true){
	return sportLeagueNextMatch($teamID, Fixtures::LOCATION_HOME, $report);
}

add_action(SPORT_LEAGUE_VAR_NAME .'_next_home_match', 'sportLeagueNextHomeMatch');


/**
 * Returns the HTML output for the next away fixture
 *
 * @param $teamID
 * @param bool $report
 * @return string
 */
function sportLeagueNextAwayMatch($teamID = -1, $report = true){
	return sportLeagueNextMatch($teamID, Fixtures::LOCATION_AWAY, $report);
}

add_action(SPORT_LEAGUE_VAR_NAME .'_next_away_match', 'sportLeagueNextAwayMatch');


/**
 * Returns the HTML output to display a
 * list of closed fixtures
 *
 * @param int|array $season
 * @param int $team
 * @return string
 */
function sportLeagueFixtureResults($season = -1, $team = -1){
	if(is_array($season) && !empty($season)){
		extract(shortcode_atts(array(
			'season' => -1,
			'team' => -1
		), $season));
	}

	$output = '<div class="' . SPORT_LEAGUE_CLASS_APPEND . 'fixtureResults">';
	if(count($fixtures = Fixtures::getInstance()->getClosedFixtures($season, $team)) > 0){
		foreach($fixtures as $fixture){
			$output .= sportLeagueWidgetBox($fixture);
		}
	}else{
		$output .= '<p>No results found</p>';
	}
	$output .= '</div>';

	return $output;
}


/**
 * Returns the HTML output for display a
 * fixture table
 *
 * @param int|array $season
 * @param int $team
 * @return string
 */
function sportLeagueFixtureTable($season = -1, $team = -1){
	if(is_array($season) && !empty($season)){
		extract(shortcode_atts(array(
			'season' => -1,
			'team' => -1
		), $season));
	}

	$output = '<div class="' . SPORT_LEAGUE_CLASS_APPEND . 'fixtureTable">';

	if(count($fixtures = Fixtures::getInstance()->getFixtures($season, $team)) > 0){
		$output .= '<table cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th class="datetime">Date &amp; KO</th>
					<th class="team home">Home Team</th>
					<th class="score">Score</th>
					<th class="team away">Away Team</th>
					<th class="tournament">Tournament</th>
					<th class="info">Info</th>
					<th class="tickets">Tickets</th>
				</tr>
			</thead>

			<tbody>';

		foreach($fixtures as $i => $fixture){
			// get the fixture report
			$report = $fixture->isOpen() ? $fixture->getPreMatchReport() : $fixture->getPostMatchReport();
			$time = strtotime($fixture->getStartDateTime());

			$hours = date('g', $time);
			$minutes = date('i', $time);
			$amPm = date('a', $time);
			if(($hours == '12') && ($minutes == 0) && ($amPm == 'am')){
				$KO = 'TBC';
			}else{
				$KO = $hours . (($minutes > 0) ? ':' . $minutes : '') . $amPm;
			}

			$team1 = $fixture->getTeam(1);
			$team2 = $fixture->getTeam(2);

			$output .= '<tr class="' . (($i&1) ? 'odd' : 'even') . '">
				<td class="datetime">
					<span class="date">' . date('l jS F', $time) . '</span>
					<span class="time">KO ' . $KO . ($fixture->isTBC() ? ' (TBC)' : '') . '</span>
				</td>
				<td class="team home">' . (is_null($team1) ? '<span title="To Be Confirmed">TBC</span>' : $team1->getName()) . '</td>
				<td class="score">
					'. ($fixture->isOpen() ? 'v' : '<span class="home">' . $fixture->getTeamScore(1) . '</span>-<span class="away">' . $fixture->getTeamScore(2) . '</span>') . '
				</td>
				<td class="team away">' . (is_null($team2) ? '<span title="To Be Confirmed">TBC</span>' : $team2->getName()) . '</td>
				<td class="tournament">' . $fixture->getTournament()->name . (($fixture->getRound() != '') ? ' ' . $fixture->getRound() : '') . '</td>
				<td class="info">' .
					(($report != '') ? '<a href="' . sprintf(SPORT_LEAGUE_REPORT_URL, $fixture->getID()) . '/' . ($fixture->isOpen() ? 'pre' : 'post') . '" title="view match report" class="reportBtn">Info</a>' : '') .
				'</td>
				<td class="tickets">'
					. ((($fixture->getTicketURL() != '') && $fixture->isOpen()) ? '<a href="' . $fixture->getTicketURL() . '" title="Buy Tickets" class="ticketButton">Buy now</a>' : '') .
				'</td>
			</tr>';
		}

		$output .= '</tbody>

			<tfoot>
				<tr>
					<th class="datetime">Date &amp; KO</th>
					<th class="team home">Home Team</th>
					<th class="score">Score</th>
					<th class="team away">Away Team</th>
					<th class="tournament">Tournament</th>
					<th class="info">Info</th>
					<th class="tickets">Tickets</th>
				</tr>
			</tfoot>
		</table>';
	}else{
		$output .= '<p>No results found</p>';
	}
	$output .= '</div>';

	return $output;
}

/**
 * Returns the review for the given fixture ID.
 * $type = 'post' | 'pre'
 * If no type is defined, then one will be automatically
 * decided, depending on whether the fixture has been
 * closed or not.
 *
 * @param int $fixtureID
 * @param string $type
 * @return string
 */
function sportLeagueFixtureReview($fixtureID, $type = ''){
	$fixtureClass = Fixtures::getInstance();

	$report = '';
	if(!is_null($fixture = $fixtureClass->getFixture($fixtureID))){
		// fixture exists - check which review we need
		switch($type){
			case 'pre':
				// pre-match review
				$report = $fixture->getPreMatchReport();
			break;
			case 'post':
				// post match review
				$report = $fixture->getPostMatchReport();
			break;
			default:
				// none specified - if it's open return the pre-match, if closed return the post match
				$report = $fixture->isOpen() ? $fixture->getPreMatchReport() : $fixture->getPostMatchReport();
			break;
		}
	}

	return ($report == '') ? '<p>No report found</p>' : str_replace(']]>', ']]&gt;', apply_filters('the_content', $report));
}


function sportLeaguePage($type, $data){
	$content = '';
	switch(strtolower($type)){
		case 'review':
		case 'report':
			// fixture review
			$pageTitle = 'Review';
			if(isset($data['fixture'])){
				$reportType = isset($data['pre']) ? 'pre' : (isset($data['post']) ? 'post' : '');

				if($reportType != ''){
					$pageTitle = ucwords($reportType) . '-Match ' . $pageTitle;
				}

				$content = '<div class="sportLeague_fixtureResults">' . sportLeagueGetMatch($data['fixture'], false) . '</div>' .
							'<div class="report">' . sportLeagueFixtureReview($data['fixture'], $reportType) . '</div>';
			}
		break;
		case 'fixtures':
			// view fixtures
			$pageTitle = 'Fixtures';

			if(isset($data['team']) && ($data['team'] != '')){
				$teamID = (is_numeric($data['team']) && ($data['team'] > 0)) ? $data['team'] : (($data['team'] == 'all') ? -1 : 'feature');
			}else{
				$teamID = 'feature';
			}
			$content = sportLeagueFixtureTable(-1, $teamID);
		break;
		case 'results':
			// view results
			$pageTitle = 'Fixture Results';

			if(isset($data['team']) && ($data['team'] != '')){
				$teamID = (is_numeric($data['team']) && ($data['team'] > 0)) ? $data['team'] : (($data['team'] == 'all') ? -1 : 'feature');
			}else{
				$teamID = 'feature';
			}
			$content = sportLeagueFixtureResults(-1, $teamID);
		break;
		case 'feed':
			// view feeds

			$feeds = new SportFeeds();

			// determine the output type
			$type = (isset($data['json']) && ($data['json'] != '')) ? 'json' : ((isset($data['rss']) && ($data['rss'] != '')) ? 'rss' : '');

			if($type != ''){
				// type was defined - check that we are allowed to output that feed type
				if(get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_' . $type)){
					// this feed type is allowed

					// get the team ID
					if(isset($data['team']) && ($data['team'] != '')){
						$teamID = (is_numeric($data['team']) && ($data['team'] > 0)) ? $data['team'] : (($data['team'] == 'all') ? -1 : 'feature');
					}else{
						$teamID = 'feature';
					}

					// get the feed limit
					$limit = get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_limit', 10);

					// check which feed we want
					switch($data[$type]){
						case 'fixtures':
							// feed for upcoming fixtures
							$feeds->buildFixtures(-1, $teamID, $limit);
						break;
						case 'results':
							// feed for previous fixtures
							$feeds->buildResults(-1, $teamID, $limit);
						break;
						default:
							// invalid type
							sportLeagueHTTPStatus(404, true);
						break;
					}

					$feeds->output($type);
				}
			}

			// if we reach here, no output type feed was found
			sportLeagueHTTPStatus(404, true);
		break;
		default:
			// un-recognised action
			sportLeagueHTTPStatus(404, true);
		break;
	}

	// include the page header
	get_header();
	// output the page
	include(SPORT_LEAGUE_DIR . 'page.php');
	// include the page sidebar
	get_sidebar();
	// include the page footer
	get_footer();
	// stop the page from progressing
	exit;
}


/****************************
 * Handle plugin re-writes
 ****************************/
/*
 * Defines the plugin rewrite rules (used on install
 */
function sportLeagueFlushRules(){
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
 * Adds the rewrite rules
 *
 * @return mixed
 */
function sportLeagueAddRewriteRules(){
	global $wp_rewrite;
	$rewrite_tag = '%' . SPORT_LEAGUE_QUERY_NAME . '%';
	$wp_rewrite->add_rewrite_tag($rewrite_tag, '(.+?)', SPORT_LEAGUE_QUERY_NAME . '=' );

	$new_rule = array(
		SPORT_LEAGUE_QUERY_NAME . '/((fixtures|results|review|report|feed)(/.*)?)$' => 'index.php?' . SPORT_LEAGUE_QUERY_NAME . '=$matches[1]'
	);
	$wp_rewrite->rules = $new_rule + (($wp_rewrite->rules === null) ? array() : $wp_rewrite->rules);

	return $wp_rewrite->rules;
}

/**
 * Allows use of custom query variables
 *
 * @param $public_query_vars
 * @return array
 */
function sportLeagueAddCustomPageVariables($public_query_vars){
	$public_query_vars[] = SPORT_LEAGUE_QUERY_NAME;

	return $public_query_vars;
}

/**
 * Handles re-direct/display of plugin pages
 */
function sportLeagueRedirectFile(){
	global $wp_query;

	if(isset($wp_query->query_vars[SPORT_LEAGUE_QUERY_NAME])){
		$vars = explode('/', $wp_query->query_vars[SPORT_LEAGUE_QUERY_NAME]);

		$page = array_shift($vars);

		$data = array();
		foreach($vars as $var){
			$var = explode('-', $var);
			$data[array_shift($var)] = implode('-', $var);
		}

		sportLeaguePage($page, $data);
	}
}

add_filter('init', 'sportLeagueFlushRules');
add_filter('generate_rewrite_rules', 'sportLeagueAddRewriteRules');
add_filter('query_vars', 'sportLeagueAddCustomPageVariables');
add_action('template_redirect', 'sportLeagueRedirectFile');



// include the widgets file
include_once(SPORT_LEAGUE_DIR . 'widgets.php');


include_once(SPORT_LEAGUE_DIR . 'admin.php');



/**
 * Set up the short codes
 */
function sportLeagueRegisterShortcodes(){
	add_shortcode('sportleague-results', 'sportLeagueFixtureResults');
	add_shortcode('sportleague-table', 'sportLeagueFixtureTable');
}

add_action('init', 'sportLeagueRegisterShortcodes');



/**
 * Include any required js|css
 */
function sportLeagueLoadJSAndCSS(){
	wp_enqueue_style('sportleague.css', SPORT_LEAGUE_PLUGIN_URL . 'css/sportleague.css', array(), time());
}
add_action('init', 'sportLeagueLoadJSAndCSS');


/**
 * Add the RSS feed links to the head of the page
 */
function sportLeagueFeedLinks(){
	$feedURLs = array(
		'fixtures'	=> rtrim(get_bloginfo('url'), '/') . '/' . SPORT_LEAGUE_QUERY_NAME . '/feed/%s-fixtures',
		'results'	=> rtrim(get_bloginfo('url'), '/') . '/' . SPORT_LEAGUE_QUERY_NAME . '/feed/%s-results'
	);

	if(get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish') && get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_rss')){
		$team = Teams::getInstance()->getFeaturedTeam();
		$team = is_null($team) ? '' : $team->getName() . ' &raquo; ';

		echo PHP_EOL .'<link href="' . sprintf($feedURLs['fixtures'], 'rss') . '" rel="alternate" type="application/rss+xml" title="' . $team . 'Upcoming Fixtures">' . PHP_EOL;
		echo '<link href="' . sprintf($feedURLs['results'], 'rss') . '" rel="alternate" type="application/rss+xml" title="' . $team . 'Fixture Results">' . PHP_EOL . PHP_EOL;
	}
}
add_action('wp_head','sportLeagueFeedLinks');


/**
 * @param $code
 * @param bool $exit
 * @return bool
 */
function sportLeagueHTTPStatus($code, $exit = false){
	switch($code){
		case 404:
			$codeText = 'Not Found';
			$description = 'The page you are looking for could not be found';
		break;
		case 403:
			$codeText = 'Forbidden';
			$description = 'You do not have access to this page';
		break;
		default:
			// un-recognised code
			if(is_numeric($code) && ($code >= 100) && ($code <= 599)){
				// code is numeric and is within the outer bounds for status codes, so may be valid
				// allow it, as we aren't catching all codes above
				$code = (int) $code;
				$codeText = '';
				$description = '';
			}else{
				// code appears to be invalid
				return false;
			}
		break;
	}

	if(!headers_sent()){
		// header hasn't been set - okay to define
		header('HTTP/1.0 ' . $code . ' ' . $codeText);
	}

	if($exit){
		// we are exiting output - display the status message
		echo '<h1>' . $code . (($codeText != '') ? ' - ' . $codeText : '') . '</h1>' .
				(($description != '') ? '<p>' . $description . '</p>' : '');
		exit;
	}
}
