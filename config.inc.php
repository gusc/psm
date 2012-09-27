<?php
/*

Copyright (C) 2012 Gusts Kaksis <gusts.kaksis@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), 
to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR 
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, 
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

/**
* Main configuration file
* Configuration parameters can be uncomented to prevent login form from prompting these fields to user
* You can set up single database, single user access without a login prompt 
* 	or single-to-many database server management console
* 
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
*/

/**
* Database server hostname/IP
*/
define('PSM_DB_HOST', '127.0.0.1');
/**
* Database server port
*/
define('PSM_DB_PORT', '5432');
/**
* Database name
*/
//define('PSM_DB_NAME', '');
/**
* Database username
*/
//define('PSM_DB_USER', '');
/**
* Database password
*/
//define('PSM_DB_PASS', '');

/**
* Server side path to main entry directory of PostgreSQL manager
*/
//define('PSM_PATH', '/var/www/psm/htdocs/');
/**
* URL of PostgreSQL manager - this is used by router, please, be careful
* where ever you place this code, this URL should point to the entry directory where index.php file resides
*/
define('PSM_URL', '/');
/**
* Location of pg_dump executable including pg_dump itself
*/
define('PSM_DB_DUMP_CMD', '/usr/bin/pg_dump');
/**
* Location of psql executable including psql itself
*/
define('PSM_DB_RESTORE_CMD', '/usr/bin/psql');
?>