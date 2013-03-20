<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 11:52
 */

$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if(is_numeric($id) && ($id > 0)){
	// id is okay - remove the item
	if($fixtureClass->removeFixture($id)){
		// item removed
		Message::add('updated', 'The fixture was removed');
	}else{
		Message::add('error', 'There was a problem removing the fixture, please try again');
	}
}else{
	Message::add('error', 'No Item was selected');
}

wp_redirect($currentURL);
exit;
?>