<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<h2 id="title_general"><?php echo $l->t('General'); ?></h2>
<div id="general">
	<table class="nostyle">
		<tr>
			<td>
				<label for="timezone" class="bold"><?php echo $l->t('Timezone');?></label>
				&nbsp;&nbsp;
			</td>
			<td>
				<select style="display: none;" id="timezone" name="timezone">
				<?php
				$continent = '';
				foreach($_['timezones'] as $timezone):
					$ex=explode('/', $timezone, 2);//obtain continent,city
					if (!isset($ex[1])) {
						$ex[1] = $ex[0];
						$ex[0] = "Other";
					}
					if ($continent!=$ex[0]):
						if ($continent!="") echo '</optgroup>';
						echo '<optgroup label="'.$ex[0].'">';
					endif;
					$city=strtr($ex[1], '_', ' ');
					$continent=$ex[0];
					echo '<option value="'.$timezone.'"'.($_['timezone'] == $timezone?' selected="selected"':'').'>'.$city.'</option>';
					var_dump($_['timezone']);
				endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;&nbsp;
			</td>
			<td>
				<input type="checkbox" name="timezonedetection" id="timezonedetection">
				&nbsp;
				<label for="timezonedetection"><?php echo $l->t('Update timezone automatically'); ?></label>
			</td>
		</tr>
		<tr>
			<td>
				<label for="timeformat" class="bold"><?php echo $l->t('Time format');?></label>
				&nbsp;&nbsp;
			</td>
			<td>
				<select style="display: none; width: 60px;" id="timeformat" title="<?php echo "timeformat"; ?>" name="timeformat">
					<option value="24" id="24h"><?php echo $l->t("24h"); ?></option>
					<option value="ampm" id="ampm"><?php echo $l->t("12h"); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="firstday" class="bold"><?php echo $l->t('Start week on');?></label>
				&nbsp;&nbsp;
			</td>
			<td>
				<select style="display: none;" id="firstday" title="<?php echo "First day"; ?>" name="firstday">
					<option value="mo" id="mo"><?php echo $l->t("Monday"); ?></option>
					<option value="su" id="su"><?php echo $l->t("Sunday"); ?></option>
				</select>
			</td>
		</tr>
		<tr class="advancedsettings">
			<td>
				<label for="" class="bold"><?php echo $l->t('Cache');?></label>
				&nbsp;&nbsp;
			</td>
			<td>
				<input id="cleancalendarcache" type="button" class="button" value="<?php echo $l->t('Clear cache for repeating events');?>">
			</td>
		</tr>
	</table>
</div>
<h2 id="title_urls"><?php echo $l->t('URLs'); ?></h2>
<div id="urls">
		<?php echo $l->t('Calendar CalDAV syncing addresses'); ?> (<a href="http://owncloud.org/synchronisation/" target="_blank"><?php echo $l->t('more info'); ?></a>)
		<dl>
		<dt><?php echo $l->t('Primary address (Kontact et al)'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('caldav'); ?></code></dd>
		<dt><?php echo $l->t('iOS/OS X'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('caldav'); ?>principals/<?php echo OCP\USER::getUser(); ?></code>/</dd>
		<dt><?php echo $l->t('Read only iCalendar link(s)'); ?></dt>
		<dd>
			<?php foreach($_['calendars'] as $calendar) {
			if($calendar['userid'] == OCP\USER::getUser()){
				$uri = rawurlencode(html_entity_decode($calendar['uri'], ENT_QUOTES, 'UTF-8'));
			}else{
				$uri = rawurlencode(html_entity_decode($calendar['uri'], ENT_QUOTES, 'UTF-8')) . '_shared_by_' . $calendar['userid'];
			}
			?>
			<a href="<?php echo OCP\Util::linkToRemote('caldav').'calendars/'.OCP\USER::getUser().'/'.$uri ?>?export"><?php echo OCP\Util::sanitizeHTML($calendar['displayname']) ?></a><br />
			<?php } ?>
		</dd>
		</dl>
	</div>
</div>