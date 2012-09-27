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
* PostgreSQL Manager View class
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
*/
abstract class PSMView {
	/**
	* PostgreSQL Manager model object
	* @var PSMModel
	*/
	protected $model = null;
	/**
	* Route parameter list
	* @var array
	*/
	protected $route = array();
	
	/**
	* Create PostgreSQL Manager view object
	* 
	* @param PSMModel $model
	* @param array $route
	* @return PSMView
	*/
	public function __construct($model, $route){
		$this->model = $model;
		$this->route = $route;
	}
}
?>