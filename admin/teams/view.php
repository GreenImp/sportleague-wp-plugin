<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 10:10
 */

// check the sort order
$sort = array(
	'orderBy' => isset($_GET['orderby']) ? $_GET['orderby'] : '',
	'order' => isset($_GET['order']) ? $_GET['order'] : 'asc'
);

// get team count
$teamCount = $teamClass->getTeamCount();

// create the pagination
$paginationClass = new Pagination();
$pagination = $paginationClass->limit((isset($_GET['l']) && is_numeric($_GET['l'])) ? $_GET['l'] : 15)->output($teamCount);

$teams = $teamClass->getTeams('name', ($sort['orderBy'] == 'name') ? $sort['order'] : 'ASC', $paginationClass->limit(), $paginationClass->offset());
?>
<div id="<?php echo SPORT_LEAGUE_VAR_NAME; ?>" class="wrap">
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Teams'); ?> <a href="<?php echo $addURL; ?>" title="add a new team" class="add-new-h2">Add New</a></h2>

	<div class="tablenav">
		<div class="tablenav-pages">
			<?php echo $pagination; ?>
		</div>
	</div>

	<table cellpadding="0" cellspacing="0" class="wp-list-table widefat">
		<thead>
			<tr>
				<th width="32" class="logo column-title">Logo</th>
				<th class="name column-title sortable <?php echo (($sort['orderBy'] == 'name') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'name', (($sort['orderBy'] == 'name') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Name</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
			</tr>
		</thead>

		<tbody>
			<?php
			if(count($teams) > 0){
				foreach($teams as $c => $team){
			?><tr class="<?php echo ($c & 1) ? '' : 'alternate'; ?>">
				<td width="32" class="logo"><?php echo $team->getLogo(32); ?></td>
				<td class="name">
					<strong>
						<a href="<?php echo sprintf($editURL, $team->getID()); ?>" title="edit fixture" class="row-title">
							<?php echo $team->getName(); ?>
						</a>
					</strong>

					<div class="row-actions">
						<span class="edit">
							<a href="<?php echo sprintf($editURL, $team->getID()); ?>" title="edit fixture">Edit</a> |
						</span>
						<span class="trash">
							<a href="<?php echo sprintf($deleteURL, $team->getID()); ?>" title="delete fixture" class="submitdelete">Trash</a>
						</span>
					</div>
				</td>
			</tr>
			<?php
				}
			}else{
				// no teams
			?><tr>
				<td colspan="2" class="empty">No Teams, why don't you <a href="<?php echo $addURL; ?>" title="add team">add one</a>?</td>
			</tr><?php
			}
			?>
		</tbody>

		<tfoot>
			<tr>
				<th width="32" class="logo column-title">Logo</th>
				<th class="name column-title sortable <?php echo (($sort['orderBy'] == 'name') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'name', (($sort['orderBy'] == 'name') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Name</span>
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