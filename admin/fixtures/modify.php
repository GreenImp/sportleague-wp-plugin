<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:54
 */

// get the action
$isAdd = isset($_GET['action']) && ($_GET['action'] == 'add');

if(!$isAdd){
	// we are editing - ensure that the fixture ID has been defined
	if(!isset($_GET['id']) || !is_numeric($_GET['id']) || ($_GET['id'] <= 0) || is_null($fixture = $fixtureClass->getFixture($_GET['id']))){
		// no ID, ID is invalid or Fixture not found
		Message::add('error', 'The selected fixture could not be found');
		wp_redirect($currentURL);
		exit;
	}
}


$seasonList = $fixtureClass->getSeasons();
$teamList = Teams::getInstance()->getTeams();
$tournamentList = $fixtureClass->getTournaments();
$roundList = $fixtureClass->getRounds();


/**
 * Validates the team selections
 *
 * @param $val
 * @return bool
 */
function validate_team($val){
	$validator = FormValidation::getInstance();

	if(($val == '') || ($validator->is_natural_no_zero($val) && $validator->is_in($val, SPORT_LEAGUE_DB_PREFIX . 'teams.id'))){
		// team ID is okay or not defined
		return true;
	}

	$validator->set_message('validate_team', 'The %s field should be a valid team');
	return false;
}


// check if the form has been submitted
if(isset($_POST['save'])){
	// form has been submitted - validate
	$postErrors = array();

	// list of validation rules
	$rules = array(
		array(
			'field'	=> 'fixtureDate',
			'label'	=> 'Date',
			'rules'	=> 'required|is_date[YYYY-MM-DD]'
		),
		array(
			'field'	=> 'fixtureTimeStart',
			'label'	=> 'Start Time',
			'rules'	=> 'is_time_24'
		),
		array(
			'field'	=> 'fixtureTimeEnd',
			'label'	=> 'End Time',
			'rules'	=> 'is_time_24'
		),
		array(
			'field'	=> 'fixtureSeason',
			'label'	=> 'Season',
			'rules'	=> 'required|is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'seasons.id]'
		),
		array(
			'field'	=> 'fixtureTournament',
			'label'	=> 'Tournament',
			'rules'	=> 'is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'tournaments.id]'
		),
		array(
			'field'	=> 'fixtureTournamentNew',
			'label'	=> 'New Tournament',
			'rules'	=> 'trim|is_unique[' . SPORT_LEAGUE_DB_PREFIX . 'tournaments.name]'
		),
		array(
			'field'	=> 'fixtureRound',
			'label'	=> 'Round',
			'rules'	=> 'is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'rounds.id]'
		),
		array(
			'field'	=> 'fixtureRoundNew',
			'label'	=> 'Custom Round',
			'rules'	=> 'trim|is_unique[' . SPORT_LEAGUE_DB_PREFIX . 'rounds.name]'
		),
		array(
			'field'	=> 'fixtureTeamHome',
			'label'	=> 'Home Team',
			//'rules'	=> 'required|is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'teams.id]'
			'rules'	=> 'callback_validate_team'
		),
		array(
			'field'	=> 'fixtureTeamAway',
			'label'	=> 'Away Team',
			//'rules'	=> 'required|is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'teams.id]'
			'rules'	=> 'callback_validate_team'
		),
		array(
			'field'	=> 'fixtureScoreHome',
			'label'	=> 'Home Team Score',
			'rules'	=> 'is_natural|less_than[100]'
		),
		array(
			'field'	=> 'fixtureScoreAway',
			'label'	=> 'Away Team Score',
			'rules'	=> 'is_natural|less_than[100]'
		),
		array(
			'field'	=> 'fixtureTicketsURL',
			'label'	=> 'Ticket URL',
			'rules'	=> 'trim|prep_url'
		),
		array(
			'field'	=> 'fixtureReportPre',
			'label'	=> 'Pre-Match Report',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'fixtureReportPost',
			'label'	=> 'Post-Match Report',
			'rules'	=> 'trim'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// no errors - continue

		$tournamentID = -1;
		if(isset($_POST['fixtureTournamentNew']) && ($_POST['fixtureTournamentNew'] != '')){
			// the fixture has been placed in a new tournament - create it
			$tournamentID = $fixtureClass->addTournament($_POST['fixtureTournamentNew']);
		}else{
			$tournamentID = isset($_POST['fixtureTournament']) ? $_POST['fixtureTournament'] : -1;
		}

		// only continue if the tournament ID was successfully set
		if($tournamentID > 0){
			if(isset($_POST['fixtureRoundNew']) && ($_POST['fixtureRoundNew'] != '')){
				$round = $_POST['fixtureRoundNew'];
			}elseif($_POST['fixtureRound'] && is_numeric($_POST['fixtureRound'])){
				if(!is_null($round = $fixtureClass->getRound($_POST['fixtureRound']))){
					// fixture found
					$round = $round->name;
				}else{
					// round wasn't found
					$round = '';
				}
			}

			$timeStart = isset($_POST['fixtureTimeStart']) ? $_POST['fixtureTimeStart'] : '00:00';
			$timeEnd = isset($_POST['fixtureTimeEnd']) ? $_POST['fixtureTimeEnd'] : '00:00';

			$reportPre = isset($_POST['fixtureReportPre']) ? stripslashes($_POST['fixtureReportPre']) : '';
			$reportPost = isset($_POST['fixtureReportPost']) ? stripslashes($_POST['fixtureReportPost']) : '';

			if($isAdd){
				// add the fixture
				$bolSuccess = $fixtureClass->addFixture(
					$_POST['fixtureSeason'],
					$tournamentID,
					$round,
					(isset($_POST['fixtureTeamHome']) && is_numeric($_POST['fixtureTeamHome'])) ? $_POST['fixtureTeamHome'] : 0,
					(isset($_POST['fixtureTeamAway']) && is_numeric($_POST['fixtureTeamAway'])) ? $_POST['fixtureTeamAway'] : 0,
					(isset($_POST['fixtureScoreHome']) && is_numeric($_POST['fixtureScoreHome'])) ? $_POST['fixtureScoreHome'] : 0,
					(isset($_POST['fixtureScoreAway']) && is_numeric($_POST['fixtureScoreAway'])) ? $_POST['fixtureScoreAway'] : 0,
					$_POST['fixtureDate'] . ' ' . $_POST['fixtureTimeStart'],
					$_POST['fixtureDate'] . ' ' . $_POST['fixtureTimeEnd'],
					isset($_POST['fixtureTBC']) && ($_POST['fixtureTBC'] == true),
					isset($_POST['fixtureTicketsURL']) ? $_POST['fixtureTicketsURL'] : '',
					$reportPre,
					$reportPost,
					isset($_POST['fixtureClose']) && ($_POST['fixtureClose'] == true)
				) > 0;
			}else{
				// update the fixture
				$bolSuccess = $fixtureClass->updateFixture(
					$fixture->getID(),
					$_POST['fixtureSeason'],
					$tournamentID,
					$round,
					isset($_POST['fixtureTeamHome']) ? $_POST['fixtureTeamHome'] : 0,
					isset($_POST['fixtureTeamAway']) ? $_POST['fixtureTeamAway'] : 0,
					(isset($_POST['fixtureScoreHome']) && is_numeric($_POST['fixtureScoreHome'])) ? $_POST['fixtureScoreHome'] : 0,
					(isset($_POST['fixtureScoreAway']) && is_numeric($_POST['fixtureScoreAway'])) ? $_POST['fixtureScoreAway'] : 0,
					$_POST['fixtureDate'] . ' ' . $_POST['fixtureTimeStart'],
					$_POST['fixtureDate'] . ' ' . $_POST['fixtureTimeEnd'],
					isset($_POST['fixtureTBC']) && ($_POST['fixtureTBC'] == true),
					isset($_POST['fixtureTicketsURL']) ? $_POST['fixtureTicketsURL'] : '',
					$reportPre,
					$reportPost,
					isset($_POST['fixtureClose']) && ($_POST['fixtureClose'] == true)
				);
			}

			if($bolSuccess){
				// add/update successful
				Message::add('updated', 'The fixture was ' . ($isAdd ? 'created' : 'updated'));
				wp_redirect($currentURL);
				exit;
			}else{
				// error adding/updating the fixture
				Message::add('error', 'There was a problem ' . ($isAdd ? 'adding' : 'updating') . ' the fixture, please try again');
			}
		}else{
			// error creating the fixture
			Message::add('error', 'There was a problem creating the tournament, please try again');
		}
	}elseif(count($errors = FormValidation::getErrors()) > 0){
		// errors exist - output them to the user
		// loop through each error and add it to the list
		foreach($errors as $error){
			Message::add('error', $error);
		}
	}
}


// output any messages
Message::show();
?>
<div id="<?php echo SPORT_LEAGUE_VAR_NAME; ?>" class="wrap">
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Fixtures - ' . ($isAdd ? 'Add' : 'Edit')); ?></h2>

	<form action="" method="post" id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<h3>Date &amp; Times</h3>

					<dl class="inside">
						<dt><label for="fixtureDate">Date*</label></dt>
						<dd><input type="date" name="fixtureDate" value="<?php
						if(isset($_POST['fixtureDate'])){
							echo htmlentities($_POST['fixtureDate']);
						}elseif(!$isAdd){
							echo htmlentities($fixture->getDate());
						}
						?>" id="fixtureDate" class="datepicker"></dd>


						<dt><label for="fixtureTimeStart">Start Time</label></dt>
						<dd><input type="text" name="fixtureTimeStart" value="<?php
						if(isset($_POST['fixtureTimeStart'])){
							echo htmlentities($_POST['fixtureTimeStart']);
						}elseif(!$isAdd){
							$time = $fixture->getStartTime();
							echo ($time == '00:00') ? '' : htmlentities($time);
						}
						?>" id="fixtureTimeStart"></dd>


						<dt><label for="fixtureTimeEnd">End Time</label></dt>
						<dd><input type="text" name="fixtureTimeEnd" value="<?php
						if(isset($_POST['fixtureTimeEnd'])){
							echo htmlentities($_POST['fixtureTimeEnd']);
						}elseif(!$isAdd){
							$time = $fixture->getEndTime();
							echo ($time == '00:00') ? '' : htmlentities($time);
						}
						?>" id="fixtureTimeEnd"></dd>
					</dl>

					<dl class="inside">
						<dt><label for="fixtureSeason">Season*</label></dt>
						<dd>
							<select name="fixtureSeason" id="fixtureSeason">
								<option value="">Select...</option>
								<?php
								foreach($seasonList as $season){
									echo '<option value="' . $season->id . '"';
									if(isset($_POST['fixtureSeason'])){
										if($season->id == $_POST['fixtureSeason']){
											echo ' selected';
										}
									}elseif(!$isAdd){
										if($season->id == $fixture->getSeasonID()){
											echo ' selected';
										}
									}
									echo '>' . date('Y', strtotime($season->date_start)) . ' / ' . date('Y', strtotime($season->date_end)) . '</option>';
								}
								?>
							</select>
						</dd>
					</dl>

					<dl class="inside">
						<dt><label for="fixtureTBC">TBC</label></dt>
						<dd>
							<input type="checkbox" name="fixtureTBC" value="1" id="fixtureTBC"<?php
							if(
								(isset($_POST['fixtureTBC']) && ($_POST['fixtureClose'] == true)) ||
								(!$isAdd && $fixture->isTBC())
							){
								echo ' checked';
							}
							?>>
						</dd>
					</dl>
				</div>

				<div class="postbox">
					<h3>Tournament</h3>

					<dl class="inside">
						<dt><label for="fixtureTournament">Existing Tournament</label></dt>
						<dd><select name="fixtureTournament" id="fixtureTournament">
							<option value="">Select...</option>
							<?php
							foreach($tournamentList as $tournament){
								echo '<option value="' . $tournament->id . '"';
								if(isset($_POST['fixtureTournament'])){
									if($tournament->id == $_POST['fixtureTournament']){
										echo ' selected';
									}
								}elseif(!$isAdd){
									if($tournament->id == $fixture->getTournamentID()){
										echo ' selected';
									}
								}
								echo '>' . htmlentities($tournament->name) . '</option>';
							}
							?>
						</select></dd>

						<dt><label for="fixtureTournamentNew">New Tournament</label></dt>
						<dd><input type="text" name="fixtureTournamentNew" value="" placeholder="Enter Name..." id="fixtureTournamentNew"></dd>
					</dl>

					<dl class="inside">
						<dt><label for="fixtureRound">Round</label></dt>
						<dd>
							<select name="fixtureRound" id="fixtureRound">
								<option value="">Select...</option>
								<?php
								$roundFound = false;
								foreach($roundList as $round){
									echo '<option value="' . $round->id . '"';
									if(isset($_POST['fixtureRound'])){
										if($round->id == $_POST['fixtureRound']){
											echo ' selected';
											$roundFound = true;
										}
									}elseif(!$isAdd){
										if($round->name == $fixture->getRound()){
											echo ' selected';
											$roundFound = true;
										}
									}
									echo '>' . htmlentities($round->name) . '</option>';
								}
								?>
							</select>
						</dd>

						<dt><label for="fixtureRoundNew">Custom Round</label></dt>
						<dd><input type="text" name="fixtureRoundNew" value="<?php
							if(!$roundFound){
								echo htmlentities(isset($_POST['fixtureRoundNew']) ? $_POST['fixtureRoundNew'] : (!$isAdd ? $fixture->getRound() : ''));
							}
						?>" placeholder="Enter Name..." id="fixtureRoundNew"></dd>
					</dl>
				</div>

				<div class="postbox">
					<h3>Match Details</h3>

					<dl class="inside">
						<dt><label for="fixtureTeamHome">Home Team</label></dt>
						<dd>
							<select name="fixtureTeamHome" id="fixtureTeamHome">
								<option value="">Select...</option>
								<option value="" class="blank">N/A</option>
								<?php
								foreach($teamList as $team){
									echo '<option value="' . $team->getID() . '"';
									if(isset($_POST['fixtureTeamHome'])){
										if($team->getID() == $_POST['fixtureTeamHome']){
											echo ' selected';
										}
									}elseif(!$isAdd){
										if($team->getID() == $fixture->getTeam(1)->getID()){
											echo ' selected';
										}
									}

									if($team->getID() == $featuredTeamID){
										echo ' class="featured highlight"';
									}
									echo '>' . htmlentities($team->getName()) . '</option>';
								}
								?>
							</select>
						</dd>

						<dt><label for="fixtureScoreHome">Home Score</label></dt>
						<dd>
							<input type="number" name="fixtureScoreHome" value="<?php
							if(isset($_POST['fixtureScoreHome'])){
								echo htmlentities($_POST['fixtureScoreHome']);
							}elseif(!$isAdd){
								echo htmlentities($fixture->getTeamScore(1));
							}
							?>" min="0" max="999" id="fixtureScoreHome">
						</dd>

						<dt><label for="fixtureTeamAway">Away Team</label></dt>
						<dd>
							<select name="fixtureTeamAway" id="fixtureTeamAway">
								<option value="">Select...</option>
								<option value="" class="blank">N/A</option>
								<?php
								foreach($teamList as $team){
									echo '<option value="' . $team->getID() . '"';
									if(isset($_POST['fixtureTeamAway'])){
										if($team->getID() == $_POST['fixtureTeamAway']){
											echo ' selected';
										}
									}elseif(!$isAdd){
										if($team->getID() == $fixture->getTeam(2)->getID()){
											echo ' selected';
										}
									}

									if($team->getID() == $featuredTeamID){
										echo ' class="featured highlight"';
									}
									echo '>' . htmlentities($team->getName()) . '</option>';
								}
								?>
							</select>
						</dd>

						<dt><label for="fixtureScoreAway">Away Score</label></dt>
						<dd>
							<input type="number" name="fixtureScoreAway" value="<?php
							if(isset($_POST['fixtureScoreAway'])){
								echo htmlentities($_POST['fixtureScoreAway']);
							}elseif(!$isAdd){
								echo htmlentities($fixture->getTeamScore(2));
							}
							?>" min="0" max="999" id="fixtureScoreAway">
						</dd>
					</dl>
				</div>

				<div class="postbox">
					<h3>Tickets</h3>

					<dl class="inside">
						<dt><label for="fixtureTicketsURL">Tickets URL</label></dt>
						<dd>
							<input type="text" name="fixtureTicketsURL" value="<?php
							if(isset($_POST['fixtureTicketsURL'])){
								echo htmlentities($_POST['fixtureTicketsURL']);
							}elseif(!$isAdd){
								echo htmlentities($fixture->getTicketURL());
							}
							?>" size="50" id="fixtureTicketsURL">
							<p class="description">(optional) Enter a URL where people can purchase tickets for the fixture</p>
						</dd>
					</dl>
				</div>

				<div class="postbox">
					<h3>Pre-Match Report</h3>

					<div class="inside">
						<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
							<?php wp_editor(isset($_POST['fixtureReportPre']) ? $_POST['fixtureReportPre'] : (!$isAdd ? $fixture->getPreMatchReport() : ''), 'fixtureReportPre'); ?>
						</div>
					</div>
				</div>

				<div class="postbox">
					<h3>Post-Match Report</h3>

					<div class="inside">
						<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
							<?php wp_editor(isset($_POST['fixtureReportPost']) ? $_POST['fixtureReportPost'] : (!$isAdd ? $fixture->getPostMatchReport() : ''), 'fixtureReportPost'); ?>
						</div>
					</div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h3>Save</h3>

					<div class="inside submitbox">
						<div id="misc-publishing-actions">
							<div class="misc-pub-section">
								<label class="selectit" for="fixtureClose">
									<input type="checkbox" name="fixtureClose" value="1"<?php
									if(isset($_POST['fixtureClose']) && ($_POST['fixtureClose'] == true)){
										echo ' checked="checked"';
									}elseif(!$isAdd && !$fixture->isOpen()){
										echo ' checked="checked"';
									}
									?> id="fixtureClose"> Close Fixture
								</label>
							</div>
						</div>

						<div id="major-publishing-actions">
							<?php if(!$isAdd){ ?>
							<div id="delete-action">
								<a href="<?php echo sprintf($deleteURL, $fixture->getID()); ?>" class="submitdelete deletion">Delete</a>
							</div>
							<?php } ?>

							<div id="publishing-action">
								<input type="submit" name="save" value="<?php echo $isAdd ? 'Add' : 'Update'; ?> Fixture" accesskey="p" id="publish" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>