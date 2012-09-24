<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/models/nova_characters_model.php';

class Characters_model extends Nova_characters_model {

	public function __construct()
	{
		parent::__construct();
	}

	function get_character_new_member($character = '')
	{
		$this->db->from('characters');
		$this->db->where('charid', $character);

		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			$item = $query->row();

			$array['new_member'] = $item->new_member;

			foreach ($array as $key => $value)
			{
				if (empty($value))
				{
					unset($array[$key]);
				}
			}

			$string = implode(' ', $array);
			return $string;
		}

		return FALSE;
	}
}