Nova UCIP Join Form
===================
Developer: Dustin Lennon<br />
Email: <demonicpagan@gmail.com>

This application is developed under the licenses of Nova and CodeIgniter.

Install Instructions
--------------------
The following application will alter your join form for use with the UCIP organization. To install this
application you need to perform the following steps.

1. Use the included nova_ucip_join.sql file to add the needed sql table fields to your nova installation.

2. Log into your Nova installation.

3. Goto Site Management > Settings.

4. Click "Manage User-Created Settings &raquo;"

5. Click "Add User-Created Setting"

6. Fill in the Label text box and Setting Key text box.
   Example (the setting key in this example is what is used in main.php):
     Label: Academy Instructor List
	 Setting Key: academy_instructor (this is only an example, make this whatever you want, just remember it for
	 use in main.php)

7. Upload application/controllers/characters.php to your application/controllers folder of your Nova install 
replacing the existing one if you haven't already modified this file. If you already have changes in this file, 
it's best that you just take the contents of this file and add it into your existing characters.php file.

8. Upload application/controllers/main.php to your application/controllers folder of your Nova install replacing 
the existing one if you haven't already modified this file. If you already have changes in this file, it's best 
that you just take the contents of this file and add it into your existing main.php file.

9. Upload application/controllers/personnel.php to your application/controllers folder of your Nova install replacing 
the existing one if you haven't already modified this file. If you already have changes in this file, it's best 
that you just take the contents of this file and add it into your existing personnel.php file.

10. Add the following line into your app_lang.php for your associated language(s) after the rest of the includes 
and before the Global items.

	`/* include UCIP Join Language file */`<br />
	`include_once APPPATH .'language/'. $language . '/ucip_lang.php';`

11. Upload application/language/english/ucip_lang.php to your 
application/views/language/english folder of your Nova install. Translate this page into other languages and upload
them to the appropriate language directories. (If you would like your language included into a future release, 
please contact me via email.)

12. Upload application/views/_base_override/main/pages/main_join_2.php to your
application/views/_base_override/main/pages folder of your Nova install.

13. Upload application/views/_base_override/main/pages/personnel_character.php to your
application/views/_base_override/main/pages folder of your Nova install.

14. Upload application/views/_base_override/main/js/main_join_js.php to your
application/views/_base_override/main/js folder of your Nova install.

15. Upload application/views/_base_override/admin/pages/characters_bio.php to your
application/views/_base_override/admin/pages folder of your Nova install.

16. Upload application/models/characters_model.php to your application/models folder of your Nova install.

If you experience any issues please submit a bug report on <http://github.com/demonicpagan/UCIP-Join-Form/issues>.

You can always get the latest source from <http://github.com/demonicpagan/UCIP-Join-Form> as well.

Changelog - Dates are in Epoch time
-----------------------------------
1272516204: 

*	Created a more readable README for GitHub.

1271470705:

*	Fixed SQL statements, left out a simple ;. Fixed jQuery for the UCIP member, box was displaying under
wrong selection.

1270024633:

*	Had the answer to New to UCIP question backwards. If answered "Yes" it showed the DBID box. Should have
been the other way around.

1270022374:

*	Forgot to notify UCIP academy of new cadets. Thanks to Wolf (KDFSnet) for reminding me of this.

1269985580:

*	Added jQuery hide/show for the UCIP DBID field on the join form. When yes is selected, that field will
show. When no is selected, the field will hide.
*	Updated README with the proper URLS for the project and issue reporting and included where to put the 
needed js for latest addition.

1269939186:

*	Handled email notification sent to GMs to show if member is a part of UCIP and their DBID. Had to write
a couple db queries in characters_model.php for this. Finished the view alterations.

1269801531:

*	Fully started work on altering the join form for UCIP. Included with this will be the needed email and
bio adjustments. All of this is still in development and not ready for release. If you do use it, you
will find things broken and incomplete.

1244254514:

*	Initial submission to SVN repository.