<?php

class OC_Search_Provider_File extends OC_Search_Provider{
	function search($query) {
		$files=OC_FileCache::search($query,true);
		$results=array();
		foreach($files as $fileData) {
			$path = $fileData['path'];
			$mime = $fileData['mimetype'];

			$name = basename($path);
			$text = '';
			$skip = false;
			if($mime=='httpd/unix-directory') {
				$link = OC_Helper::linkTo( 'files', 'index.php', array('dir' => $path));
				$type = 'Files';
			}else{
				$link = OC_Helper::linkTo( 'files', 'download.php', array('file' => $path));
				$mimeBase = $fileData['mimepart'];
				switch($mimeBase) {
					case 'audio':
						$skip = true;
						break;
					case 'text':
						$type = 'Text';
						break;
					case 'image':
						$type = 'Images';
						break;
					default:
						if($mime=='application/xml') {
							$type = 'Text';
						}else{
							$type = 'Files';
						}
				}
			}
			if(!$skip) {
				$results[] = new OC_Search_Result($name, $text, $link, $type);
			}
		}
		return $results;
	}
}
