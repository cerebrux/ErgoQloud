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

class OC_Connector_Sabre_File extends OC_Connector_Sabre_Node implements Sabre_DAV_IFile {

	/**
	 * Updates the data
	 *
	 * The data argument is a readable stream resource.
	 *
	 * After a succesful put operation, you may choose to return an ETag. The
	 * etag must always be surrounded by double-quotes. These quotes must
	 * appear in the actual string you're returning.
	 *
	 * Clients may use the ETag from a PUT request to later on make sure that
	 * when they update the file, the contents haven't changed in the mean
	 * time.
	 *
	 * If you don't plan to store the file byte-by-byte, and you return a
	 * different object on a subsequent GET you are strongly recommended to not
	 * return an ETag, and just return null.
	 *
	 * @param resource $data
	 * @return string|null
	 */
	public function put($data) {

		OC_Filesystem::file_put_contents($this->path,$data);

		return OC_Connector_Sabre_Node::getETagPropertyForPath($this->path);
	}

	/**
	 * Returns the data
	 *
	 * @return string
	 */
	public function get() {

		return OC_Filesystem::fopen($this->path,'rb');

	}

	/**
	 * Delete the current file
	 *
	 * @return void
	 */
	public function delete() {

		OC_Filesystem::unlink($this->path);

	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return int
	 */
	public function getSize() {
		$this->getFileinfoCache();
		return $this->fileinfo_cache['size'];

	}

	/**
	 * Returns the ETag for a file
	 *
	 * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
	 * The ETag is an arbritrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined
	 *
	 * @return mixed
	 */
	public function getETag() {
		$properties = $this->getProperties(array(self::GETETAG_PROPERTYNAME));
		if (isset($properties[self::GETETAG_PROPERTYNAME])) {
			return $properties[self::GETETAG_PROPERTYNAME];
		}
		return $this->getETagPropertyForPath($this->path);
	}

	/**
	 * Creates a ETag for this path.
	 * @param string $path Path of the file
	 * @return string|null Returns null if the ETag can not effectively be determined
	 */
	static protected function createETag($path) {
		return OC_Filesystem::hash('md5', $path);
	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {
		if (isset($this->fileinfo_cache['mimetype'])) {
			return $this->fileinfo_cache['mimetype'];
		}

		return OC_Filesystem::getMimeType($this->path);

	}
}
