<?php 
	$slot_name = strftime("%H:%M", strtotime($slot['TimeSlot']['start']));
	$slot_id = $slot['DayTimeSlot']['id'];
	$panel_id = $panel['Panel']['id'];
?>
<h1>Edit Panelists: <?php  __($panel['Panel']['name']);?> (id <?php echo $panel_id;?>)</h1>
<div class="panel_details">
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Panel Type'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($panel['PanelType']['name'], array('controller' => 'panel_types', 'action' => 'view', $panel['PanelType']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $panel['Panel']['description']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Panel Length'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($panel['PanelLength']['name'], array('controller' => 'panel_lengths', 'action' => 'view', $panel['PanelLength']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Source'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($panel['Track']['name'], array('controller' => 'tracks', 'action' => 'view', $panel['Track']['id'])); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div style="margin-top: 2em;">
<h3>Schedule For: <?php echo $slot['ConDay']['name'];?> - <?php echo $slot_name;?> 
	(slot id <?php echo $slot_id;?>)</h3>
</div>

<?php echo $this->Form->create('Panel', array('url' => array(
							'controller' => 'panels',
							'action' => 'panelist_edit',
							'panel' => $panel_id,
							'slot' => $slot_id,
						))); ?>
						

<?php
    // List users who want to participate in this panel
    echo $this->element('schedule_participants');

    // List users who want to watch this panel
    // echo $this->element('schedule_watchers');
?>

<?php 
// Next, list non-available (but already assigned panelists)
?>
<h4>Participants Assigned (though not available or not requested):</h4>
<table>
<thead>
	<tr>
	<th>Ldr</th>
	<th>Mod</th>
	<th>Pnlst</th>
	<th>Name</th>
	<th>Avoids</th>
	<th>Collabs</th>
	</tr>
</thead>
<tbody>
<?php 
	foreach($non_avail_assigned as $usr) {
		$user_id = $usr['PanelParticipant']['user_id'];
		$is_panelist = !($leader==$user_id) && !($moderator==$user_id) && array_key_exists($user_id, $panelists_assigned);
		$leading_role = (($leader==$user_id) || ($moderator==$user_id)) && array_key_exists($user_id, $panelists_assigned);
		$avoid_str = '';
		$checkbox_class = "";
		if(array_key_exists($user_id, $user_avoids)) {
			$people_avoid = $user_avoids[$user_id];
			foreach($people_avoid as $person) {
				$person_id = $person['User']['id'];
				if(array_search($person_id, $participant_ids)) {
					$avoid_str .= '<b>' . $person['User']['name'] . '</b>, ';
				} else {
					$avoid_str .= $person['User']['name'] . ', ';
				}
			}
		} 
		$collab_str = '';
		if(array_key_exists($user_id, $user_collabs)) {
			$people_collab = $user_collabs[$user_id];
			foreach($people_collab as $person) {
				$person_id = $person['User']['id'];
				if(array_search($person_id, $participant_ids)) {
					$collab_str .= '<b>' . $person['User']['name'] . '</b>, ';
				} else {
					$collab_str .= $person['User']['name'] . ', ';
				}
			}
		} 
?>
	<tr>
		<td>
			<input type="radio" name="data[PanelParticipant][leader]" class="<?php echo $checkbox_class; ?>" title="leader" value="<?php echo $user_id;?>" <?php if($leader==$user_id) echo 'checked="checked"';?> />
		</td>
		<td>
			<input type="radio" name="data[PanelParticipant][moderator]" class="<?php echo $checkbox_class; ?>" title="moderator" value="<?php echo $user_id;?>" <?php if($moderator==$user_id) echo 'checked="checked"';?> />
		</td>
		<td>
			<input type="checkbox" name="data[PanelParticipant][panelists][]" class="<?php echo $checkbox_class; ?>" title="panelist" value="<?php echo $user_id;?>" <?php if($is_panelist) echo 'checked="checked"';?> />
		</td>
		<td title="user id: <?php echo $user_id;?>"><?php if($leading_role) echo '<b>';?><?php echo $usr['User']['name'];?><?php if($leading_role) echo '</b>';?>
		</td>
		<td style="color: red;"><?php echo $avoid_str;?></td>
		<td style="color: green;"><?php echo $collab_str;?></td>
	</tr>
<?php 
	} ?>
</tbody>
</table>



<label>Add Panelist Ids:</label>
<ol>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px; margin: 3px 0px;" /></li>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px;" /></li>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px;" /></li>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px;" /></li>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px;" /></li>
	<li><input type="text" name="data[PanelParticipant][panelists][]" style="width: 3em; padding: 1px;" /></li>
</ol>

<input type="hidden" title="adjacent_panels_to_delete" id="adjacent_panels_to_delete" name="data[adjacent_panels_to_delete]" value="" />

<?php echo $this->Form->end(__(' Save Panelists ', true));?>
<div id="dialog-confirm"></div>
<div id="dialog-adjacent-confirm"></div>

<?php // echo $this->element('sql_dump'); ?>