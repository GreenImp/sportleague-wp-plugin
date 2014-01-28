<?php
/**
 * Author: lee
 * Date Created: 06/09/2012 16:07
 */

// get the Fixtures class
$teamClass = Teams::getInstance();

$featuredTeamID = $teamClass->getFeaturedID();

$feedURLs = array(
	'fixtures'	=> rtrim(get_bloginfo('url'), '/') . '/' . SPORT_LEAGUE_QUERY_NAME . '/feed/%s-fixtures',
	'results'	=> rtrim(get_bloginfo('url'), '/') . '/' . SPORT_LEAGUE_QUERY_NAME . '/feed/%s-results'
);

if(isset($_POST['submit'])){
	// form has been submitted - validate

	$rules = array(
		array(
			'field'	=> 'featuredTeam',
			'label'	=> 'Featured Team',
			'rules'	=> 'is_natural_no_zero|is_in[' . SPORT_LEAGUE_DB_PREFIX . 'teams.id]'
		),

		array(
			'field'	=> 'feedsPublish',
			'label'	=> 'Publish Feeds',
			'rules'	=> ''
		),
		array(
			'field'	=> 'feedsFont',
			'label'	=> 'Feed Font',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'feedsTeamLogos[]',
			'label'	=> 'Logos in Feed',
			'rules'	=> ''
		),

		array(
			'field'	=> 'feedsRSS',
			'label'	=> 'Feeds - RSS',
			'rules'	=> ''
		),
		array(
			'field'	=> 'feedsJSON',
			'label'	=> 'Feeds - RSS',
			'rules'	=> ''
		),

		array(
			'field'	=> 'feedsLimit',
			'label'	=> 'Feed Items Limit',
			'rules'	=> 'trim|is_natural'
		),

		array(
			'field'	=> 'feedsPagePre',
			'label'	=> 'Feeds Page Before',
			'rules'	=> 'trim|is_natural_no_zero'
		),
		array(
			'field'	=> 'feedsPagePost',
			'label'	=> 'Feeds Page After',
			'rules'	=> 'trim|is_natural_no_zero'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// form submitted successfully - update the options

		// set the featured team
		update_option(SPORT_LEAGUE_VAR_NAME . '_featured_team', $_POST['featuredTeam']);

		// define the allowed feed types
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish', isset($_POST['feedsPublish']) && ($_POST['feedsPublish'] == true));

		// define the feed font
		if(isset($_POST['feedsFont']) && ($_POST['feedsFont'] != '')){
			$_POST['feedsFont'] = explode(',', $_POST['feedsFont']);
			foreach($_POST['feedsFont'] as $k => $font){
				$font = trim(str_replace('"', '', $font));
				$font = (false !== strpos($font, ' ')) ? '"' . $font . '"' : $font;
				$_POST['feedsFont'][$k] = $font;
			}
			$font = implode(',', $_POST['feedsFont']);
		}else{
			$font = '';
		}
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_font', $font);

		// specify whether to include team logos in the RSS feed description or not
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_fixtures', isset($_POST['feedsTeamLogos']) && in_array('fixtures', $_POST['feedsTeamLogos']));
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_results', isset($_POST['feedsTeamLogos']) && in_array('results', $_POST['feedsTeamLogos']));

		// specify the feed limit
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_limit', isset($_POST['feedsLimit']) ? $_POST['feedsLimit'] : 10);
		// rss
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_rss', isset($_POST['feedsRSS']));
		// json
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_json', isset($_POST['feedsJSON']));

		// Feed pages pre
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_page_pre', isset($_POST['feedsPagePre']) ? $_POST['feedsPagePre'] : '');
		// Feed pages post
		update_option(SPORT_LEAGUE_VAR_NAME . '_feeds_page_post', isset($_POST['feedsPagePost']) ? $_POST['feedsPagePost'] : '');

		// update successful
		Message::add('updated', 'The settings have been updated');
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
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php echo __(SPORT_LEAGUE_NAME . ' Settings'); ?></h2>

	<form action="" method="post">
		<table class="form-table">
			<tr>
				<th>
					<label for="settingFeaturedTeam">Featured Team</label>
				</th>

				<td>
					<select name="featuredTeam" id="settingFeaturedTeam">
						<option value="" class="blank">None</option>
						<?php foreach($teamClass->getTeams() as $team){ ?>
						<option value="<?php echo $team->getID(); ?>"<?php
						if(isset($_POST['featuredTeam'])){
							if($team->getID() == $_POST['featuredTeam']){
								echo ' selected class="featured highlight"';
							}
						}elseif($team->getID() == $featuredTeamID){
							echo ' selected class="featured highlight"';
						}
						?>><?php echo $team->getName(); ?></option>
						<?php } ?>
					</select>

					<p class="description">This is the main team used for filtering widget results by 'feature'. Useful if you only want to display fixtures that a certain team has played in.</p>
				</td>
			</tr>

			<tr>
				<th>Publish Feeds</th>

				<td>
					<label for="settingFeedsPublishYes">
						<input type="radio" name="feedsPublish" value="1" id="settingFeedsPublishYes"<?php
						echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish') ? ' checked="checked"' : '';
						?>>
						Yes
					</label>

					<label for="settingFeedsPublishNo">
						<input type="radio" name="feedsPublish" value="0" id="settingFeedsPublishNo"<?php
						echo !get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_publish') ? ' checked="checked"' : '';
						?>>
						No
					</label>

					<p class="description">
						If 'Yes' then the available feed types, from below, will be published on the front-end, for people to see. This currently only includes 'RSS'.<br>
						If you want the feeds to be private (only accessible by going directly to their URL), select 'No'.
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="settingFeedsFont">Feed Font</label>
				</th>

				<td>
					<input type="text" name="feedsFont" value="<?php echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_font', ''); ?>" id="settingFeedsFont">

					<p class="description">
						If you require the <strong>RSS</strong> feed content have have a particular font applied, then you can enter it here.
					</p>
					<p class="description">
						This is only usefull in cases where you are outputting the feed but, for some reason, cannot formt the text when outputting.<br>
						An example of this may be within an IPhone app (although setting it inside the app is preferable).
					</p>
					<p class="description">
						<strong>This setting is not advised, as it is a 'bad' way of doing things, but is here for situations that absolutely require it.</strong>
					</p>
				</td>
			</tr>

			<tr>
				<th>Team Logos in RSS Feed?</th>

				<td>
					<label for="settingFeedsTeamLogos_fixtures">
						<input type="checkbox" name="feedsTeamLogos[]" value="fixtures" id="settingFeedsTeamLogos_fixtures"<?php
						echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_fixtures') ? ' checked="checked"' : '';
						?>>
						Fixtures
					</label>

					<label for="settingFeedsTeamLogos_results">
						<input type="checkbox" name="feedsTeamLogos[]" value="results" id="settingFeedsTeamLogos_results"<?php
						echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_team_logos_results') ? ' checked="checked"' : '';
						?>>
						Results
					</label>

					<p class="description">
						Some RSS parsers won't pick up the Team logos in the RSS feeds. Instead they use the first image found in the 'description' (content)<br>
						Selecting 'Yes' here will add the home team's logo to the beginning of the 'description' in the RSS feed.
					</p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="settingFeedsLimit">Feed Items Limit</label>
				</th>

				<td>
					<input type="text" name="feedsLimit" value="<?php echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_limit', 10); ?>" id="settingFeedsLimit">

					<p class="description">
						This is the maximum amount of results to display in the feeds (0 = no limit)
					</p>
				</td>
			</tr>

			<tr>
				<th>Allowed Feeds</th>

				<td>
					<div>
						<label for="settingFeedsRss">
							<input type="checkbox" name="feedsRSS" id="settingFeedsRss"<?php
							echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_rss') ? ' checked="checked"' : '';
							?>>
							RSS
						</label>
					</div>

					<div>
						<label for="settingFeedsJSON">
							<input type="checkbox" name="feedsJSON" id="settingFeedsJSON"<?php
							echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_json') ? ' checked="checked"' : '';
							?>>
							JSON
						</label>
					</div>

					<p class="description">Choose whether the plugin can output different feeds. The most typical is RSS.</p>

					<div class="feedInfo">
						<strong>Feed URLs:</strong>

						<p class="description">
							These URLs can be used to link directly to the feeds, just replace the <span class="highlight">[highlighted]</span> section with the correct file format.<br />
							If in doubt, use 'rss'.
						</p>

						<dl>
							<dt>Fixtures (upcoming matches)</dt>
							<dd>
								<?php echo sprintf($feedURLs['fixtures'], '<span class="highlight">[rss|json]</span>'); ?>

								<a href="<?php echo sprintf($feedURLs['fixtures'], 'rss'); ?>" target="_blank" title="RSS feed" class="feedLink rss">RSS</a>
								<a href="<?php echo sprintf($feedURLs['fixtures'], 'json'); ?>" target="_blank" title="JSON feed" class="feedLink json">JSON</a>
							</dd>

							<dt>Results (previous fixtures)</dt>
							<dd>
								<?php echo sprintf($feedURLs['results'], '<span class="highlight">[rss|json]</span>'); ?>

								<a href="<?php echo sprintf($feedURLs['results'], 'rss'); ?>" target="_blank" title="RSS feed" class="feedLink rss">RSS</a>
								<a href="<?php echo sprintf($feedURLs['results'], 'json'); ?>" target="_blank" title="JSON feed" class="feedLink json">JSON</a>
							</dd>
						</dl>
					</div>
				</td>
			</tr>

			<tr>
				<th>Feed Pages</th>

				<td>
					<div>
						<label for="settingFeedsPagesPre">Page before listing</label>
						<input type="number" name="feedsPagePre" value="<?php echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_page_pre', ''); ?>" min="1" id="settingFeedsPagesPre">
					</div>

					<div>
						<label for="settingFeedsPagesPost">Page before listing</label>
						<input type="number" name="feedsPagePost" value="<?php echo get_option(SPORT_LEAGUE_VAR_NAME . '_feeds_page_post', ''); ?>" min="1" id="settingFeedsPagesPost">
					</div>

					<p class="description">
						To insert Wordpress post content as list items, in the feeds, enter the post IDs into these fields.<br>
						You can add page content before and/or after the feed list.
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="submit" value="Save Changes" class="button-primary" id="submit">
		</p>
	</form>
</div>