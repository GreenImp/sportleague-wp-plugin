<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:54
 */

// get the action
$isAdd = isset($_GET['action']) && ($_GET['action'] == 'add');

if(!$isAdd){
	// we are editing - ensure that the ID has been defined
	if(!isset($_GET['id']) || !is_numeric($_GET['id']) || ($_GET['id'] <= 0) || is_null($team = $teamClass->getTeam($_GET['id']))){
		// no ID, ID is invalid or Team not found
		Message::add('error', 'The selected team could not be found');
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
			'field'	=> 'teamName',
			'label'	=> 'Name',
			'rules'	=> 'trim|required|is_unique[' . SPORT_LEAGUE_DB_PREFIX . 'teams.name' . (!$isAdd ? ',id,' . $team->getID() : '') . ']'
		),
		array(
			'field'	=> 'teamLogo',
			'label'	=> 'Small Logo',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'teamLogoLarge',
			'label'	=> 'Large Logo',
			'rules'	=> 'trim'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// no errors - continue

		// ensure that the logo url is local (if defined)
		$urlMatch = preg_quote(trim(str_replace(array('http://', 'https://', 'www.'), '', get_bloginfo('url')), '/'), '/');	// the domain name to match against

		if(
			isset($_POST['teamLogo']) && ($_POST['teamLogo'] != '') &&								// if teamLogo is set AND has a value AND
			!preg_match('/^(https?:\/\/)?([^\.]+\.)*' . $urlMatch . '\//', $_POST['teamLogo'])			// it isn't a full URL that matches the current domain
		){
			// team logo is invalid
			Message::add('error', 'The team logo must be a local URL, it cannot be to a file on another domain name');
		}else{
			$logos = array(
				isset($_POST['teamLogo']) ? $_POST['teamLogo'] : '',
				isset($_POST['teamLogoLarge']) ? $_POST['teamLogoLarge'] : ''
			);

			if($isAdd){
				// add the team
				$bolSuccess = $teamClass->addTeam($_POST['teamName'], $logos) > 0;
			}else{
				// update the team
				$bolSuccess = $teamClass->updateTeam($team->getID(), $_POST['teamName'], $logos);
			}

			if($bolSuccess){
				// add/update successful
				Message::add('updated', 'The team was ' . ($isAdd ? 'created' : 'updated'));
				wp_redirect($currentURL);
				exit;
			}else{
				// error adding/updating the fixture
				Message::add('error', 'There was a problem ' . ($isAdd ? 'adding' : 'updating') . ' the team, please try again');
			}
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
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Teams - ' . ($isAdd ? 'Add' : 'Edit')); ?></h2>

	<form action="" method="post" id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="postbox">
					<h3>Name</h3>

					<div class="inside">
						<input type="text" name="teamName" value="<?php
						if(isset($_POST['teamName'])){
							echo htmlentities($_POST['teamName']);
						}elseif(!$isAdd){
							echo htmlentities($team->getName());
						}
						?>" id="teamName">
					</div>
				</div>

				<div class="postbox">
					<h3>Logo</h3>

					<dl class="inside">
						<dt><label for="teamLogo">Small Logo</label></dt>
						<dd>
							<input type="text" name="teamLogo" value="<?php
							if(isset($_POST['teamLogo'])){
								echo htmlentities($_POST['teamLogo']);
							}elseif(!$isAdd){
								echo htmlentities($team->getLogo('small', false));
							}
							?>" size="36" readonly id="teamLogo" class="file">
							<input type="button" value="Upload Image" class="uploadBtn">

							<div class="imageBox"><?php
								if(!$isAdd){
									echo $team->getLogo('small');
								}
							?></div>
						</dd>

						<dt><label for="teamLogoLarge">Large Logo</label></dt>
						<dd>
							<input type="text" name="teamLogoLarge" value="<?php
							if(isset($_POST['teamLogoLarge'])){
								echo htmlentities($_POST['teamLogoLarge']);
							}elseif(!$isAdd){
								echo htmlentities($team->getLogo(0, false));
							}
							?>" size="36" readonly id="teamLogoLarge" class="file">
							<input type="button" value="Upload Image" class="uploadBtn">

							<div class="imageBox"><?php
								if(!$isAdd){
									echo $team->getLogo();
								}
							?></div>
						</dd>
					</dl>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h3>Save</h3>

					<div class="inside submitbox">
						<div id="major-publishing-actions">
							<?php if(!$isAdd){ ?>
							<div id="delete-action">
								<a href="<?php echo sprintf($deleteURL, $team->getID()); ?>" class="submitdelete deletion">Delete</a>
							</div>
							<?php } ?>

							<div id="publishing-action">
								<input type="submit" name="save" value="<?php echo $isAdd ? 'Add' : 'Update'; ?> Team" accesskey="p" id="publish" class="button-primary">
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>