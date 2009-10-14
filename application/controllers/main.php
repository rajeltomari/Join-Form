<?php
/*
|---------------------------------------------------------------
| MAIN CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/main.php
| System Version: 1.0
|
| Controller that handles the MAIN section of the system.
|
*/

require_once APPPATH . 'controllers/base/main_base.php';

class Main extends Main_base {
	
	function Main()
	{
		parent::Main_base();
	}

	function join()
	{
		/* load the models */
		$this->load->model('positions_model', 'pos');
		$this->load->model('depts_model', 'dept');

		/* set the variables */
		$agree = $this->input->post('agree', TRUE);
		$submit = $this->input->post('submit', TRUE);
		$selected_pos = $this->input->post('position', TRUE);

		$data['selected_position'] = (is_numeric($selected_pos) && $selected_pos > 0) ? $selected_pos : 0;
		$desc = $this->pos->get_position_details($data['selected_position']);
		$data['pos_desc'] = ($desc !== FALSE) ? $desc->pos_desc : FALSE;

		if ($submit != FALSE)
		{
			/* player POST variables */
			$email = $this->input->post('email', TRUE);
			$real_name = $this->input->post('name',TRUE);
			$im = $this->input->post('instant_message', TRUE);
			$dob = $this->input->post('date_of_birth', TRUE);
			$password = $this->input->post('password', TRUE);
			$ucip = $this->input->post('ucip', TRUE);
			$ucip_dbid = $this->input->post('ucip_dbid', TRUE);

			/* character POST variables */
			$first_name = $this->input->post('first_name',TRUE);
			$middle_name = $this->input->post('middle_name', TRUE);
			$last_name = $this->input->post('last_name', TRUE);
			$suffix = $this->input->post('suffix',TRUE);
			$position = $this->input->post('position_1',TRUE);

			if ($position == 0 || $first_name == '')
			{
				$message = sprintf(
					$this->lang->line('flash_empty_fields'),
					$this->lang->line('flash_fields_join'),
					$this->lang->line('actions_submit'),
					$this->lang->line('flash_item_join')
				);

				$flash['status'] = 'red';
				$flash['message'] = text_output($message);
			}
			else
			{
				/* load the additional models */
				$this->load->model('applications_model', 'apps');

				/* grab the player id */
				$check_player = $this->player->check_email($email);

				if ($check_player === FALSE)
				{
					/* build the players data array */
					$player_array = array(
						'name' => $real_name,
						'email' => $email,
						'password' => sha1($password),
						'instant_message' => $im,
						'date_of_birth' => $dob,
						'ucip' => $ucip,
						'ucip_dbid' => $ucip_dbid,
						'join_date' => now(),
						'status' => 'pending'
					);

					/* create the player */
					$players = $this->player->create_player($player_array);
					$player_id = $this->db->insert_id();
					$prefs = $this->player->create_player_prefs($player_id);
				}

				/* set the player id */
				$player = (!isset($player_id)) ? $check_player : $player_id;

				/* build the characters data array */
				$character_array = array(
					'player' => $player,
					'first_name' => $first_name,
					'middle_name' => $middle_name,
					'last_name' => $last_name,
					'suffix' => $suffix,
					'position_1' => $position,
					'crew_type' => 'pending'
				);

				/* create the character */
				$character = $this->char->create_character($character_array);
				$character_id = $this->db->insert_id();

				/* build the apps data array */
				$app_array = array(
					'app_email' => $email,
					'app_player_name' => $real_name,
					'app_character_name' => $first_name .' '. $middle_name .' '. $last_name .' '. $suffix,
					'app_position' => $this->pos->get_position_name($position),
					'app_date' => now()
				);

				/* create new application record */
				$apps = $this->apps->insert_application($app_array);

				foreach ($_POST as $key => $value)
				{
					if (is_numeric($key))
					{
						/* build the array */
						$array = array(
							'data_field' => $key,
							'data_char' => $character_id,
							'data_player' => $player,
							'data_value' => $value,
							'data_updated' => now()
						);

						/* insert the data */
						$this->char->create_character_data($array);
					}
				}

				if ($character < 1 && $players < 1)
				{
					$message = sprintf(
						$this->lang->line('flash_failure'),
						ucfirst($this->lang->line('flash_item_join')),
						$this->lang->line('actions_submitted'),
						$this->lang->line('flash_additional_contact_gm')
					);

					$flash['status'] = 'red';
					$flash['message'] = text_output($message);
				}
				else
				{
					$user_data = array(
						'email' => $email,
						'password' => $password,
						'name' => $real_name
					);

					/* execute the email method */
					$email_user = ($this->settings['system_email'] == 'on') ? $this->_email('join_player', $user_data) : FALSE;

					$gm_data = array(
						'email' => $email,
						'name' => $real_name,
						'id' => $character_id,
						'player' => $player
					);

					/* execute the email method */
					$email_gm = ($this->settings['system_email'] == 'on') ? $this->_email('join_gm', $gm_data) : FALSE;

					$message = sprintf(
						$this->lang->line('flash_success'),
						ucfirst($this->lang->line('flash_item_join')),
						$this->lang->line('actions_submitted'),
						''
					);

					$flash['status'] = 'green';
					$flash['message'] = text_output($message);
				}
			}

			/* write everything to the template */
			$this->template->write_view('flash_message', '_base/main/pages/flash', $flash);
		}
		elseif ($this->settings['system_email'] == 'off')
		{
			$flash['status'] = 'blue';
			$flash['message'] = lang_output('flash_system_email_off');

			/* write everything to the template */
			$this->template->write_view('flash_message', '_base/main/pages/flash', $flash);
		}

		if ($agree == FALSE && $submit == FALSE)
		{ /* if they try to come straight to the join page, make them agree */
			/* set the message */
			$data['msg'] = $this->messages_model->get_message('join_disclaimer');

			/* agree button */
			$data['button_agree'] = array(
				'type' => 'submit',
				'class' => 'button',
				'name' => 'button_agree',
				'value' => 'agree',
				'content' => ucwords($this->lang->line('button_agree'))
			);

			if ($this->uri->segment(3) != FALSE)
			{
				$data['position'] = $this->uri->segment(3);
			}

			/* figure out where the view should be coming from */
			$view_loc = view_location('main_join_1', $this->skin, 'main');
		}
		else
		{
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
									$value = FALSE;
									$values = FALSE;
									$input = FALSE;

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

			/* figure out where the view should be coming from */
			$view_loc = view_location('main_join_2', $this->skin, 'main');

			/* submit button */
			$data['button_submit'] = array(
				'type' => 'submit',
				'class' => 'button',
				'name' => 'submit',
				'value' => 'submit',
				'content' => ucwords($this->lang->line('button_submit'))
			);

			/* inputs */
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
				'dbid' => array(
					'name' => 'ucip_dbid',
					'id' => 'ucip_dbid'),
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
					'rows' => 14),
			);

			/* UCIP Selection - Yes? No? */
			$data['drop_down'] = array(
				'ucip' => array(
					'' => ucwords($this->lang->line('actions_choose_one')),
					'y' => 'Yes - I am a new member of UCIP',
					'n' => 'No - I am an existing/returning member of UCIP'),
			);

			/* get the sample post question */
			$data['sample_post_msg'] = $this->messages_model->get_message('join_post');
		}

		$data['header'] = $this->messages_model->get_message('main_join_title');

		$data['loading'] = array(
			'src' => img_location('loading-circle.gif', $this->skin, 'admin'),
			'alt' => $this->lang->line('actions_loading'),
			'class' => 'image'
		);

		$js_loc = js_location('main_join_js', $this->skin, 'main');

		/* write the data to the template */
		$this->template->write('title', $this->messages_model->get_message('main_join_title'));
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);

		/* render the template */
		$this->template->render();
	}

	function _email($type = '', $data = '')
	{
		/* load the libraries */
		$this->load->library('email');
		$this->load->library('parser');

		/* define the variables */
		$email = FALSE;

		switch ($type)
		{
			case 'contact':
				/* set the email data */
				$email_data = array(
					'email_subject' => $data['subject'],
					'email_from' => ucfirst($this->lang->line('labels_from')) .': '. $data['name'] .' - '. $data['email'],
					'email_content' => nl2br($data['message'])
				);

				/* where should the email be coming from */
				$em_loc = email_location('main_contact', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				switch ($data['to'])
				{ /* figure out who the emails are going to */
					case 1:
						/* get the game masters */
						$gm = $this->player->get_gm_emails();

						/* set the TO variable */
						$to = implode(',', $gm);

						break;

					case 2:
						/* get the command staff */	
						$command = $this->player->get_command_staff_emails();

						/* set the TO variable */
						$to = implode(',', $command);

						break;

					case 3:
						/* get the webmasters */
						$webmaster = $this->player->get_webmasters_emails();

						/* set the TO variable */
						$to = implode(',', $webmaster);

						break;
				}

				/* set the parameters for sending the email */
				$this->email->from($data['email'], $data['name']);
				$this->email->to($to);
				$this->email->subject($this->settings['email_subject'] .' '. $data['subject']);
				$this->email->message($message);

				break;

			case 'news_comment':
				/* load the models */
				$this->load->model('news_model', 'news');

				/* run the methods */
				$news = $this->news->get_news_item($data['news_item']);
				$row = $news->row();
				$name = $this->char->get_character_name($data['author']);
				$from = $this->player->get_email_address('character', $data['author']);
				$to = $this->player->get_email_address('character', $row->news_author);

				/* set the content */
				$content = sprintf(
					$this->lang->line('email_content_news_comment_added'),
					"<strong>". $row->news_title ."</strong>",
					$data['comment']
				);

				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => $this->lang->line('email_subject_news_comment_added'),
					'email_from' => ucfirst($this->lang->line('labels_from')) .': '. $name .' - '. $from,
					'email_content' => nl2br($content)
				);

				/* where should the email be coming from */
				$em_loc = email_location('main_news_comment', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* set the parameters for sending the email */
				$this->email->from($from, $name);
				$this->email->to($to);
				$this->email->subject($this->settings['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);

				break;

			case 'news_comment_pending':
				/* load the models */
				$this->load->model('news_model', 'news');

				/* run the methods */
				$news = $this->news->get_news_item($data['news_item']);
				$row = $news->row();
				$name = $this->char->get_character_name($data['author']);
				$from = $this->player->get_email_address('character', $data['author']);
				$to = implode(',', $this->player->get_emails_with_access('manage/comments', 2));

				/* set the content */
				$content = sprintf(
					$this->lang->line('email_content_comment_pending'),
					$this->lang->line('labels_news_items'),
					"<strong>". $row->news_title ."</strong>",
					$data['comment'],
					site_url('login/index')
				);

				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => $this->lang->line('email_subject_comment_pending'),
					'email_from' => ucfirst($this->lang->line('labels_from')) .': '. $name .' - '. $from,
					'email_content' => nl2br($content)
				);

				/* where should the email be coming from */
				$em_loc = email_location('comment_pending', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* set the parameters for sending the email */
				$this->email->from($from, $name);
				$this->email->to($to);
				$this->email->subject($this->settings['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);

				break;

			case 'join_player':
				/* set the content */
				$content = sprintf(
					$this->lang->line('email_content_join_player'),
					$this->settings['sim_name'],
					$data['email'],
					$data['password']
				);

				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => $this->lang->line('email_subject_join_player'),
					'email_from' => ucfirst($this->lang->line('labels_from')) .': '. $this->settings['default_email_name'] .' - '. $this->settings['default_email_address'],
					'email_content' => nl2br($content)
				);

				/* where should the email be coming from */
				$em_loc = email_location('main_join_player', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* set the parameters for sending the email */
				$this->email->from($this->settings['default_email_address'], $this->settings['default_email_name']);
				$this->email->to($data['email']);
				$this->email->subject($this->settings['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);

				break;

			case 'join_gm':
				/* load the models */
				$this->load->model('positions_model', 'pos');

				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => $this->lang->line('email_subject_join_gm'),
					'email_from' => ucfirst($this->lang->line('labels_from')) .': '. $data['name'] .' - '. $data['email'],
					'email_content' => nl2br($this->lang->line('email_content_join_gm'))
				);

				$email_data['basic_title'] = $this->lang->line('tabs_player_basic');

				/* build the player data array */
				$player_data = $this->player->get_user_details($data['player']);
				$p_data = $player_data->row();

				$email_data['player'] = array(
					array(
						'label' => $this->lang->line('labels_playerbio_name'),
						'data' => $data['name']),
					array(
						'label' => $this->lang->line('labels_playerbio_email'),
						'data' => $data['email']),
					array(
						'label' => $this->lang->line('labels_playerbio_dob'),
						'data' => $p_data->date_of_birth)
				);

				/* build the character data array */
				$character_data = $this->char->get_character_info($data['id']);
				$c_data = $character_data->row();

				$email_data['character'] = array(
					array(
						'label' => $this->lang->line('labels_join_name'),
						'data' => $this->char->get_character_name($data['id'])),
					array(
						'label' => ucfirst($this->lang->line('labels_position')),
						'data' => $this->pos->get_position_name($c_data->position_1)),
				);

				/* get the sections */
				$sections = $this->char->get_bio_sections();

				if ($sections->num_rows() > 0)
				{
					foreach ($sections->result() as $sec)
					{ /* drop the section name in */
						$email_data['sections'][$sec->section_id]['title'] = $sec->section_name;

						/* get the section fields */
						$fields = $this->char->get_bio_fields($sec->section_id);

						if ($fields->num_rows() > 0)
						{
							foreach ($fields->result() as $field)
							{ /* get the data for each field */
								$bio_data = $this->char->get_field_data($field->field_id, $data['id']);

								if ($bio_data->num_rows() > 0)
								{
									foreach ($bio_data->result() as $item)
									{ /* put the data into an array */
										$email_data['sections'][$sec->section_id]['fields'][] = array(
											'field' => $field->field_label_page,
											'data' => text_output($item->data_value, '')
										);
									}
								}
							}
						}
					}
				}

				/* where should the email be coming from */
				$em_loc = email_location('main_join_gm', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the game masters email addresses */
				$gm = $this->p->get_gm_emails();

				/* set the TO variable */
				$to = implode(',', $gm);

				/* set the parameters for sending the email */
				$this->email->from($data['email'], $data['name']);
				$this->email->to($to);
				$this->email->subject($this->settings['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);

				break;
		}

		/* send the email */
		$email = $this->email->send();

		/* return the email variable */
		return $email;
	}
}

/* End of file main.php */
/* Location: ./application/controllers/main.php */