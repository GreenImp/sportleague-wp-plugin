<?php
/**
 * Author: lee
 * Date Created: 05/09/2012 10:10
 */

// get the URL for this page
$currentURL = reset(explode('&', $_SERVER['REQUEST_URI']));
// define the URL for sorting the table columns
$sortURL = $currentURL . (isset($_GET['team']) ? '&amp;team=' . $_GET['team'] : '') . (isset($_GET['season']) ? '&amp;season=' . $_GET['season'] : '') . '&amp;orderby=%s&amp;order=%s';
// define the fixture add URL
$addURL = $currentURL . '&amp;action=add';
// define the fixture edit URL
$editURL = $currentURL . '&amp;action=edit&amp;id=%s';
// define the fixture delete URL
$deleteURL = $currentURL . '&amp;action=delete&amp;id=%s';

// get the Fixtures class
$fixtureClass = Fixtures::getInstance();

$featuredTeamID = $fixtureClass->getFeaturedTeamID();


// check the page action
$action = isset($_GET['action']) ? $_GET['action'] : '';
switch($action){
	case 'delete':
	case 'remove':
		require_once(dirname(__FILE__) . '/delete.php');
	break;
	case 'edit':
	case 'add':
		require_once(dirname(__FILE__) . '/modify.php');
	break;
	case 'view':
	default:
		require_once(dirname(__FILE__) . '/view.php');
	break;
}
?>