New Member Extension
====================
Developer: Donald Davis<br />
Email: <ddavis34@gmail.com>

Based from the UCIP Join Form developed by Dustin Lennon. This application is developed under the 
licenses of Nova and CodeIgniter.

Install Instructions
--------------------
The following application will alter your join form identify new members to your group. To install this
application you need to perform the following steps.

1. Use the included new_member.sql file to add the needed sql table fields to your nova installation.

2. Open [your domain]/application/controllers/characters.php controller, and copy the entire function segment into the one in your domain. For your convenience, the function begins and ends with

```
	/******************/
	/**** JOIN MOD ****/
	/******************/
```

3. Open [your domain]/application/controllers/main.php controller, and copy the entire function segment into the one in your domain. For your convenience, the function begins and ends with

```
	/******************/
	/**** JOIN MOD ****/
	/******************/
```

4. Open [your domain]/application/controllers/personnel.php controller, and copy the entire function segment into the one in your domain. For your convenience, the function begins and ends with

```
	/******************/
	/**** JOIN MOD ****/
	/******************/
```

5. Upload application/language/english/join_lang.php to your [your domain]/application/language/english folder of your Nova install. Translate this page into other languages and upload
them to the appropriate language directories. (If you would like your language included into a future release, please contact me via email.)

6. Add the following line into [your domain]/application/language/[your language]/app_lang.php after the rest of the includes and before the Global items.

	`/* include New Member Join Language file */`<br />
	`include_once APPPATH .'language/'. $language . '/join_lang.php';`

7. Upload application/models/characters_model.php to your application/models folder of your Nova install.

8. Upload the view folder. PLEASE NOTE: If any of these files:

```
application/views/_base_override/admin/pages/characters_bio.php
application/views/_base_override/emails/html/main_join_gm.php
application/views/_base_override/emails/text/main_join_gm.php
application/views/_base_override/main/pages/main_join_2.php
application/views/_base_override/main/pages/personnel_character.php
application/views/_base_override/main/pages/main_join_js.php
```

Were changed in your application/views/ folder, you will need to skip the step. DO NOT upload the view files if they were previously modified, unless the modification was ONLY for this particular mod. If you upload the files, you will override whatever previous modification you already had. 

If the files were only updated by this mod (Join Form) then feel free to re-upload them both without worry.

### NOTE
If you already edited these files for another mod, you will have to be careful manually managing this extension into the existing files. I only recommend you do that if YOU REALLY KNOW WHAT YOU'RE DOING!