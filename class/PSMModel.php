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
* PostgreSQL Manager model class
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
*/
class PSMModel {
	private $connection = null;
	
	private $host = '';
	private $port = '';
	private $dbname = '';
	private $user = '';
	private $pass = '';
	
	private $hidden_schemas = array('pg_catalog', 'information_schema');
	
	private $errors = array();
	
	/**
	* Construct a PostgreSQL Manager model
	*/
	public function __construct(){
		if (!$this->readSession()){
			if (defined('PSM_DB_HOST')){
				$this->host = PSM_DB_HOST;
			}
			if (defined('PSM_DB_PORT')){
				$this->port = PSM_DB_PORT;
			}
			if (defined('PSM_DB_NAME')){
				$this->dbname = PSM_DB_NAME;
			}
			if (defined('PSM_DB_USER')){
				$this->user = PSM_DB_USER;
			}
			if (defined('PSM_DB_PASS')){
				$this->pass = PSM_DB_PASS;
			}
		}
		if (strlen($this->user) > 0 && strlen($this->dbname) > 0){
			$this->connect();
		}
	}
	
	/**
	* Get all available schemas
	* @return array
	*/
	public function getSchemas(){
		$q = "SELECT 
						pn.nspname AS name,
						pu.rolname AS owner,
						pg_catalog.obj_description(pn.oid, 'pg_namespace') AS comment
					FROM pg_catalog.pg_namespace pn
						LEFT JOIN pg_catalog.pg_roles pu ON (pn.nspowner = pu.oid)
					WHERE nspname NOT LIKE 'pg@_%' ESCAPE '@' AND nspname != 'information_schema'
					ORDER BY nspname";
		return $this->exec($q);
	}
	/**
	* Get all available table like objects
	* @param string $schemaname
	* @return resource
	*/
	public function getObjects($schemaname){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							c.relname AS name,
							pg_catalog.pg_get_userbyid(c.relowner) AS owner,
							(SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=c.reltablespace) AS tablespace,
							reltuples::bigint AS row_count,
							relhasindex AS has_indexes,
							relhastriggers AS has_triggers,
							relchecks AS has_keys,
							relkind AS type,
							pg_catalog.obj_description(c.oid, 'pg_class') AS comment
						FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid=c.relnamespace
						WHERE nspname='".$this->escape($schemaname)."' AND relkind IN ('r', 'v', 'S')
						ORDER BY c.relname";
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Get columns
	* @param string $schemaname
	* @param string $tablename
	* @return resource
	*/
	public function getColumns($schemaname, $tablename){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							a.attname AS name, 
							pg_catalog.format_type(a.atttypid, a.atttypmod) as type, 
							a.atttypmod AS size,
							a.attnotnull AS is_not_null,
							a.atthasdef AS has_default, 
							pg_catalog.pg_get_expr(adef.adbin, adef.adrelid, true) as default_value,
							(
								SELECT 1 FROM pg_catalog.pg_depend pd, pg_catalog.pg_class pc
								WHERE pd.objid=pc.oid AND pd.classid=pc.tableoid AND pd.refclassid=pc.tableoid AND pd.refobjid=a.attrelid AND pd.refobjsubid=a.attnum AND pd.deptype='i' AND pc.relkind='S'
							) IS NOT NULL AS is_serial, 
							pg_catalog.col_description(a.attrelid, a.attnum) AS comment
						FROM pg_catalog.pg_attribute a 
							LEFT JOIN pg_catalog.pg_attrdef adef ON a.attrelid=adef.adrelid AND a.attnum=adef.adnum
						WHERE a.attrelid = (
							SELECT oid 
							FROM pg_catalog.pg_class 
							WHERE relname='".$this->escape($tablename)."' AND relnamespace = (
								SELECT oid 
								FROM pg_catalog.pg_namespace 
								WHERE nspname = '".$this->escape($schemaname)."'
							)
						) AND a.attnum > 0 AND NOT a.attisdropped
						ORDER BY a.attnum";
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Get table indexes
	* @param string $schemaname
	* @param string $tablename
	* @return resource
	*/
	public function getIndexes($schemaname, $tablename){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							c2.relname AS name,
							i.indisprimary AS is_primary,
							i.indisunique AS is_unique,
							i.indisclustered AS is_clustered,
							pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS definition
						FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i
						WHERE c.relname = '".$this->escape($tablename)."' AND pg_catalog.pg_table_is_visible(c.oid)
							AND c.oid = i.indrelid AND i.indexrelid = c2.oid
						ORDER BY c2.relname";
			return $this->exec($q);	
		}
		return false;
	}
	/**
	* Get table constraints
	* @param string $schemaname
	* @param string $tablename
	* @return resource
	*/
	public function getConstraints($schemaname, $tablename){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							pc.conname AS name,
							pc.contype AS type, 
							CASE 
								WHEN pc.contype='u' OR pc.contype='p' THEN (
									SELECT
										indisclustered
									FROM
										pg_catalog.pg_depend pd,
										pg_catalog.pg_class pl,
										pg_catalog.pg_index pi
									WHERE
										pd.refclassid=pc.tableoid
										AND pd.refobjid=pc.oid
										AND pd.objid=pl.oid
										AND pl.oid=pi.indexrelid
								) ELSE NULL
							END AS is_clustered,
							pg_catalog.pg_get_constraintdef(pc.oid, true) AS definition
						FROM pg_catalog.pg_constraint pc
						WHERE pc.conrelid = (
							SELECT oid 
							FROM pg_catalog.pg_class 
							WHERE relname='".$this->escape($tablename)."' AND relnamespace = (
								SELECT oid 
								FROM pg_catalog.pg_namespace
								WHERE nspname='".$this->escape($schemaname)."'
							)
						)
						ORDER BY 1";
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Get table triggers
	* @param string $schemaname
	* @param string $tablename
	* @return resource
	*/
	public function getTriggers($schemaname, $tablename){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							t.tgname as name, 
							pg_catalog.pg_get_triggerdef(t.oid) AS definition,
							CASE 
								WHEN t.tgenabled = 'D' THEN FALSE 
								ELSE TRUE 
							END AS is_enabled,
							p.oid AS id,
							p.proname || ' (' || pg_catalog.oidvectortypes(p.proargtypes) || ')' AS prototype,
							ns.nspname AS namespace
						FROM pg_catalog.pg_trigger t, pg_catalog.pg_proc p, pg_catalog.pg_namespace ns
						WHERE t.tgrelid=(SELECT oid FROM pg_catalog.pg_class WHERE relname='".$this->escape($tablename)."'
							AND relnamespace=(SELECT oid FROM pg_catalog.pg_namespace WHERE nspname='".$this->escape($schemaname)."'))
							AND (
								tgconstraint=0 OR NOT EXISTS (
									SELECT 1
									FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
									WHERE d.classid = t.tableoid AND d.objid = t.oid AND d.deptype = 'i' AND c.contype = 'f'
								)
							)
							AND p.oid=t.tgfoid
							AND p.pronamespace = ns.oid";
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Get view definition
	* @param string $schemaname
	* @param string $viewname
	* @return string
	*/
	public function getViewDefinition($schemaname, $viewname){
		$definition = '';
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT pg_catalog.pg_get_viewdef(c.oid, true) AS vwdefinition
				FROM pg_catalog.pg_class c
				WHERE (c.relname = '".$this->escape($viewname)."')";
			$r = $this->exec($q);
			if ($this->count($r) > 0){
				list ($definition) = $this->fetchRow($r);
			}
		}
		return $definition;
	}
	/**
	* Get sequence data
	* @param string $schemaname
	* @param string $sequencename
	* @return array
	*/
	public function getSequence($schemaname, $sequencename){
		$data = false;
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT sequence_name, last_value, start_value, increment_by, max_value, min_value, is_cycled
						FROM ".$this->escape($schemaname).".".$this->escape($sequencename);
			$r = $this->exec($q);
			if ($this->count($r) > 0){
				list ($sequence_name, $last_value, $start_value, $increment_by, $max_value, $min_value, $is_cycled) = $this->fetchRow($r);
				$data = array(
					'name' => $sequence_name,
					'start_value' => $start_value,
					'last_value' => $last_value,
					'increment_by' => $increment_by,
					'min_value' => $min_value,
					'max_value' => $max_value,
					'is_cycled' => $is_cycled == 't'
				);
			}
		}
		return $data;
	}
	/**
	* Get a list of functions
	* @param string $schemaname
	* @return resource
	*/
	public function getFunctions($schemaname){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT 
							p.oid AS id,
							p.proname AS name,
							pl.lanname AS language,
							pg_catalog.obj_description(p.oid, 'pg_proc') AS comment,
							p.proname || ' (' || pg_catalog.oidvectortypes(p.proargtypes) || ')' AS prototype,
							CASE WHEN p.proretset THEN 'setof ' ELSE '' END || pg_catalog.format_type(p.prorettype, NULL) AS returns,
							u.usename AS owner
						FROM pg_catalog.pg_proc p
							INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace
							INNER JOIN pg_catalog.pg_language pl ON pl.oid = p.prolang
							LEFT JOIN pg_catalog.pg_user u ON u.usesysid = p.proowner
						WHERE NOT p.proisagg AND n.nspname = '".$this->escape($schemaname)."'
						ORDER BY p.proname, returns";
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Total ammount of rows in table
	* @param string $schemaname
	* @param string $tablename
	* @return integer
	*/
	public function getDataCount($schemaname, $tablename){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT count(*) FROM ".$this->escape($schemaname).".".$this->escape($tablename);
			$r = $this->exec($q);
			if ($this->count($r) > 0){
				list ($count) = $this->fetchRow($r);
				return intval($count);
			}
		}
		return 0;
	}
	/**
	* Total ammount of rows in table
	* @param string $schemaname
	* @param string $tablename
	* @return integer
	*/
	public function getData($schemaname, $tablename, $offset, $limit){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$q = "SELECT * FROM ".$this->escape($schemaname).".".$this->escape($tablename)." OFFSET ".$offset." LIMIT ".$limit;
			return $this->exec($q);
		}
		return false;
	}
	/**
	* Get server info
	* @return array
	*/
	public function getInfo(){
		if ($this->isConnected()){
			$data = pg_version($this->connection);
			return array(
				'client_version' => $data['client'],
				'client_encoding' => pg_parameter_status($this->connection, 'client_encoding'),
				'server_version' => $data['server'],
				'server_encoding' => pg_parameter_status($this->connection, 'server_encoding'),
				'date_style' => pg_parameter_status($this->connection, 'DateStyle'),
				'time_zone' => pg_parameter_status($this->connection, 'TimeZone'),
				'session_authorization' => pg_parameter_status($this->connection, 'session_authorization'),
				'is_superuser' => pg_parameter_status($this->connection, 'is_superuser')
			);
		}
		return false;
	}
	/**
	* Import sql file
	* @param string $filename - filename in tmp directory
	* @param array $options - transaction - use transaction block to prevent partial restoration
	* @return boolean
	*/
	public function import($filename, $options){
		$cmd = PSM_DB_RESTORE_DMD;
		
		//$version = array();
		//preg_match("/(\d+(?:\.\d+)?)(?:\.\d+)?.*$/", exec($cmd.' --version'), $version);
		
		if (strlen($this->host) > 0) {
			putenv('PGHOST='.$this->host);
		}
		if (strlen($this->port) > 0) {
			putenv('PGPORT='.$this->port);
		}
		putenv('PGDATABASE='.$this->dbname);
		putenv('PGUSER='.$this->user);
		putenv('PGPASSWORD='.$this->pass);
		
		if ($options['transaction']){
			$cmd .= ' -1';
		}
		$cmd .= ' -f '.escapeshellarg(PSM_PATH.'tmp/'.$filename);
		
		$output = array();
		$return = 0;
		exec($cmd, $output, $return);
		if ($return == 0){
			return true;
		}
		$this->setError('Restore command exited with: '.$return);
		return false;
	}
	/**
	* Export sql file
	* @param string $schemaname
	* @param string $tablename
	* @param array $options - data - for data to export, drop - to add drop commands
	* @return string - filename in tmp directory
	*/
	public function export($schemaname, $tablename, $options){
		if (!in_array($schemaname, $this->hidden_schemas)){
			$cmd = PSM_DB_DUMP_CMD;
			
			$version = array();
			preg_match("/(\d+(?:\.\d+)?)(?:\.\d+)?.*$/", exec($cmd.' --version'), $version);
			
			if (strlen($this->host) > 0) {
				putenv('PGHOST='.$this->host);
			}
			if (strlen($this->port) > 0) {
				putenv('PGPORT='.$this->port);
			}
			putenv('PGDATABASE='.$this->dbname);
			putenv('PGUSER='.$this->user);
			putenv('PGPASSWORD='.$this->pass);
			
			$cmd .= ' -i';
			if (floatval($version[1]) >= 8.2) {
				if (strlen($tablename) > 0){
					$cmd .= ' -t '.escapeshellarg($schemaname).'.'.escapeshellarg($tablename);
				}	else {
					$cmd .= ' -n '.escapeshellarg($schemaname);
				}
			} else {
				$cmd .= ' -n '.escapeshellarg($schemaname);
				if (strlen($tablename) > 0){
					$cmd .= ' -t '.escapeshellarg($tablename);
				}
			}
			
			switch ($options['data']){
				case 'structure':
					$cmd .= ' -s';
					if ($options['drop']){
						$cmd .= ' -c';
					}
					break;
				case 'data':
					$cmd .= ' -a --attribute-inserts';
					break;
				case 'all':
					$cmd .= ' --attribute-inserts';
					if ($options['drop']){
						$cmd .= ' -c';
					}
					break;
			}
			
			$filename = $this->dbname.'_'.$schemaname.'_';
			if (strlen($tablename) > 0){
					$filename .= $tablename.'_';
			}
			$filename .= date('YmdHi').'_'.$options['data'].'.sql';
			
			$cmd .= ' -f '.escapeshellarg(PSM_PATH.'tmp/'.$filename);
			
			
			$output = array();
			$return = 0;
			exec($cmd, $output, $return);
			var_dump($cmd);
			if ($return == 0){
				return $filename;
			}
			$this->setError('Dump command exited with: '.$return);
			if (is_file(PSM_PATH.'tmp/'.$filename)){
				unlink(PSM_PATH.'tmp/'.$filename);
			}
		}
		return false;
	}
	
	
	/**
	* Escape string
	* @param string $string
	* @return string
	*/
	public function escape($string){
		if ($this->isConnected()){
			return pg_escape_string($this->connection, $string);
		}
		return pg_escape_string($string);
	}
	/**
	* Execute query
	* @param string $query
	* @return resource - or false on error
	*/
	public function exec($query){
		if ($this->isConnected()){
			$r = @pg_query($this->connection, $query); // argh, there is no possibility to do it without @ :(
			if ($r === false){
				$this->setError(pg_last_error($this->connection));
			}
			return $r;
		} else {
			$this->setError('Not connected to any database');
		}
		return false;
	}
	/**
	* Get number of rows returned by query
	* @param resource $resource
	* @return integer
	*/
	public function count($resource){
		if ($this->isConnected()){
			if (is_resource($resource)){
				return pg_num_rows($resource);
			} else {
				$this->setError('Parameter is not a resource');
			}
		} else {
			$this->setError('Not connected to any database');
		}
		return false;
	}
	/**
	* Get column names from query resource
	* @param resource $resource
	* @param array
	*/
	public function columnNames($resource){
		$list = array();
		if (is_resource($resource)){
			for ($i = 0; $i < pg_num_fields($resource); $i ++){
				array_push($list, pg_field_name($resource, $i));
			}
		} else {
			$this->setError('Parameter is not a resource');
		}
		return $list;
	}
	/**
	* Fetch a row
	* @param resource $resource
	* @param integer $row_num
	* @return array
	*/
	public function fetchRow($resource, $row_num = 0){
		if ($this->isConnected()){
			if (is_resource($resource)){
				return pg_fetch_row($resource, $row_num);
			} else {
				$this->setError('Parameter is not a resource');
			}
		} else {
			$this->setError('Not connected to any database');
		}
		return false;
	}
	/**
	* Fetch a row as an array
	* @param resource $resource
	* @param integer $row_num
	* @return array
	*/
	public function fetch($resource, $row_num = 0){
		if ($this->isConnected()){
			if (is_resource($resource)){
				return pg_fetch_assoc($resource, $row_num);
			} else {
				$this->setError('Parameter is not a resource');
			}
		} else {
			$this->setError('Not connected to any database');
		}
		return false;
	}
	/**
	* Free up some ram
	* @param resource $resource
	* @return boolean
	*/
	public function free($resource){
		if ($this->isConnected()){
			if (is_resource($resource)){
				return pg_free_result($resource);
			} else {
				$this->setError('Parameter is not a resource');
			}
		} else {
			$this->setError('Not connected to any database');
		}
		return false;
	}
	
	/**
	* Set server connection parameters
	* @param string $host
	* @param string $port
	*/
	public function setServer($host, $port='5432'){
		$this->host = $host;
		$this->port = $port;
	}
	/**
	* Set database name
	* @param string $dbname
	*/
	public function setDatabase($dbname){
		$this->dbname = $dbname;
	}
	/**
	* Set user credentials
	* @param string $user
	* @param string $pass
	*/
	public function setUser($user, $pass=''){
		$this->user = $user;
		$this->pass = $pass;
	}
	
	/**
	* Get connection state
	*/
	public function isConnected(){
		return is_resource($this->connection);
	}
	/**
	* Open database connection
	* @return boolean - true on success
	*/
	public function connect(){
		if ($this->isConnected()){
			$this->disconnect();
		}
		$con_array = array();
		if (strlen($this->host) > 0){
			$con_array[] = 'host='.$this->host;
		}
		$con_array[] = 'dbname='.$this->dbname;
		$con_array[] = 'user='.$this->user;
		if (strlen($this->pass) > 0){
			$con_array[] = 'password='.$this->pass;
		}
		if (strlen($this->port) > 0){
			$con_array[] = 'port='.$this->port;
		}
		$this->connection = @pg_connect(implode(' ', $con_array)); // argh, there is no possibility to do it without @ :(
		if ($this->connection === false){
			$this->setError('Could not connect, try different credentials');
			$this->deleteSession();
			return false;
		}
		$this->writeSession();
		return true;
	}
	/**
	* Close database connection
	* @return void
	*/
	public function disconnect(){
		if ($this->isConnected()){
			pg_close($this->connection);
		}
		$this->deleteSession();
	}
	
	/**
	* Check weather session has been started
	* @return boolean
	*/
	public function sessionStarted(){
		if (session_id() != '') {
			return true;
		}
		return false;
	}
	/**
	* Read connection credentials from session
	* @return boolean - true if session has been started and some data could be found
	*/
	private function readSession(){
		$res = false;
		if ($this->sessionStarted()) {
			if (isset($_SESSION['psm_db_host'])){
				$this->host = $_SESSION['psm_db_host'];
				$res = true;
			}
			if (isset($_SESSION['psm_db_port'])){
				$this->port = $_SESSION['psm_db_port'];
				$res = true;
			}
			if (isset($_SESSION['psm_db_dbname'])){
				$this->dbname = $_SESSION['psm_db_dbname'];
				$res = true;
			}
			if (isset($_SESSION['psm_db_user'])){
				$this->user = $_SESSION['psm_db_user'];
				$res = true;
			}
			if (isset($_SESSION['psm_db_pass'])){
				$this->pass = $_SESSION['psm_db_pass'];
				$res = true;
			}
		}
		return $res;
	}
	/**
	* Write connection credentials to session
	* @return boolean - true if session has been started
	*/
	private function writeSession(){
		if ($this->sessionStarted()) {
			$_SESSION['psm_db_host'] = $this->host;
			$_SESSION['psm_db_port'] = $this->port;
			$_SESSION['psm_db_dbname'] = $this->dbname;
			$_SESSION['psm_db_user'] = $this->user;
			$_SESSION['psm_db_pass'] = $this->pass;
			return true;
		}
		return false;
	}
	/**
	* Delete connection credentials from session
	* @return void
	*/
	private function deleteSession(){
		if ($this->sessionStarted()) {
			if (isset($_SESSION['psm_db_host'])){
				unset($_SESSION['psm_db_host']);
			}
			if (isset($_SESSION['psm_db_port'])){
				unset($_SESSION['psm_db_port']);
			}
			if (isset($_SESSION['psm_db_dbname'])){
				unset($_SESSION['psm_db_dbname']);
			}
			if (isset($_SESSION['psm_db_user'])){
				unset($_SESSION['psm_db_user']);
			}
			if (isset($_SESSION['psm_db_pass'])){
				unset($_SESSION['psm_db_pass']);
			}
		}
	}
	
	/**
	* Store errors
	* @param string $message
	*/
	private function setError($message){
		array_push($this->errors, $message);
	}
	/**
	* Check weather there are any errors
	* @return boolean
	*/
	public function hasErrors(){
		return (count($this->errors) > 0);
	}
	/**
	* Get the list of errors
	* @return array
	*/
	public function getErrors(){
		return $this->errors;
	}
}
?>