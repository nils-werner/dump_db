# Dump DB #

This extension exports your Symphony database

- Version: 1.09
- Date: 13rd December 2011
- Requirements: Symphony 2.2 or above
- Author: Nils Werner, nils.werner@gmail.com
- Constributors: [A list of contributors can be found in the commit history](http://github.com/nils-werner/dump_db/commits/master)
- GitHub Repository: <http://github.com/nils-werner/dump_db>

## Synopsis

This extension will create a downloadable copy of your Symphony CMS database. Please note that it will dump the complete database, not caring about sensitive data whatsoever.

## Installation & Updating

Information about [installing and updating extensions](http://symphony-cms.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://symphony-cms.com/learn/>.

## Config

Path lets you define a destination other than `/workspace`, i.e. outside your publicly accessible directories. Please make sure that destination is writeable by PHP. The path is relative to the constant `DOCROOT`, it must begin with a slash and must not end with one.
  
Format lets you define a custom file naming scheme. `%1$s` is the placeholder for the mode (authors/data). You can use any other PHP function as long as you don't interfere with sprintf's formatting rules (i.e. `'%1$s-'.date('Ymd').'.sql'` can be used).
  
The default path is `/workspace`, the default format is `%1$s.sql`
  
For example (this will place the file outside your installation-directory):
  
		###### DUMP_DB ######
		'dump_db' => array(
			'hash' => '9081f7300b82a135e0c5efa21b00cf1c',
			'format' => '%1$s.sql',
			'path' => '/../sql'
		),
		########
		
These config-parameters enable you to:
  
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
