<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:52
 */

// check the sort order
$sort = array(
	'orderBy'	=> isset($_GET['orderby']) ? $_GET['orderby'] : '',
	'order'		=> isset($_GET['order']) ? $_GET['order'] : 'asc'
);

// check if we need to filter for a team
$seasonID = (isset($_GET['season']) && is_numeric($_GET['season']) && ($_GET['season'] > 0)) ? (int) $_GET['season'] : 'all';
$teamID = (isset($_GET['team']) && is_numeric($_GET['team']) && ($_GET['team'] > 0)) ? (int) $_GET['team'] : -1;
$tournamentID = (isset($_GET['tournament']) && is_numeric($_GET['tournament']) && ($_GET['tournament'] > 0)) ? (int) $_GET['tournament'] : -1;

// get fixture count
$fixtureCount = $fixtureClass->getFixtureCount($seasonID, $teamID, $tournamentID, true);

// create the pagination
$paginationClass = new Pagination();
$pagination = $paginationClass->limit((isset($_GET['l']) && is_numeric($_GET['l'])) ? $_GET['l'] : 15)->output($fixtureCount);

// get the fixtures
$fixtures = $fixtureClass->getFixtures($seasonID, $teamID, $tournamentID, true, $sort['orderBy'], $sort['order'], $paginationClass->limit(), $paginationClass->offset());

?>
<div id="<?php echo SPORT_LEAGUE_VAR_NAME; ?>" class="wrap">
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Fixtures'); ?> <a href="<?php echo $addURL; ?>" title="add a new fixture" class="add-new-h2">Add New</a></h2>

	<div class="tablenav">
		<div class="alignleft actions">
			<form action="<?PHP echo $currentURL; ?>" method="get">
				<input type="hidden" name="page" value="<?php echo SPORT_LEAGUE_QUERY_NAME; ?>">
				<select name="season">
					<option value="">View all seasons</option>
					<?php
					foreach($fixtureClass->getSeasons() as $season){
						echo '<option value="' . $season->id . '"';
						if(isset($_GET['season'])){
							if($season->id == $_GET['season']){
								echo ' selected';
							}
						}
						echo '>' . date('Y', strtotime($season->date_start)) . ' / ' . date('Y', strtotime($season->date_end)) . '</option>';
					}
					?>
				</select>

				<select name="team">
					<option value="">View all teams</option>
					<?php
					foreach(Teams::getInstance()->getTeams() as $team){
						echo '<option value="' . $team->getID() . '"';
						if(isset($_GET['team'])){
							if($team->getID() == $_GET['team']){
								echo ' selected';
							}
						}

						if($team->getID() == $featuredTeamID){
							echo ' class="featured"';
						}
						echo '>' . htmlentities($team->getName()) . '</option>';
					}
					?>
				</select>

				<select name="tournament">
					<option value="">View all tournaments</option>
					<?php
					foreach($fixtureClass->getTournaments() as $tournament){
						echo '<option value="' . $tournament->id . '"';
						if(isset($_GET['tournament'])){
							if($tournament->id == $_GET['tournament']){
								echo ' selected';
							}
						}
						echo '>' . htmlentities($tournament->name) . '</option>';
					}
					?>
				</select>

				<input type="submit" value="Filter" id="post-query-submit" class="button-secondary">
			</form>
		</div>

		<div class="tablenav-pages">
			<?php echo $pagination; ?>
		</div>
	</div>

	<table cellpadding="0" cellspacing="0" class="wp-list-table widefat">
		<thead>
			<tr>
				<th class="datetime column-title sortable <?php echo (($sort['orderBy'] == 'date') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'date', (($sort['orderBy'] == 'date') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Date &amp; KO</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="team home column-title sortable <?php echo (($sort['orderBy'] == 'home_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'home_team', (($sort['orderBy'] == 'home_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Home Team</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="score column-title">Score</th>
				<th class="team home column-title sortable <?php echo (($sort['orderBy'] == 'away_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'away_team', (($sort['orderBy'] == 'away_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Away Team</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="tournament column-title sortable <?php echo (($sort['orderBy'] == 'tournament') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'tournament', (($sort['orderBy'] == 'tournament') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Tournament</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="status column-title sortable <?php echo (($sort['orderBy'] == 'status') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'status', (($sort['orderBy'] == 'status') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Status</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
			</tr>
		</thead>

		<tbody>
			<?php
			if(count($fixtures) > 0){
				// fixtures exist - output them
				foreach($fixtures as $c => $fixture){
					$dateTime = strtotime($fixture->getStartDateTime());

					$team1 = $fixture->getTeam(1);
					$team2 = $fixture->getTeam(2);
			?><tr class="<?php echo ($c & 1) ? '' : 'alternate'; ?>">
				<td class="datetime">
					<strong>
						<a href="<?php echo sprintf($editURL, $fixture->getID()); ?>" title="edit fixture" class="row-title">
							<span class="date"><?php echo date('l jS F Y', $dateTime); ?></span>
							<span class="time">KO <?php
								$hours = date('g', $dateTime);
								$minutes = date('i', $dateTime);
								$amPm = date('a', $dateTime);
								if(($hours == '12') && ($minutes == 0) && ($amPm == 'am')){
									echo 'TBC';
								}else{
									echo $hours . (($minutes > 0) ? ':' . $minutes : '') . $amPm;
								}
							?></span>
						</a>
					</strong>

					<div class="row-actions">
						<span class="edit">
							<a href="<?php echo sprintf($editURL, $fixture->getID()); ?>" title="edit fixture">Edit</a> |
						</span>
						<span class="trash">
							<a href="<?php echo sprintf($deleteURL, $fixture->getID()); ?>" title="delete fixture" class="submitdelete">Trash</a>
						</span>
					</div>
				</td>
				<td class="team home">
					<?php if(!is_null($team1)){ ?>
					<a href="<?php echo $currentURL . '&amp;team=' . $team1->getID(); ?>" title="view all fixtures for this team"><?php echo $team1->getName(); ?></a>
					<?php }else{ ?>
					<span title="To Be Confirmed">TBC</span>
					<?php } ?>
				</td>
				<td class="score">
					<?php echo '<span class="home">' . $fixture->getTeamScore(1) . '</span>-<span class="away">' . $fixture->getTeamScore(2) . '</span>'; ?>
				</td>
				<td class="team away">
					<?php if(!is_null($team2)){ ?>
					<a href="<?php echo $currentURL . '&amp;team=' . $team2->getID(); ?>" title="view all fixtures for this team"><?php echo $team2->getName(); ?></a>
					<?php }else{ ?>
					<span title="To Be Confirmed">TBC</span>
					<?php } ?>
				</td>
				<td class="tournament"><?php echo $fixture->getTournament()->name . (($fixture->getRound() != '') ? ' ' . $fixture->getRound() : ''); ?></td>
				<td class="status"><?php echo $fixture->isOpen() ? 'open' : 'closed'; ?></td>
			</tr><?php
				}
			}else{
				// no fixtures
			?><tr>
				<td colspan="6" class="empty">No fixtures, why don't you <a href="<?php echo $addURL; ?>" title="add fixtures">add one</a>?</td>
			</tr><?php
			}
			?>
		</tbody>

		<tfoot>
			<tr>
				<th class="datetime column-title sortable <?php echo (($sort['orderBy'] == 'date') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'date', (($sort['orderBy'] == 'date') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Date &amp; KO</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="team home column-title sortable <?php echo (($sort['orderBy'] == 'home_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'home_team', (($sort['orderBy'] == 'home_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Home Team</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="score column-title">Score</th>
				<th class="team home column-title sortable <?php echo (($sort['orderBy'] == 'away_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'away_team', (($sort['orderBy'] == 'away_team') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Away Team</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="tournament column-title sortable <?php echo (($sort['orderBy'] == 'tournament') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'tournament', (($sort['orderBy'] == 'tournament') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Tournament</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="status column-title sortable <?php echo (($sort['orderBy'] == 'status') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'status', (($sort['orderBy'] == 'status') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Status</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
			</tr>
		</tfoot>
	</table>

	<div class="tablenav">
		<div class="tablenav-pages">
			<?php echo $pagination; ?>
		</div>
	</div>
</div>