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

General Info
============

* License: MIT
* Uses FamFamFam icon set from: http://www.famfamfam.com/lab/icons/silk/

Dependencies
============

* php-5.3+ with pgsql extension
* postgresql-8.0+ (I'm not sure weather it will work with PostgreSQL 7.4)

Installation
============

1. Copy files in your web root or sub directory
2. Edit config.inc.php file accordingly (it's mandatory to set PSM_URL constant)
3. Enable mod_rewrite and .htaccess file support in your HTTP server
4. Enable exec() function and grant access to pg_dump and psql commands

Use in your project
===================

Basically you can use PSMViewHtml and PSMModel classes, just set up the constants (PSM_URL, PSM_PATH) and copy the logic from index.php and PSM.php files. And when calling a view function from PSMHtmlView first parameter should be false, so it does not output full HTML source.

