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
* URI parsing class
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
* @version 2.0
*/
class URI {
	/**
	* Is this a local file path or an URL
	* @var boolean
	*/
	private $is_local = false;
	/**
	* URI schema
	* @var string
	* @access private
	*/
	private $schema = '';
	/**
	* URI hostname
	* @var string
	* @access private
	*/
	private $host = '';
	/**
	* Directory array
	* @var array
	* @access private
	*/
	private $path = '';
	/**
	* Query params
	* @var array
	* @access private
	*/
	private $query = array();
	/**
	* Fragment part of the URL
	* @var string
	*/
	private $fragment = '';
  /**
  * URI parser constructor
  * @param string $uri - URI
  * @return URI
  */
	public function __construct($uri = null){
		if (is_null($uri) || (is_string($uri) && strlen($uri) <= 0)){
			if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])){
				$uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];	
			} else if (isset($_SERVER['PHP_SELF'])) {
				$this->is_local = true;
				$uri = $_SERVER['PHP_SELF'];
				if (substr($uri, 0, 1) != '/'){
					if (isset($_SERVER['PWD'])){
						$uri = $_SERVER['PWD'].'/'.$uri;
					} else {
						$this->is_local = false;
						$uri = 'http://localhost/'.$uri;
					}
				}
			}
		}
		$this->setUriStr($uri);
	}
  /**
	* Set URI as a string
	* @param string $uri
	* @return void
	*/
	public function setUriStr($uri){
		$data = parse_url($uri);
		$this->schema = (isset($data['schema']) ? $data['schema'] : '');
		$this->host = (isset($data['host']) ? $data['host'] : '');
		$this->path = (isset($data['path']) ? $data['path'] : '');
		if (isset($data['query'])){
			$this->query = URI::parseQuery($data['query']);
		} else {
			$this->query = array();
		}
		if (isset($data['fragment'])){
			$this->fragment = $data['fragment'];
		} else {
			$this->fragment = '';
		}
	}
	/**
	* Compile URI string
	* @param mixed $with_host - weather to prepend schema://host part
	* @return string
	*/
	public function getUriStr($with_host = false){
		$out = '';
		if ($with_host){
			$out .= $this->schema.'://'.$this->host;
		}
		$out .= $this->path;
		$out .= URI::compileQuery($this->query);
		if (strlen($this->fragment) > 0){
			$out .= '#'.$this->fragment;
		}
		return $out;
	}
	
	//
	// Schema, host and fragment methods
	//
	
	/**
	* Get schema
	* @return string
	*/
	public function getSchema(){
		return $this->schema;
	}
	/**
  * Set new schema
  * @param string $schema
  */
	public function setSchema($schema){
		if (is_string($schema) && strlen($schema) > 0){
			$this->schema = $schema;
		} else {
			$this->schema = '';
		}
	}
	/**
	* Get hostname
	* @return string
	*/
	public function getHost(){
		return $this->host;
	}
	/**
  * Set new hostname
  * @param string $host
  */
	public function setHost($host){
		if (is_string($host) && strlen($host) > 0){
			$this->host = $host;
		} else {
			$this->host = '';
		}
	}
	/**
	* Get fragment
	* @return string
	*/
	public function getFragment(){
		return $this->fragment;
	}
	/**
	* Set new fragment
	* @param string $fragment
	*/
	public function setFragment($fragment){
		if (is_string($fragment) && strlen($fragment) > 0){
			$this->fragment = $fragment;
		} else {
			$this->fragment = '';
		}
	}
	
	//
	// Path methods
	//
	
  /**
	* Get path as a string
	* @return string
	*/
	public function getPathStr(){
		return $this->path;
	}
	/**
	* Set new path as a string
	* @param string - $path
	*/
	public function setPathStr($path){
		if (is_string($path) && strlen($path) > 0){
			$this->path = $path;
			if (substr($this->path, 0, 1) != '/'){
				$this->path = '/'.$this->path;
			}
		} else {
			$this->path = '';
		}
	}
	/**
	* Get path as an array
	* @return array
	*/
	public function getPathArr(){
		$path = $this->path;
		if (substr($path, 0, 1) == '/'){
			$path = substr($path, 1);
		}
		if (substr($path, -1) == '/'){
			$path = substr($path, 0, -1);
		}
		return explode('/', $path);
	}
	/**
	* Set new path as a string
	* @param array - $path
	*/
	public function setPathArr($path){
		if (is_array($path) && count($path) > 0){
			$this->path = '/'.implode('/', $path);
		} else {
			$this->path = '';
		}
	}
	
	//
	// Query methods
	//
	
	/**
	* Get query as a string
	* @return string
	*/
	public function getQueryStr(){
		return URI::compileQuery($this->query);
	}
	/**
	* Set new query as a string
	* @param string $query
	*/
	public function setQueryStr($query){
		$this->query = URI::parseQuery($query);
	}
	/**
	* Get query as an array
	* @return array
	*/
	public function getQueryArr(){
		return $this->query;
	}
	/**
	* Set new query as an array
	* @param array $query
	*/
	public function setQueryArr($query){
		if (is_array($query)){
			$this->query = $query;
		} else {
			$this->query = array();
		}
	}
	/**
	* Get query parameter
	* @param string $name
	* @return mixed
	*/
	public function getParam($name){
		if (isset($this->query[$name])){
			return $this->query[$name];
		}
		return null;
	}
	/**
	* Set query parameter
	* @param string $name
	* @param mixed $value
	*/
	public function setParam($name, $value){
		$this->query[$name] = $value;
	}
	/**
	* Delete query parameter
	* @param string $name
	* @return mixed
	*/
	public function delParam($name){
		if (isset($this->query[$name])){
			unset($this->query[$name]);
		}
	}
	/**
	* Check weather query parameter is defined
	* @param string $name
	* @return boolean
	*/
	public function hasParam($name){
		if (isset($this->query[$name])){
			return true;
		}
		return false;
	}
  
  //
  // Static utility methods
  //
  
	/**
	* Parse query string and return as an array
	* @param string $query_str
	* @return array $query
	*/
	public static function parseQuery($query_str){
		$query = array();
		parse_str($query_str, $query);
		return $query;
	}
	/**
	* Compile query array into string
	* @param array $query
	* @return string
	*/
	public static function compileQuery($query){
		$out = '';
		if (is_array($query) && count($query) > 0){
			$out = http_build_query($query);
		}
		return (strlen($out) > 0 ? '?'.$out : '');
	}
	/**
	* Static Redirect user to some other location
	* @param string $new_uri - path to go to
	* @param bool - Force to do a JavaScript redirect
	* @access static
	*/
	public static function redirect($new_uri = null, $force_js = false){
		if (is_null($new_uri)){
			$new_uri = '';
		}
		if (strlen($new_uri) <= 0){
			$new_uri = $_SERVER['REQUEST_URI'];
		}
		if (headers_sent() || $force_js){
			echo '<script type="text/javascript">window.location="'.$new_uri.'";</script>';
		} else {
			header('Location: '.$new_uri);
		}
		exit;
	}
}
?>