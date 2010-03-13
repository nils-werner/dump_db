# Dump DB #

This extension exports your Symphony database

- Version: 1.01
- Date: 13th Mar 2010
- Requirements: Symphony 2.0.7RC2 or above
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