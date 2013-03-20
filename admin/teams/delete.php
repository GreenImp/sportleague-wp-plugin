<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:52
 */

$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if(is_numeric($id) && ($id > 0)){
	// id is okay - check that no fixtures use this team
	if(count(Fixtures::getInstance()->getTeamFixtures($id)) > 0){
		// fixtures are using this team
		Message::add('error', 'There are fixtures that use this team, please remove the fixtures first');
	}elseif($teamClass->removeTeam($id)){
		// item removed
		Message::add('updated', 'The team was removed');
	}else{
		Message::add('error', 'There was a problem removing the team, please try again');
	}
}else{
	Message::add('error', 'No Item was selected');
}

wp_redirect($currentURL);
exit;
?>