<html>
	<head>
		<style type="text/css">
			body {
				font-family: "lucida grande", verdana, sans-serif;
				font-size: 12px;
			}
		</style>
	</head>
	<body>
		On <?php echo date('F jS, Y') . ' at ' . date('g:i A T'); ?> the following data was submitted from 
		<?php echo anchor('main/join'); ?> by <?php echo $this->input->ip_address(); ?>.<br /><br />

		<h3>{basic_title}</h3>
		{user}
			<p>
				<strong>{label}</strong><br />
				{data}
			<p>
		{/user}

		{character}
			<p>
				<strong>{label}</strong><br />
				{data}
			<p>
		{/character}
	</body>
</html>