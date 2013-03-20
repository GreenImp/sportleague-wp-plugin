<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:52
 */

// check the sort order
$sort = array(
	'orderBy' => isset($_GET['orderby']) ? $_GET['orderby'] : '',
	'order' => isset($_GET['order']) ? $_GET['order'] : 'asc'
);
?>
<div id="<?php echo SPORT_LEAGUE_VAR_NAME; ?>" class="wrap">
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Seasons'); ?> <a href="<?php echo $addURL; ?>" title="add a new season" class="add-new-h2">Add New</a></h2>

	<table cellpadding="0" cellspacing="0" class="wp-list-table widefat">
		<thead>
			<tr>
				<th class="startDate column-title sortable <?php echo (($sort['orderBy'] == 'startDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'startDate', (($sort['orderBy'] == 'startDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Start Date</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="endDate column-title sortable <?php echo (($sort['orderBy'] == 'endDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'endDate', (($sort['orderBy'] == 'endDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>End Date</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="status column-title">Status</th>
			</tr>
		</thead>

		<tbody>
			<?php
			if(count($seasons = $fixtureClass->getSeasons($sort['orderBy'], $sort['order'])) > 0){
				// seasons exist - output them
				foreach($seasons as $c => $season){
			?><tr class="<?php echo ($c & 1) ? '' : 'alternate'; ?>">
				<td class="startDate">
					<strong>
						<a href="<?php echo sprintf($editURL, $season->id); ?>" title="edit season" class="row-title">
							<?php echo date('Y-m-d', strtotime($season->date_start)); ?>
						</a>
					</strong>

					<div class="row-actions">
						<span class="edit">
							<a href="<?php echo sprintf($editURL, $season->id); ?>" title="edit season">Edit</a> |
						</span>
						<span class="trash">
							<a href="<?php echo sprintf($deleteURL, $season->id); ?>" title="delete season" class="submitdelete">Trash</a>
						</span>
					</div>
				</td>
				<td class="endDate">
					<?php echo date('Y-m-d', strtotime($season->date_end)); ?>
				</td>
				<td class="status"><?php echo $season->active ? 'active' : 'in-active'; ?></td>
			</tr><?php
				}
			}else{
				// no seasons
			?><tr>
				<td colspan="2" class="empty">No Seasons, why don't you <a href="<?php echo $addURL; ?>" title="add season">add one</a>?</td>
			</tr><?php
			}
			?>
		</tbody>

		<tfoot>
			<tr>
				<th class="startDate column-title sortable <?php echo (($sort['orderBy'] == 'startDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'startDate', (($sort['orderBy'] == 'startDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>Start Date</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="endDate column-title sortable <?php echo (($sort['orderBy'] == 'endDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'; ?>">
					<a href="<?php echo sprintf($sortURL, 'endDate', (($sort['orderBy'] == 'endDate') && ($sort['order'] == 'desc')) ? 'asc' : 'desc'); ?>">
						<span>End Date</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th class="status column-title">Status</th>
			</tr>
		</tfoot>
	</table>
</div>