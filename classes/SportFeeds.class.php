<?php
/**
 * Author: lee
 * Date Created: 26/09/2012 16:20
 */

class SportFeeds extends RSSFeed{
	/**
	 * Outputs the HTTP header
	 */
	protected function outputHeader($type){
		// only set headers if they have not already been set
		if(!headers_sent()){
			switch(strtolower($type)){
				case 'rss':
					parent::outputHeader();
				break;
				case 'json':
					header("content-type: application/json; charset=utf-8");
				break;
			}
		}
	}

	/**
	 * Builds the different kinds of output and
	 * returns them as an array.
	 * Currently builds RSS(XML) and JSON.
	 *
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param array $items
	 * @param string $encoding
	 * @return array
	 */
	public function build($title, $url, $description, array $items, $encoding = 'UTF-8'){
		$this->output = array(
			'rss'	=> parent::build($title, $url, $description, $items, $encoding),
			'json'	=> json_encode(array(
							'title'			=> $title,
							'url'			=> $url,
							'description'	=> $description,
							'items'			=> $items
						))
		);

		return $this->output;
	}

	/**
	 * Builds the feed output for open fixtures
	 *
	 * @param $seasonID
	 * @param $teamID
	 * @param int $limit
	 * @return string
	 */
	public function buildFixtures($seasonID = -1, $teamID = -1, $limit = 10){
		// if a team ID has been specified, fetch the team
		// get the actual ID (this converts 'feature' to the featured team ID or returns the number, if numeric
		$teamID = Teams::getInstance()->getID($teamID);
		if(is_numeric($teamID) && ($teamID > 0) && !is_null($team = Teams::getInstance()->getTeam($teamID))){
			$team = htmlentities($team->getName());
		}else{
			$team = '';
		}

		// define the channel information
		$title = (($team != '') ? $team . ((substr($team, strlen($team)-1) == 's') ? "'" : "'s") . ' ' : '') . 'Fixtures';
		$description = 'The latest fixtures' . (($team != '') ? ' from ' . $team : '') . '.';

		// get the fixtures
		$items = array();
		if(count($fixtures = Fixtures::getInstance()->getOpenFixtures($seasonID, $teamID, $limit)) > 0){
			// loop through each fixture and grab the relevant information

			// sets whether to append the team logo to the feed or not
			$appendLogo = get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_fixtures');
			$font = get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_font', '');

			foreach($fixtures as $fixture){
				$team1 = $fixture->getTeam(1);
				$team2 = $fixture->getTeam(2);

				$logo = $team1->getLogo(-1, false);
				$logo = ($logo == '') ? $team2->getLogo(-1, false) : $logo;

				$items[] = array(
					'title' => $team1->getName() . ' v ' . $team2->getName() . ' (' . date('D, j M Y', strtotime($fixture->getDate())) . ')',
					'url' => sprintf(SPORT_LEAGUE_REPORT_URL, $fixture->getID()),
					'date' => date('Y-m-d H:i:s', strtotime($fixture->getStartDateTime())),
					'description' =>
						(($font != '') ? '<div style="font-family:' . $font . ';">' : '') .
							($appendLogo ? '<img src="' . $logo . '" alt="">' : '') .
							str_replace(']]>', ']]&gt;', apply_filters('the_content', $fixture->getPreMatchReport())) .
						(($font != '') ? '</div>' : ''),
					'thumb' => $logo
				);
			}
		}

		return $this->build($title, get_bloginfo('url'), $description, $items);
	}

	/**
	 * @param $seasonID
	 * @param $teamID
	 * @param int $limit
	 * @return array
	 */
	public function buildResults($seasonID = -1, $teamID = -1, $limit = 10){
		// if a team ID has been specified, fetch the team
		// get the actual ID (this converts 'feature' to the featured team ID or returns the number, if numeric
		$teamID = Teams::getInstance()->getID($teamID);
		if(is_numeric($teamID) && ($teamID > 0) && !is_null($team = Teams::getInstance()->getTeam($teamID))){
			$team = htmlentities($team->getName());
		}else{
			$team = '';
		}

		// define the channel information
		$title = (($team != '') ? $team . ((substr($team, strlen($team)-1) == 's') ? "'" : "'s") . ' ' : '') . 'Results';
		$description = 'The latest results' . (($team != '') ? ' from ' . $team : '') . '.';

		// get the fixtures
		$items = array();
		if(count($fixtures = Fixtures::getInstance()->getClosedFixtures($seasonID, $teamID, $limit)) > 0){
			// loop through each fixture and grab the relevant information

			// sets whether to append the team logo to the feed or not
			$appendLogo = get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_results');
			$font = get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_font', '');

			foreach($fixtures as $fixture){
				$team1 = $fixture->getTeam(1);
				$team2 = $fixture->getTeam(2);

				$logo = $team1->getLogo(-1, false);
				$logo = ($logo == '') ? $team2->getLogo(-1, false) : $logo;

				$items[] = array(
					'title' => $team1->getName() . ' ' . $fixture->getTeamScore(1) . ', ' . $team2->getName() . ' ' .$fixture->getTeamScore(2),
					'url' => sprintf(SPORT_LEAGUE_REPORT_URL, $fixture->getID()),
					'date' => date('Y-m-d H:i:s', strtotime($fixture->getStartDateTime())),
					'description' =>
						(($font != '') ? '<div style="font-family:' . $font . ';">' : '') .
							($appendLogo ? '<img src="' . $logo . '" alt="">' : '') .
							str_replace(']]>', ']]&gt;', apply_filters('the_content', $fixture->getPostMatchReport())) .
						(($font != '') ? '</div>' : ''),
					'thumb' =>$logo
				);
			}
		}

		return $this->build($title, get_bloginfo('url'), $description, $items);
	}

	/**
	 * Outputs the feed and any appropriate headers
	 *
	 * @param $type
	 */
	public function output($type){
		$output = $this->getOutput();

		$this->outputHeader($type);
		switch(strtolower($type)){
			case 'rss':
				echo isset($output[$type]) ? $output[$type] : '';
			break;
			case 'json':
				echo isset($output[$type]) ? $output[$type] : '';
			break;
			default:
				// invalid type specified
			break;
		}

		exit;
	}
}
?>