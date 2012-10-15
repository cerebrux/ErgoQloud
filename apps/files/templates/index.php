<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}table td{position:static !important;}</style><![endif]-->
<div id="controls">
	<?php echo($_['breadcrumb']); ?>
	<?php if ($_['isCreatable']):?>
		<div class="actions <?php if (isset($_['files']) and count($_['files'])==0):?>emptyfolder<?php endif; ?>">
			<div id='new' class='button'>
				<a><?php echo $l->t('New');?></a>
				<ul class="popup popupTop">
					<li style="background-image:url('<?php echo OCP\mimetype_icon('text/plain') ?>')" data-type='file'><p><?php echo $l->t('Text file');?></p></li>
					<li style="background-image:url('<?php echo OCP\mimetype_icon('dir') ?>')" data-type='folder'><p><?php echo $l->t('Folder');?></p></li>
					<li style="background-image:url('<?php echo OCP\image_path('core','actions/public.png') ?>')" data-type='web'><p><?php echo $l->t('From url');?></p></li>
				</ul>
			</div>
			<div class="file_upload_wrapper svg">
				<form data-upload-id='1' id="data-upload-form" class="file_upload_form" action="<?php echo OCP\Util::linkTo('files', 'ajax/upload.php'); ?>" method="post" enctype="multipart/form-data" target="file_upload_target_1">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
					<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
					<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
					<input class="file_upload_start" type="file" name='files[]'/>
					<a href="#" class="file_upload_button_wrapper" onclick="return false;" title="<?php echo $l->t('Upload'); echo  ' max. '.$_['uploadMaxHumanFilesize'] ?>"></a>
					<button class="file_upload_filename"></button>
					<iframe name="file_upload_target_1" class='file_upload_target' src=""></iframe>
				</form>
			</div>
					<div id="upload">
						<div id="uploadprogressbar"></div>
						<input type="button" class="stop" style="display:none" value="<?php echo $l->t('Cancel upload');?>" onclick="javascript:Files.cancelUploads();" />
					</div>

		</div>
		<div id="file_action_panel"></div>
	<?php else:?>
		<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
	<?php endif;?>
	<input type="hidden" name="permissions" value="<?php echo $_['permissions']; ?>" id="permissions">
</div>
<div id='notification'></div>

<?php if (isset($_['files']) and $_['isCreatable'] and count($_['files'])==0):?>
	<div id="emptyfolder"><?php echo $l->t('Nothing in here. Upload something!')?></div>
<?php endif; ?>

<table>
	<thead>
		<tr>
			<th id='headerName'>
				<input type="checkbox" id="select_all" />
				<span class='name'><?php echo $l->t( 'Name' ); ?></span>
				<span class='selectedActions'>
<!-- 					<a href="" class="share"><img class='svg' alt="Share" src="<?php echo OCP\image_path("core", "actions/share.svg"); ?>" /> <?php echo $l->t('Share')?></a> -->
					<?php if($_['allowZipDownload']) : ?>
						<a href="" class="download"><img class='svg' alt="Download" src="<?php echo OCP\image_path("core", "actions/download.svg"); ?>" /> <?php echo $l->t('Download')?></a>
					<?php endif; ?>
				</span>
			</th>
			<th id="headerSize"><?php echo $l->t( 'Size' ); ?></th>
			<th id="headerDate">
				<span id="modified"><?php echo $l->t( 'Modified' ); ?></span>
				<?php if ($_['permissions'] & OCP\Share::PERMISSION_DELETE): ?>
<!-- 					NOTE: Temporary fix to allow unsharing of files in root of Shared folder -->
					<?php if ($_['dir'] == '/Shared'): ?>
						<span class="selectedActions"><a href="" class="delete"><?php echo $l->t('Unshare')?> <img class="svg" alt="<?php echo $l->t('Unshare')?>" src="<?php echo OCP\image_path("core", "actions/delete.svg"); ?>" /></a></span>
					<?php else: ?>
						<span class="selectedActions"><a href="" class="delete"><?php echo $l->t('Delete')?> <img class="svg" alt="<?php echo $l->t('Delete')?>" src="<?php echo OCP\image_path("core", "actions/delete.svg"); ?>" /></a></span>
					<?php endif; ?>
				<?php endif; ?>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>
<div id="editor"></div>
<div id="uploadsize-message" title="<?php echo $l->t('Upload too large')?>">
	<p>
		<?php echo $l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.');?>
	</p>
</div>
<div id="scanning-message">
	<h3>
		<?php echo $l->t('Files are being scanned, please wait.');?> <span id='scan-count'></span>
	</h3>
	<p>
		<?php echo $l->t('Current scanning');?> <span id='scan-current'></span>
	</p>
</div>

<!-- config hints for javascript -->
<input type="hidden" name="allowZipDownload" id="allowZipDownload" value="<?php echo $_['allowZipDownload']; ?>" />
