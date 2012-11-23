<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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

class OC_Connector_Sabre_Directory extends OC_Connector_Sabre_Node implements Sabre_DAV_ICollection, Sabre_DAV_IQuota {

	/**
	 * Creates a new file in the directory
	 *
	 * Data will either be supplied as a stream resource, or in certain cases
	 * as a string. Keep in mind that you may have to support either.
	 *
	 * After succesful creation of the file, you may choose to return the ETag
	 * of the new file here.
	 *
	 * The returned ETag must be surrounded by double-quotes (The quotes should
	 * be part of the actual string).
	 *
	 * If you cannot accurately determine the ETag, you should not return it.
	 * If you don't store the file exactly as-is (you're transforming it
	 * somehow) you should also not return an ETag.
	 *
	 * This means that if a subsequent GET to this new file does not exactly
	 * return the same contents of what was submitted here, you are strongly
	 * recommended to omit the ETag.
	 *
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @return null|string
	 */
	public function createFile($name, $data = null) {
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			$info = OC_FileChunking::decodeName($name);
			if (empty($info)) {
				throw new Sabre_DAV_Exception_NotImplemented();
			}
			$chunk_handler = new OC_FileChunking($info);
			$chunk_handler->store($info['index'], $data);
			if ($chunk_handler->isComplete()) {
				$newPath = $this->path . '/' . $info['name'];
				$chunk_handler->file_assemble($newPath);
				return OC_Connector_Sabre_Node::getETagPropertyForPath($newPath);
			}
		} else {
			$newPath = $this->path . '/' . $name;
			OC_Filesystem::file_put_contents($newPath, $data);
			return OC_Connector_Sabre_Node::getETagPropertyForPath($newPath);
		}

		return null;
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @return void
	 */
	public function createDirectory($name) {

		$newPath = $this->path . '/' . $name;
		OC_Filesystem::mkdir($newPath);

	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * @param string $name
	 * @throws Sabre_DAV_Exception_FileNotFound
	 * @return Sabre_DAV_INode
	 */
	public function getChild($name, $info = null) {

		$path = $this->path . '/' . $name;
		if (is_null($info)) {
			$info = OC_Files::getFileInfo($path);
		}

		if (!$info) {
			throw new Sabre_DAV_Exception_NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] == 'httpd/unix-directory') {
			$node = new OC_Connector_Sabre_Directory($path);
		} else {
			$node = new OC_Connector_Sabre_File($path);
		}

		$node->setFileinfoCache($info);
		return $node;
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return Sabre_DAV_INode[]
	 */
	public function getChildren() {

		$source = $this->getFileSource($this->path);
		$path = $source['path'];
		$user = $source['user'];
		
		$folder_content = OC_Files::getDirectoryContent($this->path);
		$paths = array();
		foreach($folder_content as $info) {
			$paths[] = $path.'/'.$info['name'];
		}
		$properties = array_fill_keys($paths, array());
		if(count($paths)>0) {
			$placeholders = join(',', array_fill(0, count($paths), '?'));
			$query = OC_DB::prepare( 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ?' . ' AND `propertypath` IN ('.$placeholders.')' );
			array_unshift($paths, $user); // prepend userid
			$result = $query->execute( $paths );
			while($row = $result->fetchRow()) {
				$propertypath = $row['propertypath'];
				$propertyname = $row['propertyname'];
				$propertyvalue = $row['propertyvalue'];
				$properties[$propertypath][$propertyname] = $propertyvalue;
			}
		}

		$nodes = array();
		foreach($folder_content as $info) {
			$node = $this->getChild($info['name'], $info);
			$node->setPropertyCache($properties[$path.'/'.$info['name']]);
			$nodes[] = $node;
		}
		return $nodes;
	}

	/**
	 * Checks if a child exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {

		$path = $this->path . '/' . $name;
		return OC_Filesystem::file_exists($path);

	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 */
	public function delete() {

		if ($this->path != "/Shared") {
			foreach($this->getChildren() as $child) $child->delete();
			OC_Filesystem::rmdir($this->path);
		}

	}

	/**
	 * Returns available diskspace information
	 *
	 * @return array
	 */
	public function getQuotaInfo() {
		$rootInfo=OC_FileCache_Cached::get('');
		return array(
			$rootInfo['size'],
			OC_Filesystem::free_space()
		);

	}

	/**
	 * Returns a list of properties for this nodes.;
	 *
	 * The properties list is a list of propertynames the client requested,
	 * encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author
	 * If the array is empty, all properties should be returned
	 *
	 * @param array $properties
	 * @return void
	 */
	public function getProperties($properties) {
		$props = parent::getProperties($properties);
		if (in_array(self::GETETAG_PROPERTYNAME, $properties) && !isset($props[self::GETETAG_PROPERTYNAME])) {
			$props[self::GETETAG_PROPERTYNAME] 
				= OC_Connector_Sabre_Node::getETagPropertyForPath($this->path);
		}
		return $props;
	}
}
