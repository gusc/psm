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
* User interface class - form renderer
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
* @version 1.0
*/
class UIForms {
	/**
	* Prefix string for input elements ID attribute
	* @var string
	*/
	protected static $input_id_prefix = 'input';
	/**
	* Default parameter array
	* @var array
	*/
	protected static $default_params = array(
	  'array_prefix' => null,
	  'help_link' => null,
	  'preview_link' => null,
	  'html' => null,
	  'class' => null,
	  'read_only' => false,
	  'inline' => false,
	  'disabled' => false,
	  'autocomplete' => true
	);
	/**
	* Wrap name attribute in array if asked to
	* @param string $name
	* @param string $array_prefix
	* @return string
	*/
	protected static function _name_to_array($name, $array_prefix = null){
		if (!is_null($array_prefix) && strlen($array_prefix) > 0){
			if (strpos($name, '[') !== false){
				$name = $array_prefix.'['.substr($name, 0, strpos($name, '[')).']'.substr($name, strpos($name, '['));
			} else {
		  	$name = $array_prefix.'['.$name.']';
			}
		}
		return $name;
	}
	/**
	* Transform name attribute to ID (strip array brackets)
	* @param string $name
	* @return string
	*/
	protected static function _name_to_id($name){
		$id = str_replace(array('[', ']', '_'), array('-', '', '-'), $name);
		$id = (strlen(static::$input_id_prefix) > 0 ? static::$input_id_prefix.'-' : '').$id;
		return $id;
	}
	/**
	* Private label generator
	* @param string $id
	* @param string $label
	* @return string
	*/
	protected static function _label($id, $label = null){
		if (is_null($label)){
			return '';
		}
		return '<label for="'.$id.'">'.htmlspecialchars($label).'</label>';
	}
	/**
	* Private text input generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $value - current value
	* @param boolean $read_only - is this read-only field?
	* @param boolean $disabled - is this field disabled?
	* @return string
	*/
	protected static function _input_text($name, $id, $value, $read_only = false, $disabled = false){
		return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.htmlspecialchars($value).'"'.($read_only ? ' readonly="readonly"' : '').($disabled ? ' disabled="disabled"' : '').' />';
	}
	/**
	* Private hidden input generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $value - current value
	* @return string
	*/
	protected static function _input_hidden($name, $id, $value){
		return '<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.htmlspecialchars($value).'" />';	
	}
	/**
	* Private password input generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param boolean $autocomplete - disable or enable autocompletion of this field in browser
	* @return string
	*/
	protected static function _input_password($name, $id, $autocomplete = false){
		return '<input type="password" name="'.$name.'" id="'.$id.'" value=""'.(!$autocomplete ? ' autocomplete="off"' : '').' />';	
	}
	/**
	* Private file input (browse-upload) generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @return string
	*/
	protected static function _input_file($name, $id){
		return '<input type="file" name="'.$name.'" id="'.$id.'" value="" />';	
	}
	/**
	* Private textarea input generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $value - current value
	* @return string
	*/
	protected static function _textarea($name, $id, $value){
		return '<textarea name="'.$name.'" id="'.$id.'">'.htmlspecialchars($value).'</textarea>';
	}
	/**
	* Private select option generator
	* @param mixed $options - option items (can be ItemList, ItemNode, array or prepared string)
	* @param mixed $value - current value
	* @return string
	*/
	protected static function _select_options($options, $value){
		$out = '';
		if (is_a($options, 'IDataList')) {
			$out .= '<option value="0"'.($value != null && intval($value) == 0 ? ' selected="selected"' : '').'>- - -</option>';
			while (($opt = $options->next()) !== false){
				$id = null;
				$name = null;
				if (!is_null($opt->id)){
					$id = intval($opt->id);
				}
				if (!is_null($opt->title)){
					$name = $opt->title;
				} else if (!is_null($opt->name)){
					$name = $opt->name;
				}
				if (is_null($id)){
					$id = $name;
				}
				if (is_null($name)){
					$name = $id;
				}
				$add = '';
				if (!is_null($opt->depth) && intval($opt->depth) > 0){
					$add = str_pad($add, 2 * $opt->depth, '- ', STR_PAD_LEFT);
				}
				if (!is_null($id) && !is_null($name)){
					$out .= '<option value="'.htmlspecialchars($opt->id).'"'.($value != null && $opt->id == $value ? ' selected="selected"' : '').'>'.htmlspecialchars($add.$name).'</option>';
					if (is_a($opt, 'IDataNode')){
						$out .= static::_select_options($opt->children(), $value);
					}
				}
			}
		} else if (is_a($options, 'IDataNode')){
			$id = null;
			$name = null;
			if (!is_null($options->id)){
				$id = intval($options->id);
			}
			if (!is_null($options->title)){
				$name = $options->title;
			} else if (!is_null($options->name)){
				$name = $options->name;
			}
			if (is_null($id)){
				$id = $name;
			}
			if (is_null($name)){
				$name = $id;
			}
			$add = '';
			if (!is_null($options->depth) && intval($options->depth) > 0){
				$add = str_pad($add, 2 * $options->depth, '- ', STR_PAD_LEFT);
			}
			if (!is_null($id) && !is_null($name)){
				$out .= '<option value="'.htmlspecialchars($options->id).'"'.($value != null && $options->id == $value ? ' selected="selected"' : '').'>'.htmlspecialchars($add.$name).'</option>';
				$out .= static::_select_options($options->children(), $value);
			}
		} else if (is_array($options)){
			/*$out .= '<option value="0"'.($value != null && intval($value) == 0 ? ' selected="selected"' : '').'>- - -</option>';*/
			foreach ($options as $val => $lab){
				if (is_array($lab)){
					$out .= '<optgroup label="'.htmlspecialchars($val).'">';
					foreach ($lab as $val => $sublab){
						$out .= '<option value="'.htmlspecialchars($val).'"'.($value != null && $val == $value ? ' selected="selected"' : '').'>'.htmlspecialchars($sublab).'</option>';
					}
					$out .= '</optgroup>';
				} else {
					$out .= '<option value="'.htmlspecialchars($val).'"'.($value != null && $val == $value ? ' selected="selected"' : '').'>'.htmlspecialchars($lab).'</option>';
				}
			}
		} else if (is_string($options)){
			$out .= $options;
		}
		return $out;
	}
	/**
	* Private select generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $options_string - generated option list string
	* @return string
	*/
	protected static function _select($name, $id, $options_str, $class = ''){
		return '<select name="'.$name.'" id="'.$id.'"'.(strlen($class) > 0 ? ' class="'.$class.'"' : '').'>'.$options_str.'</select>';
	}
	/**
	* Private checkbox generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $value - default value
	* @param boolean $checked - is it checked?
	* @return string
	*/
	protected static function _checkbox($name, $id, $value, $checked = false){
		return '<input type="checkbox" name="'.$name.'" id="'.$id.'" value="'.htmlspecialchars($value).'"'.($checked ? ' checked="checked"' : '').' />';
	}
	/**
	* Private radio button generator
	* @param string $name - name attribute
	* @param string $id - id attribute
	* @param mixed $value - default value
	* @param boolean $checked - is it checked?
	* @return string
	*/
	protected static function _radio($name, $id, $value, $checked = false){
		return '<input type="radio" name="'.$name.'" id="'.$id.'" value="'.htmlspecialchars($value).'"'.($checked ? ' checked="checked"' : '').' />';
	}
	/**
	* Private help link generator
	* @param string $help_link - URL to help page
	* @return string
	*/
	protected static function _help_link($help_link = null){
		if (is_null($help_link)){
			return '';
		}
		return (strlen($help_link) > 0 ? '<a href="'.htmlspecialchars($help_link).'" class="help">Help</a>' : '');
	}
	/**
	* Private preview link generator
	* @param string $preview_link - URL to preview page
	* @return string
	*/
	protected static function _preview_link($preview_link = null){
		if (is_null($preview_link)){
			return '';
		}
		return (strlen($preview_link) > 0 ? '<a href="'.htmlspecialchars($preview_link).'" class="preview">Preview</a>' : '');
	}
	/**
	* Additional HTML contents wrapper
	* @param string $html - additional HTML elements for the input
	* @return string
	*/
	protected static function _add_html($html = null){
		if (is_null($html)){
			return '';
		}
		return (strlen($html) > 0 ? '<span class="more">'.$html.'</span>' : '');
	}
	/**
	* Wrap the field HTML for standard form output
	* @param string $field_html - field HTML
	* @param mixed $class_name - additional class name for wrapper
	* @return string - HTML output for forms
	*/
	protected static function _wrap_field($field_html, $class_name = null, $inline = false){
		return '
<'.($inline ? 'span' : 'div').(!is_null($class_name) && strlen($class_name) > 0 ? ' class="'.$class_name.'"' : '').'>'.$field_html.'</'.($inline ? 'span' : 'div').'>';
	}
	/**
	* Informative text
	* @param string $text
	* @return string - HTML output
	*/
	public static function info($text){
		if (is_array($text)){
			$text = implode('<br />', $text);
		}
		return '<p class="info">'.$text.'</p>';
	}
	/**
	* Error text
	* @param string $text
	* @return string - HTML output
	*/
	public static function error($text, $preformat = false){
		if (is_array($text)){
			if ($preformat){
				$text = '<pre>'.implode("\n", $text).'</pre>';
			} else {
				$text = implode('<br />', $text);
			}
		}
		return '<p class="error">'.$text.'</p>';
	}
	/**
	* Warning text
	* @param string $text
	* @return string - HTML output
	*/
	public static function warning($text){
		if (is_array($text)){
			$text = implode('<br />', $text);
		}
		return '<p class="warning">'.$text.'</p>';
	}
	/**
	* Standart label for distant label output
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function label($name, $label = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		return static::_label($id, $label);
	}
	/**
	* Hidden input field
	* @param string $name - name and id of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function hidden($name, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_input_hidden($name, $id, $value);
		return static::_wrap_field($html, 'hidden', $params['inline']);
	}
	/**
	* Standart input field
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function input($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_text($name, $id, $value, $params['read_only'], $params['disabled']);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, $params['class'], $params['inline']);
	}
	/**
	* Password input field
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function password($name, $label = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_password($name, $id, $params['autocomplete']);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, $params['class'], $params['inline']);
	}
	/**
	* Image input field (using jquery file-browser)
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function image($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_text($name, $id, $value, $params['read_only'], $params['disabled']);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'image'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Color input field (using jquery color picker)
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function color($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_text($name, $id, $value, $params['read_only'], $params['disabled']);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'color'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Standart file upload
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function upload($name, $label = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_file($name, $id);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'file'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Textarea field
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function textarea($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		if (isset($params['toggle_wysiwyg']) && $params['toggle_wysiwyg']){
			$html .= '<a href="#" rel="#'.$id.'" class="wysiwyg-switch">Toggle WYSIWYG</a>';
		}
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		$html .= static::_textarea($name, $id, $value);
		return static::_wrap_field($html, 'text'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Textarea field with WYSIWYG
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function wysiwyg($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($value)){
			$value = '';
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		$html .= static::_textarea($name, $id, $value);
		return static::_wrap_field($html, 'wysiwyg'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Select drop-down
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $options - option list
	* @param mixed $current_value - value of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function select($name, $label = null, $options, $current_value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		if (is_null($current_value)){
			$current_value = 0;
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_select($name, $id, static::_select_options($options, $current_value));
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, $params['class'], $params['inline']);
	}
	/**
	* Radio button
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - radio button value
	* @param mixed $current_value - current value of radio button group
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function radio($name, $label = null, $value, $current_value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name.'_'.$value);
		
		$checked = false;
		if (!is_null($current_value)){
			if (is_a($current_value, 'ItemList')){
				if ($current_value->getById($value) !== false){
					$checked = true;
				}
			} else if (is_array($current_value)){
				if (in_array($value, $current_value)){
					$checked = true;
				}
			} else if (is_bool($current_value)){
				$checked = $current_value;
			} else if ($current_value == 'true'){
				$checked = true;
			} else if ($current_value == $value){
				$checked = true;
			}
		}
		
		$html = static::_radio($name, $id, $value, $checked);
		$html .= static::_label($id, $label);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'choose'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Check box
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - check box value
	* @param mixed $current_value - current value of check box group
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function checkbox($name, $label = null, $value, $current_value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$checked = false;
		if (!is_null($current_value)){
			if (is_a($current_value, 'ItemList')){
				if ($current_value->getById($value) !== false){
					$checked = true;
				}
			} else if (is_array($current_value)){
				if (in_array($value, $current_value)){
					$checked = true;
				}
			} else if (is_bool($current_value)){
				$checked = $current_value;
			} else if ($current_value == 'true'){
				$checked = true;
			} else if ($current_value == 't'){
				$checked = true;
			} else if ($current_value == $value){
				$checked = true;
			}
		}
		
		$html = static::_checkbox($name, $id, $value, $checked);
		$html .= static::_label($id, $label);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'agree'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Shortcut for checkbox without value (always=1)
	* Use this with UIFormsData to test user input (true/false)
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $current_value - current value of check box group
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function checkbox_boolean($name, $label = null, $current_state = false, $params = array()){
		return static::checkbox($name, $label, 1, $current_state, $params);
	}
	/**
	* Date input (using jQuery UI datepicker)
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - radio button value
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function dateinput($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$date = '';
		if (!is_null($value)){
			$date = substr($value, 0, 10);
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$id = static::_name_to_id($name);
		
		$html = static::_label($id, $label);
		$html .= static::_input_text($name, $id, $date, $params['read_only'], $params['disabled']);
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'date'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Date input (using jQuery UI datepicker) + time input select
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param mixed $value - radio button value
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function datetimeinput($name, $label = null, $value = null, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$date = '';
		$time = intval(date('H'));
		if (!is_null($value)){
			$date = substr($value, 0, 10);
			$time = intval(substr($value, 11, 2));
		}
		$name = static::_name_to_array($name, $params['array_prefix']);
		$name_time = static::_name_to_array($name.'-time', $params['array_prefix']);
		$id = static::_name_to_id($name);
		$id_time = static::_name_to_id($name_time);
		$options_str = '';
		for ($i = 0; $i < 24; $i ++){
			$options_str .= '
		<option value="'.$i.'"'.($i == $time ? ' selected="selected"' : '').'>'.str_pad($i, 2, '0', STR_PAD_LEFT).':00</option>';
		}
		
		$html = static::_label($id, $label);
		$html .= static::_input_text($name, $id, $date, $params['read_only'], $params['disabled']);
		$html .= static::_select($name_time, $id_time, $options_str, 'time');
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'date'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Form submit button
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function submit($name, $label, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$id = static::_name_to_id($name);
		
		$html = '<input type="submit" name="'.$name.'" id="'.$id.'" value="'.$label.'" />';
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'submit'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
	/**
	* Simple button
	* @param string $name - name and id of input field
	* @param string $label - label of input field
	* @param array $params - additional parameters (see UIForms::$default_params)
	* @return string - HTML output
	*/
	public static function button($name, $label, $params = array()){
		$params = array_merge(static::$default_params, $params);
		$id = static::_name_to_id($name);
		
		$html = '<input type="button" name="'.$name.'" id="'.$id.'" value="'.$label.'" />';
		$html .= static::_add_html($params['html']);
		$html .= static::_help_link($params['help_link']);
		return static::_wrap_field($html, 'button'.(!is_null($params['class']) && strlen($params['class']) > 0 ? ' '.$params['class'] : ''), $params['inline']);
	}
}
?>