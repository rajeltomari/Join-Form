On <?php echo date('F jS, Y') . ' at ' . date('g:i A T'); ?> the following data was submitted from <?php echo anchor('main/join'); ?>
by <?php echo $this->input->ip_address(); ?>.

{email_from}

{basic_title}
{user}
	{label}
	{data}
{/user}

{character}
	{label}
	{data}
{/character}