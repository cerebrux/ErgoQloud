<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class contains all hooks.
 */

namespace OCA_Versions;

class Hooks {

	/**
	 * listen to write event.
	 */
	public static function write_hook( $params ) {

		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			
			$versions = new Storage( new \OC_FilesystemView('') );

			$path = $params[\OC_Filesystem::signal_param_path];

			if($path<>'') $versions->store( $path );

		}
	}


	/**
	 * @brief Erase versions of deleted file
	 * @param array
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * cleanup the versions directory if the actual file gets deleted
	 */
	public static function remove_hook($params) {
		$versions_fileview = \OCP\Files::getStorage('files_versions');
		$rel_path =  $params['path'];
		$abs_path = \OCP\Config::getSystemValue('datadirectory').$versions_fileview->getAbsolutePath('').$rel_path.'.v';
		if(Storage::isversioned($rel_path)) {
			$versions = Storage::getVersions($rel_path);
			foreach ($versions as $v) {
				unlink($abs_path . $v['version']);
			}
		}
	}

	/**
	 * @brief rename/move versions of renamed/moved files
	 * @param array with oldpath and newpath
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public static function rename_hook($params) {
		$versions_fileview = new \OC_FilesystemView('/'.\OCP\User::getUser().'files_versions');
		list($oldpath_uid, $oldpath) = Storage::getUidAndFilename($params['oldpath']);
		list($newpath_uid, $newpath) = Storage::getUidAndFilename($params['newpath']);
		
		$abs_oldpath = \OC_Filesystem::normalizePath(\OC_User::getHome($oldpath_uid).'/files_versions/'.$oldpath.'.v');
		$abs_newpath = \OC_Filesystem::normalizePath(\OC_User::getHome($newpath_uid).'/files_versions/'.$newpath.'.v');
		if(Storage::isversioned($oldpath)) {
			$info=pathinfo($abs_newpath);
			if(!file_exists($info['dirname'])) mkdir($info['dirname'],0750,true);
			$versions = Storage::getVersions($oldpath);
			foreach ($versions as $v) {
				rename($abs_oldpath.$v['version'], $abs_newpath.$v['version']);
			}
		}
	}

}
