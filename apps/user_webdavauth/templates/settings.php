<form id="webdavauth" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong>WebDAV Authentication</strong></legend>
		<p><label for="webdav_url"><?php echo $l->t('WebDAV URL: http://');?><input type="text" id="webdav_url" name="webdav_url" value="<?php echo $_['webdav_url']; ?>"></label>
			<input type="hidden" name="requesttoken" value="<?php echo $_['requesttoken'] ?>" id="requesttoken">
		<input type="submit" value="Save" />
	</fieldset>
</form>
