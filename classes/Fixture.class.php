<?php
/**
 * Author: lee
 * Date Created: 28/08/2012 16:08
 */

class Fixture{
	private $id					= 0;
	private $season_id			= 0;
	private $tournament_id		= 0;
	private $round				= '';
	private $team_1_id			= 0;
	private $team_2_id			= 0;
	private $team_1_score		= 0;
	private $team_2_score		= 0;
	private $timestamp_start	= '';
	private $timestamp_end		= '';
	private $tbc				= false;
	private $match_report_pre	= '';
	private $match_report_post	= '';
	private $ticket_url			= '';
	private $closed				= false;

	private $teams				= array();	// contains a list of the teams competing in the fixture

	public function __construct(array $attributes){
		foreach($attributes as $key => $val){
			if(isset($this->{$key})){
				$this->{$key} = $val;
			}
		}
	}

	/**
	 * Returns the fixture's ID
	 *
	 * @return int
	 */
	public function getID(){
		return $this->id;
	}

	/**
	 * Returns the fixture's Season ID
	 *
	 * @return int
	 */
	public function getSeasonID(){
		return $this->season_id;
	}

	/**
	 * Returns the fixture's Tournament ID
	 *
	 * @return int
	 */
	public function getTournamentID(){
		return $this->tournament_id;
	}

	/**
	 * Returns the ID for the given team number.
	 * This is the team number (1|2) NOT team ID
	 *
	 * @param $intNum
	 * @return int
	 */
	protected function getTeamID($intNum){
		return ($intNum == 1) ? $this->team_1_id : (($intNum == 2) ? $this->team_2_id : 0);
	}

	/**
	 * Returns the score for the given team number
	 * This is team number (1|2) NOT team ID
	 *
	 * @param $intNum
	 * @return int
	 */
	public function getTeamScore($intNum){
		return ($intNum == 1) ? $this->team_1_score : (($intNum == 2) ? $this->team_2_score : 0);
	}

	/**
	 * Takes a team number (1|2) and returns
	 * the relevant team from the fixture.
	 * If no team is found null is returned
	 *
	 * @param $intNum
	 * @return null|Team
	 */
	public function getTeam($intNum){
		if(is_numeric($intNum) && (($intNum == 1) || ($intNum == 2))){
			// the team number is valid
			if(isset($this->teams[$intNum])){
				// the team has already been cached - return it
				return $this->teams[$intNum];
			}else{
				// the team hasn't been cached - collect it
				$id = $this->getTeamID($intNum);
				if(($id > 0) && !is_null($team = Teams::getInstance()->getTeam($id))){
					// the team was found
					$this->teams[$intNum] = $team;
					return $this->teams[$intNum];
				}
			}
		}

		return null;
	}

	/**
	 * Returns a list of the teams playing this fixture
	 *
	 * @return array
	 */
	public function getTeams(){
		return array(
			$this->getTeam(1),
			$this->getTeam(2)
		);
	}

	/**
	 * Returns the date of the fixture in the format:
	 * Y-m-d
	 *
	 * @return string
	 */
	public function getDate(){
		return date('Y-m-d', strtotime($this->timestamp_start));
	}

	/**
	 * Returns the time of the start of the game
	 *
	 * @return string
	 */
	public function getStartTime(){
		return date('H:i', strtotime($this->timestamp_start));
	}

	/**
	 * Returns the full starting date and time
	 *
	 * @return string
	 */
	public function getStartDateTime(){
		return $this->getDate() . ' ' . $this->getStartTime();
	}

	/**
	 * Returns the time of the end of the game.
	 * If the game is still running, this is just an estimate
	 *
	 * @return string
	 */
	public function getEndTime(){
		return date('H:i', strtotime($this->timestamp_end));
	}

	/**
	 * Returns the full ending date and time
	 *
	 * @return string
	 */
	public function getEndDateTime(){
		return $this->getDate() . ' ' . $this->getEndTime();
	}

	/**
	 * Returns whether the fixture time/date is To Be Confirmed
	 *
	 * @return bool
	 */
	public function isTBC(){
		return !!$this->tbc;
	}

	/**
	 * Returns whether the fixture is open or not
	 *
	 * @return bool
	 */
	public function isOpen(){
		return !$this->closed;
	}

	/**
	 * Returns the winner of the fixture, as a Team object.
	 * If the game is a draw null is returned.
	 * This can be used during the game, to determine
	 * the current leader, but may be slower than simply
	 * comparing the score.
	 *
	 * If the optional $returnNumber == true, then the team
	 * number will be returned, instead of the Team object.
	 * (This is the team number (1|2), NOT ID.
	 *
	 * @param bool $returnNumber
	 * @return null|Team
	 */
	public function getWinner($returnNumber = false){
		$intScore1 = $this->getTeamScore(1);
		$intScore2 = $this->getTeamScore(2);

		// determine the winning team number
		$winner = ($intScore1 > $intScore2) ? 1 : (($intScore1 < $intScore2) ? 2 : null);

		if($returnNumber){
			return $winner;
		}else{
			return !is_null($winner) ? $this->getTeam($winner) : null;
		}
	}

	/**
	 * Returns the fixture's pre-match report
	 * or an empty string if none is defined
	 *
	 * @return string
	 */
	public function getPreMatchReport(){
		return $this->match_report_pre;
	}

	/**
	 * Returns the fixture's post-match report
	 * or an empty string if none is defined
	 *
	 * @return string
	 */
	public function getPostMatchReport(){
		return $this->match_report_post;
	}

	/**
	 * Returns the tournament for the fixture
	 *
	 * @return stdClass|null
	 */
	public function getTournament(){
		global $wpdb;

		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "tournaments
					WHERE
						id = " . $wpdb->escape($this->getTournamentID()) . "
					LIMIT 1";
		return $wpdb->get_row($query);
	}

	/**
	 * Returns the round for the fixture
	 *
	 * @return string
	 */
	public function getRound(){
		return $this->round;
	}

	/**
	 * Returns the URL for purchasing tickets
	 * for the fixture
	 *
	 * @return string
	 */
	public function getTicketURL(){
		return $this->ticket_url;
	}
}
?>