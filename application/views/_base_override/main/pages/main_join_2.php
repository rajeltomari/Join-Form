<?php echo text_output($header, 'h1', 'page_head');?>

<?php echo form_open('main/join');?>
	<?php echo lang_output('labels_player_info', 'h3');?>
	<table class="table100">
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_playerbio_name')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['name']);?></td>
		</tr>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_playerbio_email')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['email']);?></td>
		</tr>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_password')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_password($inputs['password']);?></td>
		</tr>
		<?php echo table_row_spacer(3, 20);?>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_playerbio_dob')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['dob']);?></td>
		</tr>
		<?php echo table_row_spacer(3, 20);?>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_ucip_member')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_dropdown('ucip', $drop_down['ucip'], 'id="ucip"', '');?></td>
		</tr>
		<?php echo table_row_spacer(3, 20);?>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_ucip_dbid')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['dbid']);?></td>
		</tr>
		<?php echo table_row_spacer(3, 20);?>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_playerbio_im')), '');?></td>
			<td class="cell_spacer"></td>
			<td>
				<?php echo lang_output('labels_join_im_instructions', 'span', 'font80 orange bold');?><br />
				<?php echo form_textarea($inputs['im']);?>
			</td>
		</tr>
	</table><br />
	
	<?php echo lang_output('labels_character', 'h3');?>
	<table class="table100">
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_first_name')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['first_name']);?></td>
		</tr>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_middle_name')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['middle_name']);?></td>
		</tr>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_last_name')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['last_name']);?></td>
		</tr>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_suffix')), '');?></td>
			<td class="cell_spacer"></td>
			<td><?php echo form_input($inputs['suffix']);?></td>
		</tr>
		<?php echo table_row_spacer(3, 20);?>
		<tr>
			<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_position')), '');?></td>
			<td class="cell_spacer"></td>
			<td>
				<?php echo form_dropdown_position('position_1', $selected_position, 'id="position"', 'open');?>
				&nbsp; <span id="loading_update" class="hidden fontSmall gray"><?php echo img($loading);?></span>
				<p id="position_desc" class="fontSmall gray"><?php echo text_output($pos_desc, '');?></p>
			</td>
		</tr>
	</table><br />

	<?php if (isset($join)): ?>
		<?php foreach ($join as $a): ?>
			<?php if (isset($a['fields'])): ?>
				<?php echo text_output($a['name'], 'h3');?>
				
				<table class="table100">
					<tbody>
						
					<?php foreach ($a['fields'] as $f): ?>
						<tr>
							<td class="cell_label"><?php echo $f['field_label'];?></td>
							<td class="cell_spacer"></td>
							<td><?php echo $f['input'];?></td>
						</tr>
					<?php endforeach; ?>
					
					</tbody>
				</table><br />
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>
	
	<?php if ($this->settings['use_sample_post'] == 'y'): ?>
		<?php echo lang_output('labels_join_other', 'h3');?>
		<table class="table100">
			<?php if ($this->settings['use_sample_post'] == 'y'): ?>
				<tr>
					<td colspan="2"></td>
					<td><?php echo text_output($sample_post_msg, 'p', 'font80 bold gray');?></td>
				</tr>
				<tr>
					<td class="cell_label"><?php echo text_output(ucwords($this->lang->line('labels_join_sample_post')), '');?></td>
					<td class="cell_spacer"></td>
					<td><?php echo form_textarea($inputs['sample_post']);?></td>
				</tr>
			<?php endif; ?>
		</table><br />
	<?php endif; ?>
	
	<?php echo form_hidden('submit', 'y');?>
	<p><?php echo form_button($button_submit);?></p>
<?php echo form_close();?>