<?php
/**
 * Author: lee
 * Date Created: 28/08/2012 16:15
 */

class Teams{
	private static $instance = null;	// holds the single instance of the class
	private $db = null;					// stores the database object

	private function __construct(){
		global $wpdb;

		$this->db =& $wpdb;
	}

	/**
	 * @static
	 * @return Teams
	 */
	public static function getInstance(){
		if(is_null(self::$instance)){
			self::$instance = new Teams();
		}

		return self::$instance;
	}

	/**
	 * Returns the URL for viewing team logos
	 *
	 * @static
	 * @return string
	 */
	public static function getLogoURL(){
		return SPORT_LEAGUE_UPLOAD_URL . 'logos/';
	}

	/**
	 * Returns a list of all of the teams
	 *
	 * @param string $orderBy
	 * @param string $order
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getTeams($orderBy = '', $order = 'ASC', $limit = 0, $offset = 0){
		// determine the limit
		if(is_numeric($limit) && ($limit > 0)){
			$limit = "LIMIT " . ((is_numeric($offset) && ($offset > 0)) ? $offset . ", " : "") . $limit;
		}else{
			$limit = "";
		}

		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "teams
					ORDER BY
						" . ((($orderBy == 'name') || ($orderBy == 'logo')) ? $orderBy : 'name') . " " . ((strtoupper($order) == 'DESC') ? "DESC" : "ASC") . "
					" . $limit;
		if(!is_null($rows = $this->db->get_results($query, ARRAY_A))){
			foreach($rows as $k => $row){
				$rows[$k] = new Team($row);
			}

			return $rows;
		}

		return array();
	}

	/**
	 * Returns the team corresponding to
	 * the given team ID or null if the
	 * team isn't found
	 *
	 * @param int $teamID
	 * @return Team|null
	 */
	public function getTeam($teamID){
		$query = "SELECT
						*
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "teams
					WHERE
						id = " . $this->db->escape($teamID) . "
					LIMIT 1";
		if(is_numeric($teamID) && ($teamID > 0) && !is_null($row = $this->db->get_row($query, ARRAY_A))){
			return new Team($row);
		}

		return null;
	}

	/**
	 * Returns a count of all teams in the db
	 *
	 * @return int
	 */
	public function getTeamCount(){
		$query = "SELECT
						id
					FROM
						" . SPORT_LEAGUE_DB_PREFIX . "teams";
		return count($this->db->get_results($query));
	}

	/**
	 * Returns the ID of the featured team
	 *
	 * @return int
	 */
	public function getFeaturedID(){
		return get_option(SPORT_LEAGUE_VAR_NAME . '_featured_team', -1);
	}

	/**
	 * Returns the featured team
	 * or null if none is defined
	 *
	 * @return null|Team
	 */
	public function getFeaturedTeam(){
		return $this->getTeam($this->getFeaturedID());
	}

	/**
	 * Takes a value and determines whether it is a
	 * numerical team ID or 'feature'.
	 * If numerical, the value is returned.
	 * If 'feature' the featured team ID is returned.
	 *
	 * @param $id
	 * @return int
	 */
	public function getID($id){
		return (int)(($id == 'feature') ? $this->getFeaturedID() : ((is_numeric($id) && ($id > 0)) ? $id : -1));
	}

	/**
	 * Adds a team to the db.
	 * Return the newly created team's
	 * ID on success or -1 on failure.
	 *
	 * @param $name
	 * @param $logos
	 * @return int
	 */
	public function addTeam($name, $logos = array()){
		if($name != ''){
			if(!is_array($logos)){
				$logos = array($logos);
			}
			$logos = array_unique(array_filter($logos));
			foreach($logos as $k => $logo){
				$logos[$k] = array(
					'file' => $logo,
					'size' => reset(getimagesize($logo))
				);
			}

			$data = array(
				'name' => $name,
				'logo' => json_encode($logos)
			);

			if($this->db->insert(SPORT_LEAGUE_DB_PREFIX . "teams", $data)){
				return $this->db->insert_id;
			}
		}

		return -1;
	}

	/**
	 * Updates the team with the given fixture ID.
	 * Returns true on success, false on failure.
	 *
	 * @param $teamID
	 * @param $name
	 * @param $logos
	 * @return bool
	 */
	public function updateTeam($teamID, $name, $logos = array()){
		if(is_numeric($teamID) && ($teamID > 0) && ($name != '')){
			if(!is_array($logos)){
				$logos = array($logos);
			}
			$logos = array_unique(array_filter($logos));
			foreach($logos as $k => $logo){
				$dir = wp_upload_dir();
				$logos[$k] = array(
					'file' => $logo,
					'size' => reset(getimagesize(rtrim($dir['path'], '/') . '/' . strrchr($logo, '/')))
				);
			}

			$data = array(
				'name' => $name,
				'logo' => json_encode($logos)
			);

			$where = array(
				'id' => $teamID
			);

			return false !== $this->db->update(SPORT_LEAGUE_DB_PREFIX . "teams", $data, $where);
		}

		return false;
	}

	public function removeTeam($teamID){
		if(is_numeric($teamID) && ($teamID > 0)){
			return false !== $this->db->query(
				$this->db->prepare(
					"DELETE FROM
						" . SPORT_LEAGUE_DB_PREFIX . "teams
					WHERE
						id = %d
					LIMIT 1",
					$teamID
				)
			);
		}

		return false;
	}
}
?>