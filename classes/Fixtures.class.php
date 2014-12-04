<?php
/**
 * Author: lee
 * Date Created: 28/08/2012 15:21
 */

class Fixtures{
	private static $instance = null;	// holds the single instance of the class

	// define the class constants
	const LOCATION_HOME = 0;			// home location
	const LOCATION_AWAY = 1;			// away location

	private $db = null;					// stores the database object

	private function __construct(){
		global $wpdb;

		$this->db =& $wpdb;
	}

	/**
	 * @static
	 * @return Fixtures
	 */
	public static function getInstance(){
		if(is_null(self::$instance)){
			self::$instance = new Fixtures();
		}

		return self::$instance;
	}

	/**
	 * Returns the ID of the featured team
	 *
	 * @return int
	 */
	public function getFeaturedTeamID(){
		return Teams::getInstance()->getFeaturedID();
	}

	/**
	 * Returns a list of all fixtures for the
	 * given season.
	 * If no season is specified, the latest
	 * season is used.
	 * Returns an empty array if no fixtures
	 * are found.
	 *
	 * @param string|int $seasonID
	 * @param int $teamID
	 * @param int $tournamentID
	 * @param bool $includeInactive
	 * @param string $orderBy
	 * @param string $order
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getFixtures($seasonID = -1, $teamID = -1, $tournamentID = -1, $includeInactive = false, $orderBy = '', $order = 'ASC', $limit = 0, $offset = 0){
		if($seasonID == 'all'){
			$seasonID = 0;
		}elseif(!is_numeric($seasonID) || ($seasonID <= 0)){
			// no season ID specified - default to the latest season
			if(is_null($season = $this->getLatestSeason())){
				// no latest season found
				return array();
			}else{
				$seasonID = $season->id;
			}
		}
		$seasonID = $this->db->escape((int) $seasonID);

		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));

		// any extra 'FROM' tables
		$from = array();
		// any extra 'WHERE' statements
		$where = array();

		// determine the order by
		$order = (strtoupper($order) == 'DESC') ? "DESC" : "ASC";
		switch($orderBy){
			case 'status':
			case 'closed':
				$orderBy = "fixtures.closed " . $order;
			break;
			case 'tournament':
				$from[] = SPORT_LEAGUE_DB_PREFIX . "tournaments AS tournaments";
				$where[] = "fixtures.tournament_id = tournaments.id";
				$orderBy = "tournaments.name " . $order;
			break;
			case 'team_1':
			case 'home_team':
				$from[] = SPORT_LEAGUE_DB_PREFIX . "teams AS teams";
				$where[] = "fixtures.team_1_id = teams.id";
				$orderBy = "teams.name " . $order;
			break;
			case 'team_2':
			case 'away_team':
				$from[] = SPORT_LEAGUE_DB_PREFIX . "teams AS teams";
				$where[] = "fixtures.team_2_id = teams.id";
				$orderBy = "teams.name " . $order;
			break;
			case 'date':
			case 'timestamp_start':
			case '':
				$orderBy = "fixtures.timestamp_start " . $order . ",
							fixtures.timestamp_end " . $order;
			break;
			default:
				$orderBy = "fixtures." . $orderBy . " " . $order;
			break;
		}

		// determine the limit
		if(is_numeric($limit) && ($limit > 0)){
			$limit = "LIMIT " . ((is_numeric($offset) && ($offset > 0)) ? $offset . ", " : "") . $limit;
		}else{
			$limit = "";
		}

		$query = "SELECT
						fixtures.*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
						" . (!empty($from) ? ", " . implode(',', $from) : "") . "
					WHERE
						" . (($seasonID > 0) ? " fixtures.season_id = " . $seasonID . " AND" : "") . "
						fixtures.season_id = seasons.id
						" . (!$includeInactive ? " AND seasons.active = 1" : "") . "
						" . (
						($teamID > 0) ?
							"AND (fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID . ")"
							:
							""
						) . "
						" . (
						($tournamentID > 0) ?
							"AND fixtures.tournament_id = " . $tournamentID
							:
							""
						) . "
						" . (!empty($where) ? "AND " . implode('AND', $where) : "") . "
					ORDER BY
						" . $orderBy . "
					" . $limit;
		if(count($rows = $this->db->get_results($query, ARRAY_A)) > 0){
			foreach($rows as $k => $row){
				$rows[$k] = new Fixture($row);
			}

			return $rows;
		}

		return array();
	}

	/**
	 * Returns a count of fixtures
	 *
	 * @param $seasonID
	 * @param $teamID
	 * @param int $tournamentID
	 * @param bool $includeInactive
	 * @return int
	 */
	public function getFixtureCount($seasonID = -1, $teamID = -1, $tournamentID = -1, $includeInactive = false){
		if($seasonID == 'all'){
			$seasonID = 0;
		}elseif(!is_numeric($seasonID) || ($seasonID <= 0)){
			// no season ID specified - default to the latest season
			if(is_null($season = $this->getLatestSeason())){
				// no latest season found
				return 0;
			}else{
				$seasonID = $season->id;
			}
		}
		$seasonID = $this->db->escape((int) $seasonID);

		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));

		$query = "SELECT
						fixtures.id
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
					WHERE
						" . (($seasonID > 0) ? " fixtures.season_id = " . $seasonID . " AND" : "") . "
						fixtures.season_id = seasons.id
						" . (!$includeInactive ? " AND seasons.active = 1" : "") . "
						" . (
						($teamID > 0) ?
							"AND (fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID . ")"
							:
							""
						) . "
						" . (
						($tournamentID > 0) ?
							"AND fixtures.tournament_id = " . $tournamentID
							:
							""
						);
		return count($this->db->get_results($query));
	}

	/**
	 * Returns a list of fixtures for the specified team ID
	 *
	 * @param $teamID
	 * @return array
	 */
	public function getTeamFixtures($teamID){
		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));

		if($teamID > 0){
			$query = "SELECT
							fixtures.*
						FROM
							" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
							" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
						WHERE
							fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID . "
						ORDER BY
							fixtures.timestamp_start ASC,
							fixtures.timestamp_end ASC";
			if(count($rows = $this->db->get_results($query, ARRAY_A)) > 0){
				foreach($rows as $k => $row){
					$rows[$k] = new Fixture($row);
				}

				return $rows;
			}
		}

		return array();
	}

	/**
	 * Returns the fixture for the given ID
	 *
	 * @param $fixtureID
	 * @return Fixture|null
	 */
	public function getFixture($fixtureID){
		if(is_numeric($fixtureID) && ($fixtureID > 0)){
			$query = "SELECT
							*
						FROM
							" . SPORT_LEAGUE_DB_PREFIX . "fixtures
						WHERE
							id = " . $this->db->escape($fixtureID) . "
						LIMIT 1";
			if(count($row = $this->db->get_row($query, ARRAY_A)) > 0){
				return new Fixture($row);
			}
		}

		return null;
	}

	/**
	 * Returns the last played fixture.
	 * If the given $teamID is specified,
	 * then the last fixture that team was
	 * in will be returned.
	 * $teamID can be a numeric ID or the string
	 * 'feature' for the featured team.
	 * $location can be 'home' | 'away'.
	 *
	 * A value of null is returned if no played
	 * fixture is found
	 *
	 * @param $teamID
	 * @param string $location
	 * @return Fixture|null
	 */
	public function getLatestFixture($teamID = -1, $location = ''){
		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));
		$location = ($location === self::LOCATION_HOME) ? '1' : (($location === self::LOCATION_AWAY) ? '2' : -1);

		$query = "SELECT
						fixtures.*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
					WHERE
						fixtures.timestamp_start < NOW() AND
						fixtures.timestamp_end <= NOW() AND
						fixtures.closed = '1'
						" . (
						($teamID > 0) ?
							"AND (" . (
								($location > 0) ?
										"fixtures.team_" . $location . "_id = " . $teamID
										:
										"fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID
							) . ")"
							:
							""
						) . " AND
						fixtures.season_id = seasons.id AND
						seasons.active = 1
					ORDER BY
						fixtures.timestamp_start DESC,
						fixtures.timestamp_end DESC
					LIMIT 1";
		if(count($row = $this->db->get_row($query, ARRAY_A)) > 0){
			return new Fixture($row);
		}

		return null;
	}

	/**
	 * Returns the next fixture to be played.
	 * If the given $teamID is specified,
	 * then the next fixture that team is in
	 * will be returned.
	 * $teamID can be a numeric ID or the string
	 * 'feature' for the featured team.
	 * $location can be 'home' | 'away'.
	 *
	 * A value of null is returned if no next
	 * fixture is found
	 *
	 * @param $teamID
	 * @param string $location
	 * @return Fixture|null
	 */
	public function getNextFixture($teamID = -1, $location = ''){
		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));
		$location = ($location == self::LOCATION_HOME) ? '1' : (($location == self::LOCATION_AWAY) ? '2' : -1);

		$query = "SELECT
						fixtures.*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
					WHERE
						fixtures.timestamp_start > NOW() AND
						fixtures.timestamp_end > NOW() AND
						fixtures.closed = 0
						" . (
						($teamID > 0) ?
							"AND (" . (
								($location > 0) ?
										"fixtures.team_" . $location . "_id = " . $teamID
										:
										"fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID
							) . ")"
							:
							""
						) . " AND
						fixtures.season_id = seasons.id AND
						seasons.active = 1
					ORDER BY
						fixtures.timestamp_start ASC,
						fixtures.timestamp_end ASC
					LIMIT 1";
		if(count($row = $this->db->get_row($query, ARRAY_A)) > 0){
			return new Fixture($row);
		}

		return null;
	}

	/**
	 * Returns a list of open fixtures
	 * for the given Season (or the latest
	 * season, if none is specified).
	 * If no open fixtures are found an
	 * empty array is returned.
	 *
	 * @param int $seasonID
	 * @param int $teamID
	 * @param int $limit
	 * @return array
	 */
	public function getOpenFixtures($seasonID = -1, $teamID = -1, $limit = -1){
		if(!is_numeric($seasonID) || ($seasonID <= 0)){
			// no season ID specified - default to the latest season
			if(is_null($season = $this->getLatestSeason())){
				// no latest season found
				return array();
			}else{
				$seasonID = $season->id;
			}
		}
		$seasonID = $this->db->escape((int) $seasonID);

		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));

		$query = "SELECT
						fixtures.*,
						DATE(fixtures.timestamp_end),
						CURDATE()
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
					WHERE " .
						// get where start datetime is greater than or equal to now
						"fixtures.timestamp_start >= NOW() AND " .
						// get where end date if greater than or equal to current date (don't check time as it sometimes equals 0)
						"DATE(fixtures.timestamp_end) >= CURDATE() AND " .
						"fixtures.closed = '0'
						" . (
						($teamID > 0) ?
							"AND (fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID . ")"
							:
							""
						) . " AND
						fixtures.season_id = " . $seasonID . " AND
						fixtures.season_id = seasons.id AND
						seasons.active = 1
					ORDER BY
						fixtures.timestamp_start ASC,
						fixtures.timestamp_end ASC";
		if(is_numeric($limit) && ($limit > 0)){
			$query .= " LIMIT " . round($limit);
		}

		if(count($rows = $this->db->get_results($query, ARRAY_A)) > 0){
			foreach($rows as $k => $row){
				$rows[$k] = new Fixture($row);
			}

			return $rows;
		}

		return array();
	}

	/**
	 * Returns a list of closed fixtures
	 * for the given Season (or the latest
	 * season, if none is specified).
	 * If no closed fixtures are found an
	 * empty array is returned.
	 *
	 * @param int $seasonID
	 * @param int $teamID
	 * @param int $limit
	 * @return array
	 */
	public function getClosedFixtures($seasonID = -1, $teamID = -1, $limit = -1){
		if(!is_numeric($seasonID) || ($seasonID <= 0)){
			// no season ID specified - default to the latest season
			if(is_null($season = $this->getLatestSeason())){
				// no latest season found
				return array();
			}else{
				$seasonID = $season->id;
			}
		}
		$seasonID = $this->db->escape((int) $seasonID);

		$teamID = $this->db->escape(Teams::getInstance()->getID($teamID));

		$query = "SELECT
						fixtures.*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures AS fixtures,
						" . SPORT_LEAGUE_DB_PREFIX . "seasons AS seasons
					WHERE
						fixtures.timestamp_start < NOW() AND
						fixtures.timestamp_end <= NOW() AND
						fixtures.closed = '1'
						" . (
						($teamID > 0) ?
							"AND (fixtures.team_1_id = " . $teamID . " OR fixtures.team_2_id = " . $teamID . ")"
							:
							""
						) . " AND
						fixtures.season_id = " . $seasonID . " AND
						fixtures.season_id = seasons.id AND
						seasons.active = 1
					ORDER BY
						fixtures.timestamp_start DESC,
						fixtures.timestamp_end DESC";
		if(is_numeric($limit) && ($limit > 0)){
			$query .= " LIMIT " . round($limit);
		}

		if(count($rows = $this->db->get_results($query, ARRAY_A)) > 0){
			foreach($rows as $k => $row){
				$rows[$k] = new Fixture($row);
			}

			return $rows;
		}

		return array();
	}

	/**
	 * Returns a list of all the seasons
	 *
	 * @param string $orderBy
	 * @param string $order
	 * @return array
	 */
	public function getSeasons($orderBy = '', $order = 'ASC'){
		$order = (strtoupper($order) == 'DESC') ? "DESC" : "ASC";
		switch(strtolower($orderBy)){
			case 'end_date':
			case 'enddate':
				$orderBy = "date_end " . $order . ",
							date_start " . $order;
			break;
			default:
				$orderBy = "date_start " . $order . ",
							date_end " . $order;
			break;
		}

		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "seasons
					ORDER BY
						" . $orderBy;
		return  $this->db->get_results($query);
	}

	/**
	 * Returns the season for the given ID
	 *
	 * @param $seasonID
	 * @return stdClass|null
	 */
	public function getSeason($seasonID){
		if(is_numeric($seasonID) && ($seasonID > 0)){
			$query = "SELECT
							*
						FROM
							" . SPORT_LEAGUE_DB_PREFIX . "seasons
						WHERE
							id = " . $this->db->escape($seasonID) . "
						LIMIT 1";
			return $this->db->get_row($query);
		}

		return null;
	}

	/**
	 * Returns the latest open season
	 *
	 * @return mixed
	 */
	public function getLatestSeason(){
		/*$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "seasons
					WHERE
						date_start < NOW() AND
						active = '1'
					ORDER BY
						date_start DESC,
						date_end DESC
					LIMIT 1";*/

		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "seasons
					WHERE
						active = '1'
						date_start < NOW() AND
						date_end >= NOW() AND
					ORDER BY
						date_start DESC,
						date_end DESC
					LIMIT 1";
		if(!is_null($row = $this->db->get_row($query))){
			return $row;
		}else{
			// no active season - get the closest season to the current date
			$query = "SELECT
							*
						FROM
							" . SPORT_LEAGUE_DB_PREFIX . "seasons
						WHERE
							active = '1'
						ORDER BY
							ABS(DATEDIFF(date_start, NOW())) ASC,
							ABS(DATEDIFF(date_end, NOW())) ASC
						LIMIT 1";
			return $this->db->get_row($query);
		}
	}

	/**
	 * Returns a list of all tournaments
	 *
	 * @return array
	 */
	public function getTournaments(){
		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "tournaments
					ORDER BY
						name";
		return $this->db->get_results($query);
	}

	/**
	 * Returns a list of all rounds
	 *
	 * @return array
	 */
	public function getRounds(){
		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "rounds
					ORDER BY
						id";
		return $this->db->get_results($query);
	}

	/**
	 * Returns the round for the given ID
	 * or null, if no round is found
	 *
	 * @param $id
	 * @return stdClass|null
	 */
	public function getRound($id){
		if(is_numeric($id) && ($id > 0)){
			$query = "SELECT
							*
						FROM
							" . SPORT_LEAGUE_DB_PREFIX . "rounds
						WHERE
							id = " . $this->db->escape($id) . "
						LIMIT 1";
			return $this->db->get_row($query);
		}else{
			return null;
		}
	}


	/**
	 * Adds a new fixture to the db.
	 * Returns the new insert ID on success
	 * or -1 on failure.
	 *
	 * @param int $seasonID
	 * @param int $tournamentID
	 * @param string $round
	 * @param int $team1ID
	 * @param int $team2ID
	 * @param int $team1Score
	 * @param int $team2Score
	 * @param string $timeStart
	 * @param string $timeEnd
	 * @param bool $tbc
	 * @param string $ticketURL
	 * @param string $preReport
	 * @param string $postReport
	 * @param bool $closed
	 * @return int
	 */
	public function addFixture($seasonID, $tournamentID, $round, $team1ID, $team2ID, $team1Score, $team2Score, $timeStart, $timeEnd, $tbc, $ticketURL = '', $preReport = '', $postReport = '', $closed = false){
		if(
			is_numeric($seasonID) && ($seasonID > 0) &&
			is_numeric($tournamentID) && ($tournamentID > 0) &&
			is_numeric($team1ID) && ($team1ID >= 0) &&
			is_numeric($team2ID) && ($team2ID >= 0) &&
			is_numeric($team1Score) && ($team1Score >= 0) &&
			is_numeric($team2Score) && ($team2Score >= 0)
		){
			$timeStart = date('Y-m-d H:i:s', strtotime($timeStart));
			$timeEnd = date('Y-m-d H:i:s', strtotime($timeEnd));

			$data = array(
				'season_id' => $seasonID,
				'tournament_id' => $tournamentID,
				'round' => $round,
				'team_1_id' => $team1ID,
				'team_2_id' => $team2ID,
				'team_1_score' => $team1Score,
				'team_2_score' => $team2Score,
				'timestamp_start' => $timeStart,
				'timestamp_end' => $timeEnd,
				'tbc' => ($tbc == true) ? '1' : '0',
				'ticket_url' => $ticketURL,
				'match_report_pre' => $preReport,
				'match_report_post' => $postReport,
				'closed' => (($closed == true) ? '1' : '0')
			);

			if($this->db->insert(SPORT_LEAGUE_DB_PREFIX . "fixtures", $data)){
				return $this->db->insert_id;
			}
		}

		return -1;
	}

	/**
	 * Updates the fixture with the given fixtureID
	 * Returns true on success, false on failure
	 *
	 * @param int $fixtureID
	 * @param int $seasonID
	 * @param int $tournamentID
	 * @param string $round
	 * @param int $team1ID
	 * @param int $team2ID
	 * @param int $team1Score
	 * @param int $team2Score
	 * @param string $timeStart
	 * @param string $timeEnd
	 * @param bool $tbc
	 * @param string $ticketURL
	 * @param string $preReport
	 * @param string $postReport
	 * @param bool $closed
	 * @return bool
	 */
	public function updateFixture($fixtureID, $seasonID, $tournamentID, $round, $team1ID, $team2ID, $team1Score, $team2Score, $timeStart, $timeEnd, $tbc, $ticketURL = '', $preReport = '', $postReport = '', $closed = false){
		if(
			is_numeric($fixtureID) && ($fixtureID > 0) &&
			is_numeric($seasonID) && ($seasonID > 0) &&
			is_numeric($tournamentID) && ($tournamentID > 0) &&
			is_numeric($team1ID) && ($team1ID >= 0) &&
			is_numeric($team2ID) && ($team2ID >= 0) &&
			is_numeric($team1Score) && ($team1Score >= 0) &&
			is_numeric($team2Score) && ($team2Score >= 0)
		){
			$timeStart = date('Y-m-d H:i:s', strtotime($timeStart));
			$timeEnd = date('Y-m-d H:i:s', strtotime($timeEnd));

			$data = array(
				'season_id' => $seasonID,
				'tournament_id' => $tournamentID,
				'round' => $round,
				'team_1_id' => $team1ID,
				'team_2_id' => $team2ID,
				'team_1_score' => $team1Score,
				'team_2_score' => $team2Score,
				'timestamp_start' => $timeStart,
				'timestamp_end' => $timeEnd,
				'tbc' => ($tbc == true) ? '1' : '0',
				'ticket_url' => $ticketURL,
				'match_report_pre' => $preReport,
				'match_report_post' => $postReport,
				'closed' => (($closed == true) ? '1' : '0')
			);

			$where = array(
				'id' => $fixtureID
			);

			return false !== $this->db->update(SPORT_LEAGUE_DB_PREFIX . "fixtures", $data, $where);
		}

		return false;
	}

	/**
	 * Deletes the fixture with the given ID
	 * Returns true on success, false on failure
	 *
	 * @param $fixtureID
	 * @return bool
	 */
	public function removeFixture($fixtureID){
		if(is_numeric($fixtureID) && ($fixtureID > 0)){
			return false !== $this->db->query(
				$this->db->prepare(
					"DELETE FROM
						" . SPORT_LEAGUE_DB_PREFIX . "fixtures
					WHERE
						id = %d
					LIMIT 1",
					$fixtureID
				)
			);
		}

		return false;
	}

	/**
	 * Inserts a season into the db
	 *
	 * @param $startDate
	 * @param $endDate
	 * @return int
	 */
	public function addSeason($startDate, $endDate){
		if(($startDate != '') && ($endDate != '')){
			$data = array(
				'date_start' => date('Y-m-d', strtotime($startDate)),
				'date_end' => date('Y-m-d', strtotime($endDate)),
				'active' => 0
			);

			if($this->db->insert(SPORT_LEAGUE_DB_PREFIX . "seasons", $data)){
				return $this->db->insert_id;
			}
		}

		return -1;
	}

	/**
	 * Updates the season with the given ID
	 *
	 * @param $seasonID
	 * @param $startDate
	 * @param $endDate
	 * @param $active
	 * @return bool
	 */
	public function updateSeason($seasonID, $startDate, $endDate, $active){
		if(is_numeric($seasonID) && ($seasonID > 0) && ($startDate != '') && ($endDate != '')){
			$data = array(
				'date_start' => date('Y-m-d', strtotime($startDate)),
				'date_end' => date('Y-m-d', strtotime($endDate)),
				'active' => !!$active
			);

			$where = array(
				'id' => $seasonID
			);

			return false !== $this->db->update(SPORT_LEAGUE_DB_PREFIX . "seasons", $data, $where);
		}

		return false;
	}

	/**
	 * Returns the season with the given ID
	 *
	 * @param $seasonID
	 * @return bool
	 */
	public function removeSeason($seasonID){
		if(is_numeric($seasonID) && ($seasonID > 0)){
			return false !== $this->db->query(
				$this->db->prepare(
					"DELETE FROM
						" . SPORT_LEAGUE_DB_PREFIX . "seasons
					WHERE
						id = %d
					LIMIT 1",
					$seasonID
				)
			);
		}

		return false;
	}

	/**
	 * Adds a tournament to the db
	 *
	 * @param $name
	 * @return bool
	 */
	public function addTournament($name){
		if($this->db->insert(SPORT_LEAGUE_DB_PREFIX . "tournaments", array('name' => $name))){
			return $this->db->insert_id;
		}

		return false;
	}
}
?>