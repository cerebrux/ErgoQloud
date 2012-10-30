<?php

// Init owncloud
require_once '../../lib/base.php';

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = $_POST["username"];

// A user shouldn't be able to delete his own account
if(OC_User::getUser() === $username) {
	exit;
}

if(!OC_Group::inGroup(OC_User::getUser(), 'admin') && !OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$l = OC_L10N::get('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
	exit();
}

// Return Success story
if( OC_User::deleteUser( $username )) {
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Unable to delete user") )));
}