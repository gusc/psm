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
* User interface class - table form renderer
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
* @version 1.0
*/
class UITableForms extends UIForms {
	/**
	* Wrap the field HTML for table form output
	* @param string $field_html - field HTML
	* @param mixed $class_name - additional class name for wrapper
	* @return string - HTML output for tables
	*/
	protected static function _wrap_field($field_html, $class_name = null){
		return '
<td'.(!is_null($class_name) && strlen($class_name) > 0 ? ' class="'.$class_name.'"' : '').'>'.$field_html.'</td>';
	}
	
	/**
	* Ordering buttons
	* @param string $action_up - up action call
	* @param string $action_down - down action call
	* @param string $name - name and id of hidden input field
	* @param mixed $value - custom value (for referencing if needed)
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function order($action_up, $action_down, $name = null, $value = null, $params = array()){
		$params = array_merge(self::$default_params, $params);
		$input = '';
		if (!is_null($name)){
			if (is_null($value)){
				$value = '';
			}
			
			$name = self::_name_to_array($name, $params['array_prefix']);
			$id = self::_name_to_id($name);
			$input = self::_input_hidden($name, $id, $value);
		}
		$html = '<a href="'.htmlspecialchars($action_up).'" class="up">Move up</a><a href="'.htmlspecialchars($action_down).'" class="down">Move down</a>'.$input;
		return self::_wrap_field($html, 'order'.(!is_null($params['additional_class']) && strlen($params['additional_class']) > 0 ? ' '.$params['additional_class'] : ''));
	}
}
?>