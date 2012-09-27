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

// Helper classes
include(PSM_PATH.'class/UIForms.php');
include(PSM_PATH.'class/UITableForms.php');
include(PSM_PATH.'class/UIPages.php');

/**
* HTML view class
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
*/
class PSMViewHtml extends PSMView {
	/**
	* Constraint type cast
	* PostgreSQL values to readable values
	* @var array
	*/
	private $constraint_types = array(
		'p' => 'primary',
		'f' => 'foreign'
	);
	/**
	* Object type cast
	* PostgreSQL values to readable values
	* @var array
	*/
	private $object_types = array(
		'r' => 'table',
		'v' => 'view',
		'S' => 'sequence',
		'i' => 'index',
		'c' => 'composite',
		't' => 'toast'
	);
	/**
	* Number of rows to display per-page
	* @var integer
	*/
	private $rows_per_page = 20;
	
	/**
	* Process user input
	* @return boolean
	*/
	public function input(){
		if (isset($_POST['do_login'])){
			if ($this->inputLogin()){
				URI::redirect();
				exit;
			}
			return false;
		} else if (isset($_GET['logout'])){
			$this->model->disconnect();
			URI::redirect(PSM_URL);
			exit;
		} else if (isset($_POST['do_export'])){
			$schema = isset($_POST['schema']) ? trim($_POST['schema']) : '';
			$table = '';
			$options = array(
				'data' => isset($_POST['data']) ? $_POST['data'] : 'all',
				'drop' => isset($_POST['drop']) && intval($_POST['drop']) > 0
			);
			if (($filename = $this->model->export($schema, $table, $options)) !== false){
				$filepath = PSM_PATH.'tmp/'.$filename;
				header('Content-type: text/plain');
				header('Content-Length: '.filesize($filepath));
				header('Content-Disposition: attachment;filename='.$filename);
				header('Content-Transfer-Encoding: binary');
				
				$fh = fopen($filepath, 'rb');
				fpassthru($fh);
				fclose($fh);
				
				unlink($filepath);
				exit;
			}
		} else if (isset($_POST['do_import'])){
			$options = array(
				'transaction' => isset($_POST['transaction']) && intval($_POST['transaction']) > 0
			);
			if (is_uploaded_file($_FILES['file']['tmp_name'])){
				$filename = 'import_'.date('YmdHi').rand(1000, 9999).'.sql';
				if (move_uploaded_file($_FILES['file']['tmp_name'], PSM_PATH.'tmp/'.$filename)){
					if ($this->model->import($filename, $options)){
						unlink(PSM_PATH.'tmp/'.$filename);
						URI::redirect('?ok=1');
						exit;
					}
					unlink(PSM_PATH.'tmp/'.$filename);
				} else {
					URI::redirect('?err=2');
					exit;
				}
			} else {
				URI::redirect('?err=1');
				exit;
			}
			return false;
		}
		return true;
	}
	/**
	* Process login information
	* @return boolean
	*/
	private function inputLogin(){
		$res = false;
		// Read input
		$host = '';
		if (isset($_POST['host'])){
			$host = trim($_POST['host']);
		}
		$port = '';
		if (isset($_POST['port'])){
			$port = trim($_POST['port']);
		}
		$dbname = '';
		if (isset($_POST['dbname'])){
			$dbname = trim($_POST['dbname']);
		}
		$user = '';
		if (isset($_POST['user'])){
			$user = trim($_POST['user']);
		}
		$pass = '';
		if (isset($_POST['pass'])){
			$pass = $_POST['pass'];
		}
		// Set credentials
		if (strlen($host) > 0){
			if (strlen($port) > 0){
				$this->model->setServer($host, $port);
			} else {
				$this->model->setServer($host);
			}
		}
		if (strlen($dbname) > 0){
			$this->model->setDatabase($dbname);
			$res = true;
		}
		if (strlen($user) > 0){
			if (strlen($pass) > 0){
				$this->model->setUser($user, $pass);
			} else {
				$this->model->setUser($user);
			}
			$res = true;
		}
		if ($res){
			// Try connection
			if ($this->model->connect()){
				return true;
			}
		}
		return false;
	}
	/**
	* Display a view
	*/
	public function view($full_page = true){
		if ($full_page){
			echo '<!DOCTYPE html> 
<html lang="lv"> 
<head> 
	<meta charset="utf-8">
	<title>PostgreSQL Manager 1.0alpha</title>
	<meta http-equiv="Content-Language" content="en" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Lang" content="en">
	<meta name="copyright" content="Gusts \'gusC\' Kaksis">
	<meta name="author" content="Gusts \'gusC\' Kaksis">  
	<meta name="description" content="Web based PostgreSQL administration tool">
	<meta name="keywords" content="postgresql, psql">
	<link rel="stylesheet" type="text/css" href="/assets/css/main.css" media="screen, projection">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
	<script type="text/javascript" src="/assets/js/main.js"></script>
</head>
<body>
	<div id="holder">
		<div id="wrap">
			<!-- HEADER -->
			<header>';
		$this->viewMenu();
		echo '
			</header>
			<!-- // HEADER -->
			<!-- CONTENT -->
			<div id="content">';
		$this->viewContent();
		echo '
			</div>
			<!-- // CONTENT -->
		</div>
		<!-- FOOTER -->
		<footer>';
		$this->viewFooter();
		echo '
		</footer>
		<!-- // FOOTER -->
	</div>
</body>
</html>';
		} else {
			echo '
<div id="psm">';
			$this->viewMenu();
			$this->viewContent();
			echo '
</div>';
		}
		return true;
	}
	/**
	* Display menu bar
	*/
	private function viewMenu(){
		if ($this->model->isConnected()){
			echo '
				<ul id="psm-menu">
					<li class="structure"><a href="'.PSM_URL.'" title="Display database structure">Structure</a></li>
					<li class="functions"><a href="'.PSM_URL.'functions/" title="Display database functions">Functions</a></li>
					<li class="info"><a href="'.PSM_URL.'info/" title="Display database information">Info</a></li>
					<li class="sql"><a href="'.PSM_URL.'sql/" title="Execute SQL query">SQL</a></li>
					<li class="io"><a href="'.PSM_URL.'io/" title="Import or export data">Import/Export</a></li>
					<li class="logout"><a href="'.PSM_URL.'?logout=1" title="Log-out">Log-out</a></li>
				</ul>';
		}
	}
	/**
	* Display login form
	*/
	private function loginForm(){
		echo '
					<form method="post" action="" id="psm-login">
						<h1>Authorization</h1>';
		echo UIForms::hidden('do_login', 1);
		if ($this->model->hasErrors()){
			echo UIForms::error($this->model->getErrors());
		}
		if (!defined('PSM_DB_HOST')){
			echo UIForms::input('host', 'Host:', isset($_POST['host']) ? $_POST['host'] : '');
		}
		if (!defined('PSM_DB_PORT')){
			echo UIForms::input('port', 'Port:', isset($_POST['port']) ? $_POST['port'] : '');
		}
		if (!defined('PSM_DB_NAME')){
			echo UIForms::input('dbname', 'Database:', isset($_POST['dbname']) ? $_POST['dbname'] : '');
		}
		if (!defined('PSM_DB_USER')){
			echo UIForms::input('user', 'User:', isset($_POST['user']) ? $_POST['user'] : '');
			echo UIForms::password('pass', 'Password:');
		}
		echo UIForms::submit('login', 'Log-in');
		echo '
					</form>';
	}
	/**
	* Display contents
	*/
	private function viewContent(){
		if ($this->model->isConnected()){
			$route = $this->route;
			$main = '';
			if (count($route) > 0){
				$main = array_shift($route);
			}
			$object = '';
			if (count($route) > 0){
				$object = array_shift($route);
			}
			$task = '';
			if (count($route) > 0){
				$task = array_shift($route);
			}
			switch ($main){
				case 'info':
					$this->serverInfo();
					break;
				case 'functions':
					if (strlen($task) > 0){
						// because of:
						// functions/schema/public/ =  $main/$object/$task
						$this->functionList($task);
					} else {
						$this->schemaList('Functions');
					}
					break;
				case 'schema':
					$this->objectList($object, (isset($_GET['type']) ? $_GET['type'] : null));
					break;
				case 'table':
					list ($schema, $table) = explode('.', $object);
					if ($task == 'data'){
						$this->tableData($schema, $table);
					} else {
						$this->tableInfo($schema, $table);
					}
					break;
				case 'view':
					list ($schema, $view) = explode('.', $object);
					if ($task == 'data'){
						$this->tableData($schema, $view);
					} else {
						$this->viewInfo($schema, $view);
					}
					break;
				case 'sequence':
					list ($schema, $sequence) = explode('.', $object);
					$this->sequenceInfo($schema, $sequence);
					break;
				case 'sql':
					$this->sqlForm();
					break;
				case 'io':
					$this->ioForm();
					break;
				default:
					$this->schemaList('Structure');
					break;
			}
		} else {
			$this->loginForm();
		}
	}

	private function ioForm(){
		$schema = isset($_POST['schema']) ? trim($_POST['schema']) : '';
		$table = '';
		$data_type = isset($_POST['data']) ? $_POST['data'] : 'all';
		$drop = isset($_POST['drop']) && intval($_POST['drop']) > 0;
		echo '
					<form method="post" action="" id="psm-export">
						<h1>Export</h1>';
		echo UIForms::hidden('do_export', 1);
		if ($this->model->hasErrors()){
			if (isset($_POST['do_export'])){
				echo UIForms::error($this->model->getErrors());
			}
		}
		$schemas = array();
		if (($r = $this->model->getSchemas())!== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$schema = $this->model->fetch($r, $i);
				$schemas[$schema['name']] = $schema['name'];
			}
		}
		echo UIFOrms::select('schema', 'Schema:', $schemas, $schema);
		$data = array(
			'all' => 'Data & Structure',
			'structure' => 'Structure Only',
			'data' => 'Data Only'
		);
		echo UIForms::select('data', 'Export:', $data, $data_type);
		echo UIForms::checkbox('drop', 'Include drop commands', 1, $drop);
		echo UIForms::submit('submit', 'Export SQL file');
		echo '
					</form>';
		$transaction = isset($_POST['transaction']) && intval($_POST['transaction']) > 0;
		echo '
					<form method="post" action="" id="psm-import" enctype="multipart/form-data">
						<h1>Import</h1>';
		echo UIForms::hidden('do_import', 1);
		if ($this->model->hasErrors()){
			if (isset($_POST['do_import'])){
				echo UIForms::error($this->model->getErrors());
			}
		} else if (isset($_GET['err'])){
			echo UIForms::error('Error uploading SQL file');
		} else if (isset($_GET['ok'])){
			echo UIForms::info('SQL file imported successfully');
		}
		echo UIForms::upload('file', 'File to import:');
		echo UIForms::checkbox('transaction', 'Use transaction to prevent partial restoration', 1, $transaction);
		echo UIForms::submit('submit', 'Import SQL file');
		echo '
					</form>';
	}
	private function sqlForm(){
		$query = '';
		$test = false;
		if (isset($_POST['do_sql'])){
			$query = trim($_POST['query']);
			$test = (isset($_POST['test']) && intval($_POST['test']) > 0);
		}
		echo '
					<form method="post" action="" id="psm-query">
						<h1>Execute SQL query</h1>';
		echo UIForms::hidden('do_sql', 1);
		if ($this->model->hasErrors()){
			echo UIForms::error($this->model->getErrors());
		}
		echo UIForms::textarea('query', 'SQL query:', $query, array('class' => 'large'));
		echo UIFOrms::checkbox('test', 'Test query (transaction encapsulated)', 1, $test);
		echo UIForms::submit('execute', 'Execute');
		echo UIForms::submit('results', 'Execute With Results');
		if (isset($_POST['do_sql'])){
			if ($test){
				$this->model->exec('START TRANSACTION');
			}
			$start = microtime(true);
			$r = $this->model->exec($query);
			$end = microtime(true);
			$rows = 0;
			if ($r === false){
				echo UIForms::error($this->model->getErrors(), true);
				echo '<p>Status: Failed</p>';
			} else {
				$rows = pg_affected_rows($r);
				if (isset($_POST['results'])){
					$this->displayResults($r);
					echo '<p>Rows in total: '.$this->model->count($r).'</p>';
				} else {
					echo '<p>Status: OK</p>';
					echo '<p>Affected rows: '.$rows.'</p>';
				}
				$this->model->free($r);
			}
			if ($test){
				$this->model->exec('ROLLBACK TRANSACTION');
			}
			echo '<p>Execution time: '.number_format($end - $start, 2, '.', '').'</p>';
		}
		echo '
					</form>';
	}
	private function displayResults($r){
		echo '
					<table>
						<thead>
							<tr>';
		for ($i = 0; $i < pg_num_fields($r); $i ++){
			echo '
								<th>'.htmlspecialchars(pg_field_name($r, $i)).'</th>';
		}
		echo '
							</tr>
						</thead>
						<tbody>';
		$c = $this->model->count($r);
		for ($i = 0; $i < $c; $i ++){
			$row = $this->model->fetchRow($r, $i);
			echo '
							<tr>';
			foreach ($row as $field){
				echo '
								<td><pre>'.htmlspecialchars($field).'</pre></td>';
			}
			echo '
							</tr>';
		}
		echo '
						</tbody>
					</table>';
	}
	private function serverInfo(){
		echo '
					<h1>Info</h1>
					<table>
						<thead>
							<tr>
								<th>Field</th>
								<th>Value</th>
							</tr>
						</thead>
						<tbody>';
		if (($info = $this->model->getInfo()) !== false){
			echo '
							<tr>
								<th>Client version</th>
								<td>'.$info['client_version'].'</td>
							</tr>
							<tr>
								<th>Client encoding</th>
								<td>'.$info['client_encoding'].'</td>
							</tr>
							<tr>
								<th>Server version</th>
								<td>'.$info['server_version'].'</td>
							</tr>
							<tr>
								<th>Server encoding</th>
								<td>'.$info['server_encoding'].'</td>
							</tr>
							<tr>
								<th>Date format</th>
								<td>'.$info['date_style'].'</td>
							</tr>
							<tr>
								<th>Timezone</th>
								<td>'.$info['time_zone'].'</td>
							</tr>';
		} else {
			echo '
							<tr>
								<td colspan="2">No connection available</td>
							</tr>';
		}
		echo '
						</tbody>
					</table>';
	}
	private function functionList($schema){
		echo '
					<h1><a href="../../">Functions</a> &gt; '.$schema.'</h1>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Owner</th>
								<th>Language</th>
								<th>Prototype</th>
								<th>Returns</th>
								<th>Comments</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getFunctions($schema)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$function = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($function['name']).'</td>
								<td>'.htmlspecialchars($function['owner']).'</td>
								<td>'.$function['language'].'</td>
								<td>'.htmlspecialchars($function['prototype']).'</td>
								<td>'.htmlspecialchars($function['returns']).'</td>
								<td>'.htmlspecialchars($function['comment']).'</td>
							</tr>';
			}
		}
		echo '
						</tbody>
					</table>';
	}
	private function sequenceInfo($schema, $sequence){
		echo '
					<h1><a href="../../">Structure</a> &gt; <a href="../../schema/'.$schema.'/">'.$schema.'</a> &gt; '.$sequence.'</h1>
					<table>
						<thead>
							<tr>
								<th>Field</th>
								<th>Value</th>
							</tr>
						</thead>
						<tbody>';
		if (($seq = $this->model->getSequence($schema, $sequence)) !== false){
			echo '
							<tr>
								<th>Start value</th>
								<td>'.$seq['start_value'].'</td>
							</tr>
							<tr>
								<th>Last value</th>
								<td>'.$seq['last_value'].'</td>
							</tr>
							<tr>
								<th>Increment by</th>
								<td>'.$seq['increment_by'].'</td>
							</tr>
							<tr>
								<th>Min value</th>
								<td>'.$seq['min_value'].'</td>
							</tr>
							<tr>
								<th>Max value</th>
								<td>'.$seq['max_value'].'</td>
							</tr>
							<tr>
								<th>Cycle</th>
								<td>'.($seq['is_cycled'] ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
							</tr>';
		} else {
			echo '
							<tr>
								<td colspan="2">Sequence does not exist</td>
							</tr>';
		}
		echo '
						</tbody>
					</table>';
	}
	private function viewInfo($schema, $view){
		echo '
					<h1><a href="../../">Structure</a> &gt; <a href="../../schema/'.$schema.'/">'.$schema.'</a> &gt; '.$view.'</h1>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Type</th>
								<th>Not NULL</th>
								<th>Default</th>
								<th>Is Serial</th>
								<th>Comment</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getColumns($schema, $view)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$column = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($column['name']).'</td>
								<td>'.$column['type'].'</td>
								<td>'.($column['is_not_null'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($column['has_default'] == 't' ? htmlspecialchars($column['default_value']) : '').'</td>
								<td>'.($column['is_serial'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($column['comment']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
					</table>
					<h2>Definition</h2>
					<pre>'.$this->model->getViewDefinition($schema, $view).'</pre>';
	}
	private function tableInfo($schema, $table){
		echo '
					<h1><a href="../../">Structure</a> &gt; <a href="../../schema/'.$schema.'/">'.$schema.'</a> &gt; '.$table.'</h1>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Type</th>
								<th>Not NULL</th>
								<th>Default</th>
								<th>Is Serial</th>
								<th>Comment</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getColumns($schema, $table)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$column = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($column['name']).'</td>
								<td>'.$column['type'].'</td>
								<td>'.($column['is_not_null'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($column['has_default'] == 't' ? htmlspecialchars($column['default_value']) : '').'</td>
								<td>'.($column['is_serial'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($column['comment']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
						<tfoot>
							<tr>
								<th colspan="6"><p><a href="data/">View data</a></p></th>
							</tr>
						</tfoot>
					</table>
					<h2>Indexes</h2>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Is Primary</th>
								<th>Is Unique</th>
								<th>Is Clustered</th>
								<th>Definition</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getIndexes($schema, $table)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$index = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($index['name']).'</td>
								<td>'.($index['is_primary'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($index['is_unique'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($index['is_clustered'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($index['definition']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
					</table>
					<h2>Constraints</h2>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Type</th>
								<th>Is Clustered</th>
								<th>Definition</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getConstraints($schema, $table)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$constraint = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($constraint['name']).'</td>
								<td>'.$this->constraint_types[$constraint['type']].'</td>
								<td>'.($constraint['is_clustered'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($constraint['definition']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
					</table>
					<h2>Triggers</h2>
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Namespace</th>
								<th>Is Enabled</th>
								<th>Prototype</th>
								<th>Definition</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getTriggers($schema, $table)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$triggers = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td>'.htmlspecialchars($triggers['name']).'</td>
								<td>'.htmlspecialchars($triggers['namespace']).'</td>
								<td>'.($triggers['is_enabled'] ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($triggers['prototype']).'</td>
								<td>'.htmlspecialchars($triggers['definition']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
					</table>';
	}
	private function tableData($schema, $table){
		echo '
					<h1><a href="../../../">Structure</a> &gt; <a href="../../../schema/'.$schema.'/">'.$schema.'</a> &gt; <a href="../">'.$table.'</a> &gt; Data</h1>';
		$data_count = $this->model->getDataCount($schema, $table);
		if (($r = $this->model->getData($schema, $table, (isset($_GET['page']) ? $_GET['page'] * $this->rows_per_page : 0), $this->rows_per_page)) !== false){
			echo '
					<table>
						<thead>
							<tr>';
			$columns = $this->model->columnNames($r);
			foreach ($columns as $field){
				echo '
								<th>'.htmlspecialchars($field).'</th>';
			}
			echo '
							</tr>
						</thead>
						<tbody>';
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$data = $this->model->fetchRow($r, $i);
				echo '
							<tr>';
				foreach ($data as $field){
					echo '
								<td><pre>'.htmlspecialchars($field).'</pre></td>';
				}
				echo '
							</tr>';
			}
			$this->model->free($r);
			echo '
						</tbody>';
			if ($data_count > $this->rows_per_page){
				echo '
						<tfoot>
							<tr>
								<th colspan="'.count($columns).'">';
				echo UIPages::inTable(ceil($data_count / $this->rows_per_page), (isset($_GET['page']) ? $_GET['page'] : 0), $_GET);
				echo '
								</th>
							</tr>
						</tfoot>';
			}
			echo '
					</table>';
		}
	}
	private function objectList($schema, $type = null){
		echo '
					<h1><a href="../../">Structure</a> &gt; '.$schema.'</h1>
					<table>
						<thead>
							<tr>
								<th>Type</th>
								<th>Name</th>
								<th>Owner</th>
								<th>Tablespace</th>
								<th>Row count</th>
								<th>Has Indexes</th>
								<th>Has Keys</th>
								<th>Has Triggers</th>
								<th>Comments</th>
								<th></th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getObjects($schema)) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$table = $this->model->fetch($r, $i);
				$show = true;
				if (!is_null($type) && $this->object_types[$table['type']] != $type){
					$show = false;
				}
				if ($show){
					echo '
							<tr>
								<td class="type '.$this->object_types[$table['type']].'">'.$this->object_types[$table['type']].'</td>
								<td><a href="../../'.$this->object_types[$table['type']].'/'.$schema.'.'.$table['name'].'/">'.htmlspecialchars($table['name']).'</a></td>
								<td>'.htmlspecialchars($table['owner']).'</td>
								<td>'.htmlspecialchars($table['tablespace']).'</td>
								<td>'.$table['row_count'].'</td>
								<td>'.($table['has_indexes'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($table['has_keys'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.($table['has_triggers'] == 't' ? '<span class="yes">YES</span>' : '<span class="no">NO</span>').'</td>
								<td>'.htmlspecialchars($table['comment']).'</td>
								<td><a href="../../'.$this->object_types[$table['type']].'/'.$schema.'.'.$table['name'].'/">Info</a>';
					if (in_array($table['type'], array('r', 'v'))){
						echo ' | <a href="../../'.$this->object_types[$table['type']].'/'.$schema.'.'.$table['name'].'/data/">Data</a>';
					}
					echo '</td>
							</tr>';
				}
			}
		}
		echo '
						</tbody>
					</table>';
	}
	private function schemaList($title){
		echo '
					<h1>'.$title.'</h1>
					<table>
						<thead>
							<tr>
								<th>Type</th>
								<th>Name</th>
								<th>Owner</th>
								<th>Comments</th>
							</tr>
						</thead>
						<tbody>';
		if (($r = $this->model->getSchemas()) !== false){
			$c = $this->model->count($r);
			for ($i = 0; $i < $c; $i ++){
				$schema = $this->model->fetch($r, $i);
				echo '
							<tr>
								<td class="type schema">schema</td>
								<td><a href="schema/'.$schema['name'].'/">'.htmlspecialchars($schema['name']).'</a></td>
								<td>'.htmlspecialchars($schema['owner']).'</td>
								<td>'.htmlspecialchars($schema['comment']).'</td>
							</tr>';
			}
			$this->model->free($r);
		}
		echo '
						</tbody>
					</table>';
	}
	/**
	* Display footer
	*/
	private function viewFooter(){
		echo '<p>&copy; Gusts \'gusC\' Kaksis, 2012</p>';
	}
}
?>