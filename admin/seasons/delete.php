<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:52
 */

$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if(is_numeric($id) && ($id > 0)){
	// id is okay - check that no fixtures use this season
	if(count($fixtureClass->getFixtures($id, -1, -1, true)) > 0){
		// fixtures are using this season
		Message::add('error', 'There are fixtures that use this season, please remove the fixtures first');
	}elseif($fixtureClass->removeSeason($id)){
		// item removed
		Message::add('updated', 'The season was removed');
	}else{
		Message::add('error', 'There was a problem removing the season, please try again');
	}
}else{
	Message::add('error', 'No Item was selected');
}

wp_redirect($currentURL);
exit;
?>