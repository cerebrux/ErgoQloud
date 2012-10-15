<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class OC_USER_WEBDAVAUTH extends OC_User_Backend {
	protected $webdavauth_url;

	public function __construct() {
		$this->webdavauth_url = OC_Config::getValue( "user_webdavauth_url" );
	}

	public function createUser() {
		// Can't create user
		OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to create users from web frontend using WebDAV user backend',3);
		return false;
	}

	public function deleteUser() {
		// Can't delete user
		OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to delete users from web frontend using WebDAV user backend',3);
		return false;
	}

	public function setPassword ( $uid, $password ) {
		// We can't change user password
		OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to change password for users from web frontend using WebDAV user backend',3);
		return false;
	}

	public function checkPassword( $uid, $password ) {

		$url= 'http://'.urlencode($uid).':'.urlencode($password).'@'.$this->webdavauth_url;
		$headers = get_headers($url);
		if($headers==false) {
			OC_Log::write('OC_USER_WEBDAVAUTH', 'Not possible to connect to WebDAV Url: "'.$this->webdavauth_url.'" ' ,3);
			return false;

		}
		$returncode= substr($headers[0], 9, 3);

		if($returncode=='401') {
			return false;
		}else{
			return true;
		}

	}

	/*
	* we don´t know if a user exists without the password. so we have to return false all the time
	*/
	public function userExists( $uid ) {
		return false;
	}

	/*
	* we don´t know the users so all we can do it return an empty array here
	*/
	public function getUsers() {
		$returnArray = array();

		return $returnArray;
	}
}
