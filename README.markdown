# Dump DB #

This extension exports your Symphony database

- Version: 1.07
- Date: 10th December 2010
- Requirements: Symphony 2.0.7 or above
- Author: Nils Werner, nils.werner@gmail.com
- Constributors: [A list of contributors can be found in the commit history](http://github.com/nils-werner/dump_db/commits/master)
- GitHub Repository: <http://github.com/nils-werner/dump_db>

## Synopsis

This extension will create a downloadable copy of your Symphony CMS database. Please note that it will dump the complete database, not caring about sensitive data whatsoever.

## Installation & Updating

Information about [installing and updating extensions](http://symphony-cms.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://symphony-cms.com/learn/>.

## Change Log

**Version 1.00**

- Initial release. Mainly a copy of Alistairs Export Ensemble.

**Version 1.01**

- Prevented dumping data of `cache` and `sessions` tables.

**Version 1.02**

- Dump is now being saved into /workspace/dump.sql instead of offered for download.

**Version 1.03**

- Dump-file will now have a random hash in its filename for security reasons.

**Version 1.04**

- New Config parameters `path` and `format`.

**Version 1.05**

- New function to restore database from dump.
  Be advised that this should never be used in a production environment as the procedure to extract the queries from the dump may be prone to errors.
  To enable this feature you need to activate it manually in your config file.
  
**Version 1.06**

- Extension will now dump and restore both data and authors into two seperate files.

**Version 1.07**

- Extension will now show a notification on every backend page if one of the dump files is newer than your database.
  Also, it will show a notification and disable the "Dump"-buttons if one of the files isn't writeable.

## Config

Path lets you define a destination other than `/workspace`, i.e. outside your publicly accessible directories. Please make sure that destination is writeable by PHP. The path is relative to the constant `DOCROOT`, it must begin with a slash and must not end with one.
  
Format lets you define a custom file naming scheme. `%1$s` is the placeholder for the mode (authors/data) `%2$s` is the placeholder for the hash, `%3$s` the placeholder for the timestamp. You will see the final filename before running the dump in the backend.
  
The default path is `/workspace`, the default format is `%1$s-%2$s.sql`
  
For example (this will place the file outside your installation-directory):
  
		###### DUMP_DB ######
		'dump_db' => array(
			'hash' => '9081f7300b82a135e0c5efa21b00cf1c',
			'format' => '%1$s-%2$s.sql',
			'path' => '/../sql'
		),
		########
		
These config-parameters enable you to:
  
 - Share the hash with your collaborators. That way everybody will commit to the same file.
 - Append a timestamp to your filenames. That way you will be able to go back to older versions of the database.
 - Move the file outside your publicly accessible directories.
  
Any mixture of the options above is possible.

To enable the restore-feature you need to do so manually using the following line in your array:

		'restore' => 'yes'

## Database downloads

In some occasions you don't want DumpDB to save your database into the files on your server (i.e. a server that can only pull from your repositories). For these cases you can provide an option like

		'dump' => 'download'

or

		'dump' => 'text'

This will either force your browser to `download` the dump or display it as `text` in your browser window without touching the files on your server. An additional help-text will be displayed showing that you're in one of these two modes.
