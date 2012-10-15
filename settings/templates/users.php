<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$allGroups=array();
foreach($_["groups"] as $group) {
	$allGroups[] = $group['name'];
}
$_['subadmingroups'] = $allGroups;
$items = array_flip($_['subadmingroups']);
unset($items['admin']);
$_['subadmingroups'] = array_flip($items);
?>
<script>
var isadmin = <?php echo $_['isadmin']?'true':'false'; ?>;
</script>
<div id="controls">
	<form id="newuser" autocomplete="off">
		<input id="newusername" type="text" placeholder="<?php echo $l->t('Name')?>" /> <input
			type="password" id="newuserpassword"
			placeholder="<?php echo $l->t('Password')?>" /> <select
			class="groupsselect"
			id="newusergroups" data-placeholder="groups"
			title="<?php echo $l->t('Groups')?>" multiple="multiple">
			<?php foreach($_["groups"] as $group): ?>
			<option value="<?php echo $group['name'];?>">
				<?php echo $group['name'];?>
			</option>
			<?php endforeach;?>
		</select> <input type="submit" value="<?php echo $l->t('Create')?>" />
	</form>
	<div class="quota">
		<span><?php echo $l->t('Default Quota');?>:</span>
		<div class="quota-select-wrapper">
			<?php if((bool) $_['isadmin']): ?>
			<select class='quota'>
				<?php foreach($_['quota_preset'] as $preset):?>
				<?php if($preset!='default'):?>
				<option
				<?php if($_['default_quota']==$preset) echo 'selected="selected"';?>
					value='<?php echo $preset;?>'>
					<?php echo $preset;?>
				</option>
				<?php endif;?>
				<?php endforeach;?>
				<?php if(array_search($_['default_quota'], $_['quota_preset'])===false):?>
				<option selected="selected"
					value='<?php echo $_['default_quota'];?>'>
					<?php echo $_['default_quota'];?>
				</option>
				<?php endif;?>
				<option value='other'>
					<?php echo $l->t('Other');?>
					...
				</option>
			</select> <input class='quota-other'></input>
			<?php endif; ?>
			<?php if((bool) !$_['isadmin']): ?>
				<select class='quota' disabled="disabled">
					<option selected="selected">
				<?php echo $_['default_quota'];?>
					</option>
				</select>
			<?php endif; ?>
		</div>
	</div>
</div>

<div id='notification'></div>

<table data-groups="<?php echo implode(', ', $allGroups);?>">
	<thead>
		<tr>
			<th id='headerName'><?php echo $l->t('Name')?></th>
			<th id="headerPassword"><?php echo $l->t( 'Password' ); ?></th>
			<th id="headerGroups"><?php echo $l->t( 'Groups' ); ?></th>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<th id="headerSubAdmins"><?php echo $l->t('Group Admin'); ?></th>
			<?php endif;?>
			<th id="headerQuota"><?php echo $l->t( 'Quota' ); ?></th>
			<th id="headerRemove">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
		<tr data-uid="<?php echo $user["name"] ?>">
			<td class="name"><?php echo $user["name"]; ?></td>
			<td class="password"><span>●●●●●●●</span> <img class="svg action"
				src="<?php echo image_path('core', 'actions/rename.svg')?>"
				alt="set new password" title="set new password"/>
			</td>
			<td class="groups"><select
				class="groupsselect"
				data-username="<?php echo $user['name'] ;?>"
				data-user-groups="<?php echo $user['groups'] ;?>"
				data-placeholder="groups" title="<?php echo $l->t('Groups')?>"
				multiple="multiple">
					<?php foreach($_["groups"] as $group): ?>
					<option value="<?php echo $group['name'];?>">
						<?php echo $group['name'];?>
					</option>
					<?php endforeach;?>
			</select>
			</td>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<td class="subadmins"><select
				class="subadminsselect"
				data-username="<?php echo $user['name'] ;?>"
				data-subadmin="<?php echo $user['subadmin'] ;?>"
				data-placeholder="subadmins" title="<?php echo $l->t('Group Admin')?>"
				multiple="multiple">
					<?php foreach($_["subadmingroups"] as $group): ?>
					<option value="<?php echo $group;?>">
						<?php echo $group;?>
					</option>
					<?php endforeach;?>
			</select>
			</td>
			<?php endif;?>
			<td class="quota">
				<div class="quota-select-wrapper">
					<select class='quota-user'>
						<?php foreach($_['quota_preset'] as $preset):?>
						<option
						<?php if($user['quota']==$preset) echo 'selected="selected"';?>
							value='<?php echo $preset;?>'>
							<?php echo $preset;?>
						</option>
						<?php endforeach;?>
						<?php if(array_search($user['quota'], $_['quota_preset'])===false):?>
						<option selected="selected" value='<?php echo $user['quota'];?>'>
							<?php echo $user['quota'];?>
						</option>
						<?php endif;?>
						<option value='other'>
							<?php echo $l->t('Other');?>
							...
						</option>
					</select> <input class='quota-other'></input>
				</div>
			</td>
			<td class="remove">
				<?php if($user['name']!=OC_User::getUser()):?> 
					<a href="#" class="action delete" original-title="<?php echo $l->t('Delete')?>">
						<img src="<?php echo image_path('core', 'actions/delete.svg') ?>" />
					</a>
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>