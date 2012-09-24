<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/controllers/nova_characters.php';

class Characters extends Nova_characters {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	*** Put your own methods below this...
	**/

	/******************/
    /**** JOIN MOD ****/
    /******************/
	public function bio($id = false)
	{
		Auth::check_access();

		$id = (is_numeric($id)) ? $id : false;
		$level = Auth::get_access_level();
		$data['level'] = $level;
		if ( ! $id and count($this->session->userdata('characters')) > 1)
		{
			redirect('characters/select');
		}
		elseif ( ! $id and count($this->session->userdata('characters')) <= 1)
		{
			$id = $this->session->userdata('main_char');
		}

		$data['id'] = $id;
		$allowed = false;
		switch ($level)
		{
			case 1:
				$allowed = (in_array($id, $this->session->userdata('characters'))) ? true : false;
			break;

			case 2:
				$type = $this->char->get_character($data['id'], 'crew_type');

				if (in_array($id, $this->session->userdata('characters')) or $type == 'npc')
				{
					$allowed = true;
				}
			break;

			case 3:
				$allowed = true;
			break;
		}

		if ( ! $allowed)
		{
			redirect('admin/error/1');
		}
		$this->load->model('positions_model', 'pos');
		$this->load->model('ranks_model', 'ranks');
		$this->load->model('access_model', 'access');
		$this->load->helper('directory');
		if (isset($_POST['submit']))
		{
			switch ($this->uri->segment(4))
			{
				default:
					$user = $this->char->get_character($id, array('user', 'crew_type'));
					$p = (empty($user['user'])) ? null : $user['user'];
					foreach ($_POST as $key => $value)
					{
						if (is_numeric($key))
						{
							// build the array
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
					unset($array['character']['submit']);

					$c = $this->char->get_character($id);
					if (($level == 2 and $c->crew_type == 'npc') or $level == 3)
					{
						$position1_old = $array['character']['position_1_old'];
						$position2_old = $array['character']['position_2_old'];
						$rank_old = $array['character']['rank_old'];

						unset($array['character']['position_1_old']);
						unset($array['character']['position_2_old']);
						unset($array['character']['rank_old']);
						if ($array['character']['rank'] != $rank_old)
						{
							$oldR = $this->ranks->get_rank($rank_old, array('rank_order', 'rank_name'));
							$newR = $this->ranks->get_rank($array['character']['rank'], array('rank_order', 'rank_name'));
							$promotion = array(
								'prom_char' => $data['id'],
								'prom_user' => $this->char->get_character($data['id'], 'user'),
								'prom_date' => now(),
								'prom_old_order' => ($oldR['rank_order'] === null) ? 0 : $oldR['rank_order'],
								'prom_old_rank' => ($oldR['rank_name'] === null) ? '' : $oldR['rank_name'],
								'prom_new_order' => ($newR['rank_order'] === null) ? 0 : $newR['rank_order'],
								'prom_new_rank' => ($newR['rank_name'] === null) ? '' : $newR['rank_name'],
							);
							$prom = $this->char->create_promotion_record($promotion);
						}

						if ($level == 3)
						{
							if ($c->crew_type == 'active')
							{
								// if we've assigned a new position, update the open slots
								if ($array['character']['position_1'] != $position1_old)
								{
									$this->pos->update_open_slots($array['character']['position_1'], 'add_crew');
									$this->pos->update_open_slots($position1_old, 'remove_crew');
								}

								// if we've assigned a new position, update the open slots
								if ($array['character']['position_2'] != $position2_old)
								{
									$this->pos->update_open_slots($array['character']['position_2'], 'add_crew');
									$this->pos->update_open_slots($position2_old, 'remove_crew');
								}
							}
						}
					}

					$update = $this->char->update_character($data['id'], $array['character']);
					foreach ($array['fields'] as $k => $v)
					{
						$update += $this->char->update_character_data($k, $data['id'], $v);
					}

					$message = sprintf(
						($update > 0) ? lang('flash_success') : lang('flash_failure'),
						ucfirst(lang('global_character')),
						lang('actions_updated'),
						''
					);
					$flash['status'] = ($update > 0) ? 'success' : 'error';
					$flash['message'] = text_output($message);
				break;
				case 'activate':
					if ($level == 3)
					{
						$user = (isset($_POST['user'])) ? $_POST['user'] : false;
						$activate = (isset($_POST['activate_user'])) ? (bool) $this->input->post('activate_user') : false;
						$primary = (isset($_POST['primary'])) ? (bool) $this->input->post('primary', true) : false;
						$c = $this->char->get_character($id);
						
						if ($activate)
						{
							$user_update_data['status'] = 'active';
							$user_update_data['leave_date'] = null;
							$user_update_data['access_role'] = Access_Model::STANDARD;
							$user_update_data['last_update'] = now();
						}
						
						if ($primary)
						{
							$user_update_data['main_char'] = $id;
							$user_update_data['last_update'] = now();
						}
						
						$character_update_data = array(
							'user' => $user,
							'crew_type' => 'active',
							'date_deactivate' => null,
						);
						
						$this->pos->update_open_slots($c->position_1, 'add_crew');
						if ($c->position_2 > 0 and $c->position_2 !== null)
						{
							$this->pos->update_open_slots($c->position_2, 'add_crew');
						}

						if (isset($user_update_data))
						{
							$update_user = $this->user->update_user($user, $user_update_data);
						}

						$update_char = $this->char->update_character($id, $character_update_data);
						$message = sprintf(
							($update_char > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_character')),
							lang('actions_activated'),
							''
						);
						$flash['status'] = ($update_char > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;
				case 'deactivate':
					if ($level == 3)
					{
						$maincharacter = (isset($_POST['main_character'])) ? $_POST['main_character'] : false;
						$deactivate = (isset($_POST['deactivate_user'])) ? (bool) $this->input->post('deactivate_user') : false;

						$c = $this->char->get_character($id);
						if ($deactivate)
						{
							$user_update_data['status'] = 'inactive';
							$user_update_data['leave_date'] = now();
							$user_update_data['access_role'] = Access_Model::INACTIVE;
							$user_update_data['last_update'] = now();
						}

						if ($maincharacter)
						{
							$user_update_data['main_char'] = $maincharacter;
							$user_update_data['last_update'] = now();
						}

						$character_update_data = array(
							'crew_type' => 'inactive',
							'date_deactivate' => now(),
						);

						$this->pos->update_open_slots($c->position_1, 'remove_crew');
						if ($c->position_2 > 0 and $c->position_2 !== null)
						{
							$this->pos->update_open_slots($c->position_2, 'remove_crew');
						}

						if (isset($user_update_data))
						{
							// update the user
							$update_user = $this->user->update_user($c->user, $user_update_data);
						}

						$update_char = $this->char->update_character($id, $character_update_data);
						$message = sprintf(
							($update_char > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_character')),
							lang('actions_deactivated'),
							''
						);
						$flash['status'] = ($update_char > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;
				case 'makenpc':
					if ($level == 3)
					{
						$maincharacter = (isset($_POST['main_character'])) ? $_POST['main_character'] : false;
						$deactivate = (isset($_POST['deactivate_user'])) ? (bool) $this->input->post('deactivate_user') : false;
						$assoc = (isset($_POST['remove_user'])) ? (bool) $this->input->post('remove_user') : false;

						$c = $this->char->get_character($id);
						if ($deactivate)
						{
							$user_update_data['status'] = 'inactive';
							$user_update_data['leave_date'] = now();
							$user_update_data['access_role'] = Access_Model::INACTIVE;
							$user_update_data['last_update'] = now();
						}

						if ($maincharacter)
						{
							$user_update_data['main_char'] = $maincharacter;
							$user_update_data['last_update'] = now();
						}

						if ($assoc)
						{
							$character_update_data['user'] = null;
							$user_update_data['main_char'] = null;
						}

						$character_update_data['crew_type'] = 'npc';
						$this->pos->update_open_slots($c->position_1, 'remove_crew');
						if ($c->position_2 > 0 and $c->position_2 !== null)
						{
							$this->pos->update_open_slots($c->position_2, 'remove_crew');
						}

						if (isset($user_update_data))
						{
							// update the user
							$update_user = $this->user->update_user($c->user, $user_update_data);
						}

						$update_char = $this->char->update_character($id, $character_update_data);
						$message = sprintf(
							($update_char > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_character')),
							lang('actions_updated'),
							''
						);
						$flash['status'] = ($update_char > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;
				case 'makeplaying':
					if ($level == 3)
					{
						$maincharacter = (isset($_POST['main_character'])) ? $_POST['main_character'] : false;
						$user = (isset($_POST['user'])) ? $this->input->post('user') : false;

						$c = $this->char->get_character($id);
						$u = $this->user->get_user($user);
						if ($u->status == 'inactive')
						{
							$user_update_data['status'] = 'active';
							$user_update_data['leave_date'] = null;
							$user_update_data['last_update'] = now();
							$user_update_data['access_role'] = Access_Model::STANDARD;
						}

						if ($maincharacter)
						{
							$user_update_data['main_char'] = $id;
							$user_update_data['last_update'] = now();
						}

						$character_update_data['crew_type'] = 'active';
						$character_update_data['user'] = $user;
						$this->pos->update_open_slots($c->position_1, 'add_crew');
						if ($c->position_2 > 0 and $c->position_2 !== null)
						{
							$this->pos->update_open_slots($c->position_2, 'add_crew');
						}

						if (isset($user_update_data))
						{
							$update_user = $this->user->update_user($user, $user_update_data);
						}

						$update_char = $this->char->update_character($id, $character_update_data);
						$message = sprintf(
							($update_char > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_character')),
							lang('actions_updated'),
							''
						);
						$flash['status'] = ($update_char > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;
			}
			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'admin', $flash);
		}
		$char = $this->char->get_character($id);
		$sections = $this->char->get_bio_sections();
		if ($sections->num_rows() > 0)
		{
			foreach ($sections->result() as $sec)
			{
				$sid = $sec->section_id;
				$data['join'][$sid]['name'] = $sec->section_name;
				$fields = $this->char->get_bio_fields($sec->section_id);
				if ($fields->num_rows() > 0)
				{
					foreach ($fields->result() as $field)
					{
						$f_id = $field->field_id;
						$data['join'][$sid]['fields'][$f_id]['field_label'] = $field->field_label_page;
						$info = $this->char->get_field_data($field->field_id, $data['id']);
						$row = ($info->num_rows() > 0) ? $info->row() : false;
						switch ($field->field_type)
						{
							case 'text':
								$input = array(
									'name' => $field->field_id,
									'id' => $field->field_fid,
									'class' => $field->field_class,
									'value' => ($row !== false) ? $row->data_value : '',
								);
								$data['join'][$sid]['fields'][$f_id]['input'] = form_input($input);
							break;
							case 'textarea':
								$input = array(
									'name' => $field->field_id,
									'id' => $field->field_fid,
									'class' => $field->field_class,
									'value' => ($row !== false) ? $row->data_value : '',
									'rows' => $field->field_rows
								);
								$data['join'][$sid]['fields'][$f_id]['input'] = form_textarea($input);
							break;
							case 'select':
								$value = false;
								$values = false;
								$input = false;
								$values = $this->char->get_bio_values($field->field_id);
								$data_val = ($row !== false) ? $row->data_value : '';
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
			'position1_name' => ($pos1 !== false) ? $pos1->pos_name : '',
			'position2_name' => ($pos2 !== false) ? $pos2->pos_name : '',
			'position1_desc' => ($pos1 !== false) ? $pos1->pos_desc : '',
			'position2_desc' => ($pos2 !== false) ? $pos2->pos_desc : '',
			'rank_id' => $char->rank,
			'rank_name' => ($rank !== false) ? $rank->rank_name : '',
			'rank' => array(
				'src' => ($rank !== false) ? Location::rank($this->rank, $rank->rank_image, $rankcat->rankcat_extension) : '',
				'alt' => ($rank !== false) ? $rank->rank_name : '',
				'class' => 'image'),
			'crew_type' => $char->crew_type,
			'images' => ( ! empty($char->images)) ? explode(',', $char->images) : '',
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
							'src' => Location::asset('images/characters', $d->upload_filename),
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
							'src' => Location::asset('images/characters', $d->upload_filename),
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
			'activate' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'char-activate',
				'myid' => $id,
				'content' => ucwords(lang('actions_activate').' '.lang('global_character'))),
			'deactivate' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'char-deactivate',
				'myid' => $id,
				'content' => ucwords(lang('actions_deactivate').' '.lang('global_character'))),
			'npc' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'char-npc',
				'myid' => $id,
				'content' => ucwords(lang('actions_make').' '.strtoupper(lang('abbr_npc')))),
			'playing' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'char-playingchar',
				'myid' => $id,
				'content' => ucwords(lang('actions_make').' '.lang('status_playing').' '.lang('global_character'))),
		);

		$data['images'] = array(
			'loading' => array(
				'src' => Location::img('loading-circle.gif', $this->skin, 'admin'),
				'alt' => lang('actions_loading'),
				'class' => 'image'),
			'upload' => array(
				'src' => Location::img('image-upload.png', $this->skin, 'admin'),
				'alt' => lang('actions_upload'),
				'class' => 'image'),
			'loader' => array(
				'src' => Location::img('loading-bar.gif', $this->skin, 'admin'),
				'alt' => lang('actions_loading'),
				'class' => 'image'),
		);

		$data['label'] = array(
			'character' => ucfirst(lang('global_character')),
			'fname' => ucwords(lang('order_first') .' '. lang('labels_name')),
			'images' => ucfirst(lang('labels_images')),
			'info' => ucfirst(lang('labels_info')),
			'loading' => ucfirst(lang('actions_loading')) .'...',
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
			'change' => ucwords(lang('actions_change').' '.lang('global_character').' '.lang('labels_status')),
			'available_images' => ucwords(lang('labels_available').' '.lang('labels_images')),
			'character_images' => ucwords(lang('global_character').' '.lang('labels_images')),
		);

		$js_data['rankloc'] = $this->rank;
		$js_data['id'] = $data['id'];

		$this->_regions['content'] = Location::view('characters_bio', $this->skin, 'admin', $data);
		$this->_regions['javascript'] = Location::js('characters_bio_js', $this->skin, 'admin', $js_data);
		$this->_regions['title'].= $data['header'];
		
		Template::assign($this->_regions);
		Template::render();
	}
	/******************/
    /**** JOIN MOD ****/
    /******************/
}