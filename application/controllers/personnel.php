<?php
/*
|---------------------------------------------------------------
| PERSONNEL CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/personnel.php
| System Version: 1.0
|
| Controller that handles the PERSONNEL section of the system.
|
*/

require_once APPPATH . 'controllers/base/personnel_base.php';

class Personnel extends Personnel_base {

	function Personnel()
	{
		parent::Personnel_base();
	}

function character()
	{
		/* load the models */
		$this->load->model('ranks_model', 'ranks');
		$this->load->model('positions_model', 'pos');
		$this->load->model('posts_model', 'posts');
		$this->load->model('personallogs_model', 'logs');
		$this->load->model('awards_model', 'awards');

		/* set the variables */
		$id = $this->uri->segment(3, FALSE, TRUE);

		/* grab the character info */
		$character = $this->char->get_character($id);

		if ($character !== FALSE)
		{
			$data['postcount'] = $this->posts->count_character_posts($id);
			$data['logcount'] = $this->logs->count_character_logs($id);
			$data['awardcount'] = $this->awards->count_character_awards($id);

			/* set the name items into an array */
			$name_array = array(
				'first_name' => $character->first_name,
				'middle_name' => $character->middle_name,
				'last_name' => $character->last_name,
				'suffix' => $character->suffix
			);

			foreach ($name_array as $key => $value)
			{ /* make sure there aren't any blank items */
				if (empty($value))
				{
					unset($name_array[$key]);
				}
			}

			$name = implode(' ', $name_array);
			$rank = $this->ranks->get_rank($character->rank, 'rank_name');

			/* set the character info */
			$data['character_info'] = array(
				array(
					'label' => ucfirst(lang('labels_name')),
					'value' => $name),
				array(
					'label' => ucfirst(lang('global_position')),
					'value' => $this->pos->get_position($character->position_1, 'pos_name')),
				array(
					'label' => ucwords(lang('order_second') .' '. lang('global_position')),
					'value' => $this->pos->get_position($character->position_2, 'pos_name')),
				array(
					'label' => ucfirst(lang('global_rank')),
					'value' => $rank),
				array(
					'label' => ucfirst(lang('dbid')),
					'value' => $character->ucip_dbid),
			);

			/* set the data used by the view */
			$data['character']['id'] = $id;
			$data['character']['name'] = $name;
			$data['character']['rank'] = $character->rank;
			$data['character']['position_1'] = $character->position_1;
			$data['character']['position_2'] = $character->position_2;
			$data['character']['user'] = $character->user;
			$data['character']['ucip_dbid'] = $character->ucip_dbid;

			if ($character->images > '')
			{ /* make sure there are actually images */
				/* get the images */
				$images = explode(',', $character->images);
				$images_count = count($images);

				if (strstr($images[0], 'http://'))
				{ /* make sure it is not an external image */
					$src = $images[0];
				}
				else
				{
					$src = asset_location('images/characters', trim($images[0]));
				}

				/* set the image */
				$data['character']['image'] = array(
					'src' => $src,
					'alt' => $name,
					'class' => 'image reflect rheight20 ropacity30',
					'height' => 150
				);

				for ($i=1; $i < $images_count; $i++)
				{
					if (strstr($images[$i], 'http://'))
					{ /* make sure it is not an external image */
						$src = trim($images[$i]);
					}
					else
					{
						$src = asset_location('images/characters', trim($images[$i]));
					}

					/* build the array */
					$data['character']['image_array'][] = array(
						'src' => $src,
						'alt' => $name,
						'class' => 'image'
					);
				}
			}

			/* get the bio tabs */
			$tabs = $this->char->get_bio_tabs();

			/* get the bio sections */
			$sections = $this->char->get_bio_sections();

			if ($tabs->num_rows() > 0)
			{
				$i = 1;
				foreach ($tabs->result() as $tab)
				{
					$data['tabs'][$i]['id'] = $tab->tab_id;
					$data['tabs'][$i]['name'] = $tab->tab_name;
					$data['tabs'][$i]['link'] = $tab->tab_link_id;

					++$i;
				}
			}

			if ($sections->num_rows() > 0)
			{
				$i = 1;
				foreach ($sections->result() as $sec)
				{
					$fields = $this->char->get_bio_fields($sec->section_id);

					if ($fields->num_rows() > 0)
					{
						$j = 1;
						foreach ($fields->result() as $field)
						{
							$data['fields'][$sec->section_id][$j]['label'] = $field->field_label_page;
							$data['fields'][$sec->section_id][$j]['value'] = FALSE;

							$info = $this->char->get_field_data($field->field_id, $id);

							if ($info->num_rows() > 0)
							{
								foreach ($info->result() as $item)
								{
									$data['fields'][$sec->section_id][$j]['value'] = $item->data_value;
								}
							}

							++$j;
						}
					}

					if ($tabs->num_rows() > 0)
					{
						$data['sections'][$sec->section_tab][$i]['id'] = $sec->section_id;
						$data['sections'][$sec->section_tab][$i]['name'] = $sec->section_name;
					}
					else
					{
						$data['sections'][$i]['id'] = $sec->section_id;
						$data['sections'][$i]['name'] = $sec->section_name;
					}

					++$i;
				}
			}

			/* set the header */
			$data['header'] = ucfirst(lang('labels_biography')) .' - '. $rank .' '. $name;

			/* set the title */
			$this->template->write('title', ucfirst(lang('labels_biography')) .' - '. $name);
		}
		else
		{
			/* set the header */
			$data['header'] = lang('error_title_invalid_char');
			$data['msg_error'] = lang_output('error_msg_invalid_char');

			/* set the title */
			$this->template->write('title', lang('error_pagetitle'));
		}

		if ($this->auth->is_logged_in() === TRUE)
		{
			if ($this->auth->check_access('site/bioform', FALSE) === TRUE)
			{
				$data['edit_valid_form'] = TRUE;
			}
			else
			{
				$data['edit_valid_form'] = FALSE;
			}

			if ($this->auth->check_access('characters/bio', FALSE) === TRUE)
			{
				if ($this->auth->get_access_level('characters/bio') == 3)
				{
					$data['edit_valid'] = TRUE;
				}
				elseif ($this->auth->get_access_level('characters/bio') == 2)
				{
					$characters = $this->char->get_user_characters($this->session->userdata('userid'), '', 'array');

					if (in_array($id, $characters) || $character->crew_type == 'npc')
					{
						$data['edit_valid'] = TRUE;
					}
					else
					{
						$data['edit_valid'] = FALSE;
					}
				}
				elseif ($this->auth->get_access_level('characters/bio') == 1)
				{
					$characters = $this->char->get_user_characters($this->session->userdata('userid'), '', 'array');

					if (in_array($id, $characters))
					{
						$data['edit_valid'] = TRUE;
					}
					else
					{
						$data['edit_valid'] = FALSE;
					}
				}
				else
				{
					$data['edit_valid'] = FALSE;
				}
			}
			else
			{
				$data['edit_valid'] = FALSE;
			}
		}
		else
		{
			$data['edit_valid'] = FALSE;
			$data['edit_valid_form'] = FALSE;
		}

		$data['label'] = array(
			'edit' => '[ '. ucwords(lang('actions_edit') .' '. lang('global_character')) .' ]',
			'edit_form' => '[ '. ucwords(lang('actions_edit') .' '. lang('labels_biography') .' '. 
				lang('labels_form')) .' ]',
			'gallery' => lang('open_gallery'),
			'view_all_posts' => ucwords(lang('actions_viewall') .' '. lang('global_posts') .' '. RARROW),
			'view_all_logs' => ucwords(lang('actions_viewall') .' '. lang('global_personallogs') .' '. RARROW),
			'view_all_awards' => ucwords(lang('actions_viewall') .' '. lang('global_awards') .' '. RARROW),
			'view_user' => ucwords(lang('actions_view') .' '. lang('global_user') .' '.
				lang('labels_info') .' '. RARROW),
		);

		/* figure out where the view JS files should be coming from */
		$view_loc = view_location('personnel_character', $this->skin, 'main');
		$js_loc = js_location('personnel_character_js', $this->skin, 'main');

		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);

		/* render the template */
		$this->template->render();
	}
}

/* End of file personnel.php */
/* Location: ./application/controllers/personnel.php */