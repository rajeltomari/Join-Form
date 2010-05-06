<?php
/*
|---------------------------------------------------------------
| ADMIN - CHARACTERS CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/characters.php
| System Version: 1.0
|
| Controller that handles the CHARACTERS section of the admin system.
|
*/

require_once APPPATH . 'controllers/base/characters_base.php';

class Characters extends Characters_base {

	function Characters()
	{
		parent::Characters_base();
	}

	function bio()
	{
		$this->auth->check_access();

		/* grab the level and character ID */
		$data['level'] = $this->auth->get_access_level();
		$data['id'] = $this->uri->segment(3, FALSE, TRUE);

		if ($data['id'] === FALSE && count($this->session->userdata('characters')) > 1)
		{
			redirect('characters/select');
		}
		elseif ($data['id'] === FALSE && count($this->session->userdata('characters')) <= 1)
		{
			$data['id'] = $this->session->userdata('main_char');
		}

		$allowed = FALSE;

		switch ($data['level'])
		{
			case 1:
				$allowed = (in_array($data['id'], $this->session->userdata('characters'))) ? TRUE : FALSE;
				break;

			case 2:
				$type = $this->char->get_character($data['id'], 'crew_type');

				if (in_array($data['id'], $this->session->userdata('characters')) || $type == 'npc')
				{
					$allowed = TRUE;
				}
				break;

			case 3:
				$allowed = TRUE;
				break;
		}

		if ($allowed === FALSE)
		{
			redirect('admin/error/1');
		}

		/* load the resources */
		$this->load->model('positions_model', 'pos');
		$this->load->model('ranks_model', 'ranks');
		$this->load->helper('directory');

		if (isset($_POST['submit']))
		{
			/* get the user ID and figure out if it should be NULL or not */
			$user = $this->char->get_character($data['id'], array('user', 'crew_type'));
			$p = (empty($user['user'])) ? NULL : $user['user'];

			foreach ($_POST as $key => $value)
			{
				if (is_numeric($key))
				{
					/* build the array */
					$array['fields'][$key] = array(
						'data_field' => $key,
						'data_char' => $data['id'],
						'data_user' => $p,
						'data_value' => $value,
						'data_updated' => now()
					);
				}
				else
				{
					$array['character'][$key] = $value;
				}
			}

			$position1_old = $array['character']['position_1_old'];
			$position2_old = $array['character']['position_2_old'];

			/* get rid of the submit button data and old position refs */
			unset($array['character']['submit']);
			unset($array['character']['position_1_old']);
			unset($array['character']['position_2_old']);

			if ($array['character']['crew_type'] == 'inactive' && $user['crew_type'] != 'inactive')
			{ /* set the deactivate date */
				$array['character']['date_deactivate'] = now();
			}

			if ($array['character']['crew_type'] != 'inactive' && $user['crew_type'] == 'inactive')
			{ /* wipe out the deactivate date if they're being reactivated */
				$array['character']['date_deactivate'] = NULL;
			}

			/* update the characters table */
			$update = $this->char->update_character($data['id'], $array['character']);

			foreach ($array['fields'] as $k => $v)
			{
				$update += $this->char->update_character_data($k, $data['id'], $v);
			}

			if ($update > 0)
			{
				$message = sprintf(
					lang('flash_success'),
					ucfirst(lang('global_character')),
					lang('actions_updated'),
					''
				);

				$flash['status'] = 'success';
				$flash['message'] = text_output($message);

				/* update the positions */
				if ($array['character']['position_1'] != $position1_old)
				{
					$posnew = $this->pos->get_position($array['character']['position_1']);
					$posold = $this->pos->get_position($position1_old);

					if ($posnew !== FALSE)
					{
						/* build the update array */
						$position_update['new'] = array('pos_open' => ($posnew->pos_open == 0) ? 0 : ($posnew->pos_open - 1));

						/* update the new position */
						$posnew_update = $this->pos->update_position($array['character']['position_1'], $position_update['new']);
					}

					if ($posold !== FALSE)
					{
						/* build the update array */
						$position_update['old'] = array('pos_open' => $posold->pos_open + 1);

						/* update the new position */
						$posold_update = $this->pos->update_position($position1_old, $position_update['old']);
					}
				}

				if ($array['character']['position_2'] != $position2_old)
				{
					$posnew = $this->pos->get_position($array['character']['position_2']);
					$posold = $this->pos->get_position($position2_old);

					if ($posnew !== FALSE)
					{
						/* build the update array */
						$position_update['new'] = array('pos_open' => ($posnew->pos_open == 0) ? 0 : ($posnew->pos_open - 1));

						/* update the new position */
						$posnew_update = $this->pos->update_position($array['character']['position_2'], $position_update['new']);
					}

					if ($posold !== FALSE)
					{
						/* build the update array */
						$position_update['old'] = array('pos_open' => $posold->pos_open + 1);

						/* update the new position */
						$posold_update = $this->pos->update_position($position2_old, $position_update['old']);
					}
				}
			}
			else
			{
				$message = sprintf(
					lang('flash_failure'),
					ucfirst(lang('global_character')),
					lang('actions_updated'),
					''
				);

				$flash['status'] = 'error';
				$flash['message'] = text_output($message);
			}

			/* write everything to the template */
			$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);
		}

		/* grab the character info */
		$char = $this->char->get_character($data['id']);

		/* grab the join fields */
		$sections = $this->char->get_bio_sections();

		if ($sections->num_rows() > 0)
		{
			foreach ($sections->result() as $sec)
			{
				$sid = $sec->section_id; /* section id */

				/* set the section name */
				$data['join'][$sid]['name'] = $sec->section_name;

				/* grab the fields for the given section */
				$fields = $this->char->get_bio_fields($sec->section_id);

				if ($fields->num_rows() > 0)
				{
					foreach ($fields->result() as $field)
					{
						$f_id = $field->field_id; /* field id */

						/* set the page label */
						$data['join'][$sid]['fields'][$f_id]['field_label'] = $field->field_label_page;

						$info = $this->char->get_field_data($field->field_id, $data['id']);
						$row = ($info->num_rows() > 0) ? $info->row() : FALSE;

						switch ($field->field_type)
						{
							case 'text':
								$input = array(
									'name' => $field->field_id,
									'id' => $field->field_fid,
									'class' => $field->field_class,
									'value' => ($row !== FALSE) ? $row->data_value : '',
								);

								$data['join'][$sid]['fields'][$f_id]['input'] = form_input($input);

								break;

							case 'textarea':
								$input = array(
									'name' => $field->field_id,
									'id' => $field->field_fid,
									'class' => $field->field_class,
									'value' => ($row !== FALSE) ? $row->data_value : '',
									'rows' => $field->field_rows
								);

								$data['join'][$sid]['fields'][$f_id]['input'] = form_textarea($input);

								break;

							case 'select':
								$value = FALSE;
								$values = FALSE;
								$input = FALSE;

								$values = $this->char->get_bio_values($field->field_id);
								$data_val = ($row !== FALSE) ? $row->data_value : '';

								if ($values->num_rows() > 0)
								{
									foreach ($values->result() as $value)
									{
										$input[$value->value_field_value] = $value->value_content;
									}
								}

								$data['join'][$sid]['fields'][$f_id]['input'] = form_dropdown($field->field_id, $input, $data_val);
								break;
						}
					}
				}
			}
		}

		$pos1 = $this->pos->get_position($char->position_1);
		$pos2 = $this->pos->get_position($char->position_2);
		$rank = $this->ranks->get_rank($char->rank);
		$rankcat = $this->ranks->get_rankcat($this->rank);

		/* inputs */
		$data['inputs'] = array(
			'first_name' => array(
				'name' => 'first_name',
				'id' => 'first_name',
				'value' => $char->first_name),
			'middle_name' => array(
				'name' => 'middle_name',
				'id' => 'middle_name',
				'value' => $char->middle_name),
			'last_name' => array(
				'name' => 'last_name',
				'id' => 'last_name',
				'value' => $char->last_name),
			'suffix' => array(
				'name' => 'suffix',
				'id' => 'suffix',
				'class' => 'medium',
				'value' => $char->suffix),
			'position1_id' => $char->position_1,
			'position2_id' => $char->position_2,
			'position1_name' => ($pos1 !== FALSE) ? $pos1->pos_name : '',
			'position2_name' => ($pos2 !== FALSE) ? $pos2->pos_name : '',
			'position1_desc' => ($pos1 !== FALSE) ? $pos1->pos_desc : '',
			'position2_desc' => ($pos2 !== FALSE) ? $pos2->pos_desc : '',
			'rank_id' => $char->rank,
			'rank_name' => $rank->rank_name,
			'rank' => array(
				'src' => rank_location($this->rank, $rank->rank_image, $rankcat->rankcat_extension),
				'alt' => $rank->rank_name,
				'class' => 'image'),
			'crew_type' => $char->crew_type,
			'ucip_dbid' => array(
					'name' => 'ucip_dbid',
					'id' => 'ucip_dbid',
					'value' => $char->ucip_dbid),
			'images' => (!empty($char->images)) ? explode(',', $char->images) : '',
		);

		$data['values']['crew_type'] = array(
			'active' => ucwords(lang('status_playing') .' '. lang('global_character')),
			'npc' => ucwords(lang('status_nonplaying') .' '. lang('global_character')),
			'inactive' => ucwords(lang('status_inactive') .' '. lang('global_character')),
			'pending' => ucwords(lang('status_pending') .' '. lang('global_character')),
		);

		$data['directory'] = array();

		$dir = $this->sys->get_uploaded_images('bio');

		if ($dir->num_rows() > 0)
		{
			foreach ($dir->result() as $d)
			{
				if ($d->upload_user == $this->session->userdata('userid'))
				{
					$data['myuploads'][$d->upload_id] = array(
						'image' => array(
							'src' => asset_location('images/characters', $d->upload_filename),
							'alt' => $d->upload_filename,
							'class' => 'image image-height-100'),
						'file' => $d->upload_filename,
						'id' => $d->upload_id
					);
				}
				else
				{
					$data['directory'][$d->upload_id] = array(
						'image' => array(
							'src' => asset_location('images/characters', $d->upload_filename),
							'alt' => $d->upload_filename,
							'class' => 'image image-height-100'),
						'file' => $d->upload_filename,
						'id' => $d->upload_id
					);
				}
			}
		}

		$data['header'] = ucwords(lang('actions_edit') .' '. lang('labels_bio')) .' - '. $this->char->get_character_name($data['id']);

		$data['image_instructions'] = sprintf(
			lang('text_image_select'),
			lang('labels_bio')
		);

		/* submit button */
		$data['button'] = array(
			'submit' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'content' => ucwords(lang('actions_submit'))),
			'use' => array(
				'type' => 'submit',
				'class' => 'button-sec add',
				'name' => 'use',
				'value' => 'use',
				'content' => ucwords(lang('actions_use') .' '. lang('labels_image'))),
			'update' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'update',
				'rel' => $data['id'],
				'content' => ucwords(lang('actions_update'))),
		);

		$data['images'] = array(
			'loading' => array(
				'src' => img_location('loading-circle.gif', $this->skin, 'admin'),
				'alt' => lang('actions_loading'),
				'class' => 'image'),
			'upload' => array(
				'src' => img_location('image-upload.png', $this->skin, 'admin'),
				'alt' => lang('actions_upload'),
				'class' => 'image'),
		);

		$data['label'] = array(
			'character' => ucfirst(lang('global_character')),
			'fname' => ucwords(lang('order_first') .' '. lang('labels_name')),
			'images' => ucfirst(lang('labels_images')),
			'info' => ucfirst(lang('labels_info')),
			'lname' => ucwords(lang('order_last') .' '. lang('labels_name')),
			'mname' => ucwords(lang('order_middle') .' '. lang('labels_name')),
			'myuploads' => ucwords(lang('labels_my') .' '. lang('labels_uploads')),
			'other' => ucfirst(lang('labels_other')),
			'position1' => ucwords(lang('order_first') .' '. lang('global_position')),
			'position2' => ucwords(lang('order_second') .' '. lang('global_position')),
			'rank' => ucfirst(lang('global_rank')),
			'suffix' => ucfirst(lang('labels_suffix')),
			'type' => ucwords(lang('global_character') .' '. lang('labels_type')),
			'type_active' => ucwords(lang('status_active') .' '. lang('global_characters')),
			'type_inactive' => ucwords(lang('status_inactive') .' '. lang('global_characters')),
			'type_npc' => ucwords(lang('status_nonplaying') .' '. lang('global_characters')),
			'upload' => ucwords(lang('actions_upload') .' '. lang('labels_images') .' '. RARROW),
			'ucip_dbid' => ucfirst(lang('ucip_dbid')),
		);

		$js_data['rankloc'] = $this->rank;
		$js_data['id'] = $data['id'];

		/* figure out where the view should be coming from */
		$view_loc = view_location('characters_bio', $this->skin, 'admin');
		$js_loc = js_location('characters_bio_js', $this->skin, 'admin');

		/* write the data to the template */
		$this->template->write('title', $data['header']);
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc, $js_data);

		/* render the template */
		$this->template->render();
	}
}

/* End of file characters.php */
/* Location: ./application/controllers/characters.php */