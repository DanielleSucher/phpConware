<div class="users index">
	<h2><?php __('The last day of Registration is currently set to '.$registration_end_date);?></h2>
	<br />
	<br />
	<?php echo $this->Form->create('User');?>
		<fieldset>
	 		<legend><?php __('Change the last day of registration'); ?></legend>
	 		<?php echo $session->flash('auth'); ?>
		<?php
			if($admin) {
				echo $this->Form->input('date', array('label'=>'YYYY-MM-DD'));
			}
		?>
		</fieldset>
	<?php echo $this->Form->end(__('Submit', true));?>
</div>