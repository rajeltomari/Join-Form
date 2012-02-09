<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/controllers/nova_main.php';

class Main extends Nova_main {

	public function __construct()
	{
		parent::__construct();
	}

	public function join()
	{
		$this->load->model('positions_model', 'pos');
		$this->load->model('depts_model', 'dept');
		$this->load->model('ranks_model', 'ranks');
		$this->load->helper('utility');

		$agree = $this->input->post('agree', true);
		$submit = $this->input->post('submit', true);
		$selected_pos = $this->input->post('position', true);
		
		$data['selected_position'] = (is_numeric($selected_pos) and $selected_pos > 0) ? $selected_pos : 0;
		$desc = $this->pos->get_position($data['selected_position'], 'pos_desc');
		$data['pos_desc'] = ($desc !== false) ? $desc : false;

		if ($submit !== false)
		{
			// user POST variables
			$email = $this->input->post('email', true);
			$real_name = $this->input->post('name',true);
			$im = $this->input->post('instant_message', true);
			$dob = $this->input->post('date_of_birth', true);
			$password = $this->input->post('password', true);

			// character POST variables
			$first_name = $this->input->post('first_name',true);
			$middle_name = $this->input->post('middle_name', true);
			$last_name = $this->input->post('last_name', true);
			$suffix = $this->input->post('suffix',true);
			$position = $this->input->post('position_1',true);
			$ucip_member = $this->input->post('ucip_member',true);
			$ucip_dbid = $this->input->post('ucip_dbid',true);

			if ($position == 0 or $first_name == '' or empty($password) or empty($email))
			{
				$message = sprintf(
					lang('flash_empty_fields'),
					lang('flash_fields_join'),
					lang('actions_submit'),
					lang('actions_join') .' '. lang('actions_request')
				);

				$flash['status'] = 'error';
				$flash['message'] = text_output($message);
			}
			else
			{
				// check the ban list
				$ban['ip'] = $this->sys->get_item('bans', 'ban_ip', $this->input->ip_address());
				$ban['email'] = $this->sys->get_item('bans', 'ban_email', $email);

				if ($ban['ip'] !== false or $ban['email'] !== false)
				{
					$message = sprintf(
						lang('text_ban_join'),
						lang('global_sim'),
						lang('global_game_master')
					);

					$flash['status'] = 'error';
					$flash['message'] = text_output($message);
				}
				else
				{
					// load the additional models
					$this->load->model('applications_model', 'apps');

					// grab the user id
					$check_user = $this->user->check_email($email);

					if ($check_user === false)
					{
						// build the users data array
						$user_array = array(
							'name' => $real_name,
							'email' => $email,
							'password' => Auth::hash($password),
							'instant_message' => $im,
							'date_of_birth' => $dob,
							'join_date' => now(),
							'status' => 'pending',
							'skin_main' => $this->sys->get_skinsec_default('main'),
							'skin_admin' => $this->sys->get_skinsec_default('admin'),
							'skin_wiki' => $this->sys->get_skinsec_default('wiki'),
							'display_rank' => $this->ranks->get_rank_default(),
						);

						// create the user
						$users = $this->user->create_user($user_array);
						$user_id = $this->db->insert_id();
						$prefs = $this->user->create_user_prefs($user_id);
						$my_links = $this->sys->update_my_links($user_id);
					}

					// set the user id
					$user = ($check_user === false) ? $user_id : $check_user;

					// build the characters data array
					$character_array = array(
						'user' => $user,
						'first_name' => $first_name,
						'middle_name' => $middle_name,
						'last_name' => $last_name,
						'suffix' => $suffix,
						'position_1' => $position,
						'crew_type' => 'pending',
						'ucip_member' => $ucip_member,
						'ucip_dbid' => $ucip_dbid
					);

					// create the character
					$character = $this->char->create_character($character_array);
					$character_id = $this->db->insert_id();

					// update the main character if this is their first app
					if ($check_user === false)
					{
						$main_char = array('main_char' => $character_id);
						$update_main = $this->user->update_user($user, $main_char);
					}

					// optimize the tables
					$this->sys->optimize_table('characters');
					$this->sys->optimize_table('users');

					$name = array($first_name, $middle_name, $last_name, $suffix);

					// build the apps data array
					$app_array = array(
						'app_email' => $email,
						'app_ip' => $this->input->ip_address(),
						'app_user' => $user,
						'app_user_name' => $real_name,
						'app_character' => $character_id,
						'app_character_name' => parse_name($name),
						'app_position' => $this->pos->get_position($position, 'pos_name'),
						'app_date' => now(),
						'ucip_member' => $ucip_member,
						'ucip_dbid' => $ucip_dbid
					);

					// create new application record
					$apps = $this->apps->insert_application($app_array);

					foreach ($_POST as $key => $value)
					{
						if (is_numeric($key))
						{
							// build the array
							$array = array(
								'data_field' => $key,
								'data_char' => $character_id,
								'data_user' => $user,
								'data_value' => $value,
								'data_updated' => now()
							);

							// insert the data
							$this->char->create_character_data($array);
						}
					}

					if ($character < 1 and $users < 1)
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('actions_join') .' '. lang('actions_request')),
							lang('actions_submitted'),
							lang('flash_additional_contact_gm')
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
					else
					{
						$user_data = array(
							'email' => $email,
							'password' => $password,
							'name' => $real_name
						);

						// execute the email method
						$email_user = ($this->options['system_email'] == 'on') ? $this->_email('join_user', $user_data) : false;

						$gm_data = array(
							'email' => $email,
							'name' => $real_name,
							'id' => $character_id,
							'user' => $user,
							'sample_post' => $this->input->post('sample_post'),
							'ipaddr' => $this->input->ip_address()
						);

						// execute the email method
						$email_gm = ($this->options['system_email'] == 'on') ? $this->_email('join_gm', $gm_data) : false;

						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('actions_join') .' '. lang('actions_request')),
							lang('actions_submitted'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
				}
			}

			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'main', $flash);
		}
		elseif ($this->options['system_email'] == 'off')
		{
			$flash['status'] = 'info';
			$flash['message'] = lang_output('flash_system_email_off');

			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'main', $flash);
		}

		if ($agree == false and $submit == false)
		{
			$data['msg'] = $this->msgs->get_message('join_disclaimer');
			
			if ($this->uri->segment(3) != false)
			{
				$data['position'] = $this->uri->segment(3);
			}

			$view_loc = 'main_join_1';
		}
		else
		{
			// grab the join fields
			$sections = $this->char->get_bio_sections();

			if ($sections->num_rows() > 0)
			{
				foreach ($sections->result() as $sec)
				{
					$sid = $sec->section_id;
					
					// set the section name
					$data['join'][$sid]['name'] = $sec->section_name;

					// grab the fields for the given section
					$fields = $this->char->get_bio_fields($sec->section_id);

					if ($fields->num_rows() > 0)
					{
						foreach ($fields->result() as $field)
						{
							$f_id = $field->field_id;

							// set the page label
							$data['join'][$sid]['fields'][$f_id]['field_label'] = $field->field_label_page;

							switch ($field->field_type)
							{
								case 'text':
									$input = array(
										'name' => $field->field_id,
										'id' => $field->field_fid,
										'class' => $field->field_class,
										'value' => $field->field_value
									);

									$data['join'][$sid]['fields'][$f_id]['input'] = form_input($input);
								break;

								case 'textarea':
									$input = array(
										'name' => $field->field_id,
										'id' => $field->field_fid,
										'class' => $field->field_class,
										'value' => $field->field_value,
										'rows' => $field->field_rows
									);

									$data['join'][$sid]['fields'][$f_id]['input'] = form_textarea($input);
								break;

								case 'select':
									$value = false;
									$values = false;
									$input = false;

									$values = $this->char->get_bio_values($field->field_id);

									if ($values->num_rows() > 0)
									{
										foreach ($values->result() as $value)
										{
											$input[$value->value_field_value] = $value->value_content;
										}
									}

									$data['join'][$sid]['fields'][$f_id]['input'] = form_dropdown($field->field_id, $input);
								break;
							}
						}
					}
				}
			}

			// get the join instructions
			$data['msg'] = $this->msgs->get_message('join_instructions');

			// figure out where the view should be coming from
			$view_loc = 'main_join_2';

			// inputs
			$data['inputs'] = array(
				'name' => array(
					'name' => 'name',
					'id' => 'name'),
				'email' => array(
					'name' => 'email',
					'id' => 'email'),
				'password' => array(
					'name' => 'password',
					'id' => 'password'),
				'dob' => array(
					'name' => 'date_of_birth',
					'id' => 'date_of_birth'),
				'im' => array(
					'name' => 'instant_message',
					'id' => 'instant_message',
					'rows' => 4),
				'first_name' => array(
					'name' => 'first_name',
					'id' => 'first_name'),
				'middle_name' => array(
					'name' => 'middle_name',
					'id' => 'middle_name'),
				'last_name' => array(
					'name' => 'last_name',
					'id' => 'last_name'),
				'suffix' => array(
					'name' => 'suffix',
					'id' => 'suffix',
					'class' => 'medium'),
				'sample_post' => array(
					'name' => 'sample_post',
					'id' => 'sample_post',
					'rows' => 30),
				'ucip_dbid' => array(
					'name' => 'ucip_dbid',
					'id' => 'ucip_dbid'),
				'ucip_member_yes' => array(
					'name' => 'ucip_member',
					'id' => 'ucip_member',
					'value' => 'yes',
					'checked' => true),
				'ucip_member_no' => array(
					'name' => 'ucip_member',
					'id' => 'ucip_member',
					'value' => 'no',
					'checked' => false),
			);

			// get the sample post question
			$data['sample_post_msg'] = $this->msgs->get_message('join_post');

			$data['label'] = array(
				'user_info' => ucwords(lang('global_user') .' '. lang('labels_information')),
				'name' => ucwords(lang('labels_name')),
				'email' => ucwords(lang('labels_email_address')),
				'password' => ucwords(lang('labels_password')),
				'dob' => lang('labels_dob'),
				'im' => ucwords(lang('labels_im')),
				'im_inst' => lang('text_im_instructions'),
				'fname' => ucwords(lang('order_first') .' '. lang('labels_name')),
				'mname' => ucwords(lang('order_middle') .' '. lang('labels_name')),
				'next' => ucwords(lang('actions_next') .' '. lang('labels_step')) .' '. RARROW,
				'lname' => ucwords(lang('order_last') .' '. lang('labels_name')),
				'suffix' => ucfirst(lang('labels_suffix')),
				'position' => ucwords(lang('global_position')),
				'other' => ucfirst(lang('labels_other')),
				'samplepost' => ucwords(lang('labels_sample_post')),
				'character' => ucfirst(lang('global_character')),
				'character_info' => ucwords(lang('global_character') .' '. lang('labels_info')),
				'ucip_member' => lang('ucip_member'),
				'ucip_member_yes' => lang('ucip_member_yes'),
				'ucip_member_no' => lang('ucip_member_no'),
				'ucip_dbid' => lang('ucip_dbid'),
				'yes' => lang('labels_yes'),
				'no' => lang('labels_no'),
			);
		}

		// submit button
		$data['button'] = array(
			'submit' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'submitJoin',
				'content' => ucwords(lang('actions_submit'))),
			'next' => array(
				'type' => 'submit',
				'class' => 'button-sec',
				'name' => 'submit',
				'value' => 'submit',
				'id' => 'nextTab',
				'content' => ucwords(lang('actions_next') .' '. lang('labels_step'))),
			'agree' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'button_agree',
				'value' => 'agree',
				'content' => ucwords(lang('actions_agree')))
		);

		$data['header'] = ucfirst(lang('actions_join'));

		$data['loading'] = array(
			'src' => Location::img('loading-circle.gif', $this->skin, 'main'),
			'alt' => lang('actions_loading'),
			'class' => 'image'
		);

		$this->_regions['content'] = Location::view($view_loc, $this->skin, 'main', $data);
		$this->_regions['javascript'] = Location::js('main_join_js', $this->skin, 'main');
		$this->_regions['title'].= $data['header'];

		Template::assign($this->_regions);

		Template::render();
	}
}