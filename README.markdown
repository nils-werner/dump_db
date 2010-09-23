# Dump DB #

This extension exports your Symphony database

- Version: 1.04
- Date: 23nd September 2010
- Requirements: Symphony 2.0.7 or above
- Author: Nils Werner, nils.werner@gmail.com
- Constributors: [A list of contributors can be found in the commit history](http://github.com/phoque/dump_db/commits/master)
- GitHub Repository: <http://github.com/phoque/dump_db>

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

## Config

Path lets you define a destination other than `/workspace`, i.e. outside your publicly accessible directories. Please make sure that destination is writeable by PHP. The path is relative to the constant `DOCROOT`, it must begin with a slash and must not end with one.
  
Format lets you define a custom file naming scheme. `%1$s` is the placeholder for the hash, `%2$s` the placeholder for the timestamp. You will see the final filename before running the dump in the backend.
  
The default path is `/workspace`, the default format is `dump-%1$s.sql`
  
For example (this will place the file outside your installation-directory):
  
		###### DUMP_DB ######
		'dump_db' => array(
			'hash' => '9081f7300b82a135e0c5efa21b00cf1c',
			'format' => 'dump-%1$s.sql',
			'path' => '../sql'
		),
		########
		
These config-parameters enable you to:
  
 - Share the hash with your collaborators. That way everybody will commit to the same file.
 - Append a timestamp to your filenames. That way you will be able to go back to older versions of the database.
 - Move the file outside your publicly accessible directories.
  
Any mixture of the options above is possible.