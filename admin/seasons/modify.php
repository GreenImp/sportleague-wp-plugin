<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:54
 */

// get the action
$isAdd = isset($_GET['action']) && ($_GET['action'] == 'add');

if(!$isAdd){
	// we are editing - ensure that the season ID has been defined
	if(!isset($_GET['id']) || !is_numeric($_GET['id']) || ($_GET['id'] <= 0) || is_null($season = $fixtureClass->getSeason($_GET['id']))){
		// no ID, ID is invalid or Season not found
		Message::add('error', 'The selected season could not be found');
		wp_redirect($currentURL);
		exit;
	}
}


// check if the form has been submitted
if(isset($_POST['save'])){
	// form has been submitted - validate
	$postErrors = array();

	// list of validation rules
	$rules = array(
		array(
			'field'	=> 'seasonStartDate',
			'label'	=> 'Start Date',
			'rules'	=> 'required|is_date[YYYY-MM-DD]'
		),
		array(
			'field'	=> 'seasonEndDate',
			'label'	=> 'End Date',
			'rules'	=> 'required|is_date[YYYY-MM-DD]'
		),
		array(
			'field'	=> 'seasonActive',
			'label'	=> 'Active',
			'rules'	=> 'is_natural_no_zero|less_than[2]'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// no errors - continue

		$reportPre = isset($_POST['fixtureReportPre']) ? stripslashes($_POST['fixtureReportPre']) : '';
		$reportPost = isset($_POST['fixtureReportPost']) ? stripslashes($_POST['fixtureReportPost']) : '';

		if($isAdd){
			// add the fixture
			$bolSuccess = $fixtureClass->addSeason($_POST['seasonStartDate'], $_POST['seasonEndDate']) > 0;
		}else{
			// update the fixture
			$bolSuccess = $fixtureClass->updateSeason($season->id, $_POST['seasonStartDate'], $_POST['seasonEndDate'], isset($_POST['seasonActive']) ? !!$_POST['seasonActive'] : false);
		}

		if($bolSuccess){
			// add/update successful
			Message::add('updated', 'The season was ' . ($isAdd ? 'created' : 'updated'));
			wp_redirect($currentURL);
			exit;
		}else{
			// error adding/updating the fixture
			Message::add('error', 'There was a problem ' . ($isAdd ? 'adding' : 'updating') . ' the seaeon, please try again');
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
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Seasons - ' . ($isAdd ? 'Add' : 'Edit')); ?></h2>

	<form action="" method="post" id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<h3>Date</h3>

					<dl class="inside">
						<dt><label for="seasonStartDate">Start Date</label></dt>
						<dd><input type="date" name="seasonStartDate" value="<?php
						if(isset($_POST['seasonStartDate'])){
							echo htmlentities($_POST['seasonStartDate']);
						}elseif(!$isAdd){
							echo htmlentities($season->date_start);
						}
						?>" id="seasonStartDate" class="datepicker"></dd>

						<dt><label for="seasonEndDate">End Date</label></dt>
						<dd><input type="date" name="seasonEndDate" value="<?php
						if(isset($_POST['seasonEndDate'])){
							echo htmlentities($_POST['seasonEndDate']);
						}elseif(!$isAdd){
							echo htmlentities($season->date_end);
						}
						?>" id="seasonEndDate" class="datepicker"></dd>
					</dl>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h3>Save</h3>

					<div class="inside submitbox">
						<?php if(!$isAdd){ ?>
						<div id="misc-publishing-actions">
							<div class="misc-pub-section">
								<label class="selectit" for="seasonActive">
									Active?
									<input type="checkbox" name="seasonActive" value="1"<?php
									if(isset($_POST['seasonActive']) && ($_POST['seasonActive'] == true)){
										echo ' checked="checked"';
									}elseif(!$isAdd && $season->active){
										echo ' checked="checked"';
									}
									?> id="seasonActive">
								</label>
							</div>
						</div>
						<?php } ?>

						<div id="major-publishing-actions">
							<?php if(!$isAdd){ ?>
							<div id="delete-action">
								<a href="<?php echo sprintf($deleteURL, $season->id); ?>" class="submitdelete deletion">Delete</a>
							</div>
							<?php } ?>

							<div id="publishing-action">
								<input type="submit" name="save" value="<?php echo $isAdd ? 'Add' : 'Update'; ?> Season" accesskey="p" id="publish" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>