<?php 
	$slot_name = strftime("%H:%M", strtotime($slot['TimeSlot']['start']));
	$slot_id = $slot['DayTimeSlot']['id'];
	$panel_id = $panel['Panel']['id'];
?>
<h1>Schedule: <?php  __($panel['Panel']['name']);?> (id <?php echo $panel_id;?>)</h1>
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
							'action' => 'schedule_new',
							'panel' => $panel_id,
							'slot' => $slot_id,
						))); ?>
						
<?php echo $this->Form->input('PanelSchedule.room_id'); ?>

<?php
    // List users who want to participate in this panel
    echo $this->element('schedule_participants');

    // List users who want to watch this panel
    // echo $this->element('schedule_watchers');
?>

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

<?php echo $this->Form->end(__(' Schedule ', true));?>
<div id="dialog-confirm"></div>
<div id="dialog-adjacent-confirm"></div>