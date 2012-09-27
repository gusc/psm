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
* User interface class - pagination renderer
* @author Gusts 'gusC' Kaksis <gusts.kaksis@graftonit.lv>
* @version 1.0
*/
class UIPages {
	static $pageGroup = 3;

	/**
	* Simple anchor list
	* @param integer $page_count
	* @param integer $current_page
	* @param mixed $add_link
	* @param string $page_param
	* @param boolean $prev_after
	* @param string $anchor
	* @return string
	*/
	static function inTable($page_count, $current_page, $add_link, $page_param = 'page', $prev_after = false, $anchor = ''){
		if (is_array($add_link)){
			if (isset($add_link[$page_param])){
				unset($add_link[$page_param]);
			}
			$add_link = http_build_query($add_link);
		}
		if ($page_count > 1){
			$pages = '';
			$skip = false;
			if ($current_page > 0){
				$pages .= '<a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.($current_page - 1).(strlen($anchor) > 0 ? $anchor : '').'" class="prev">Previous</a>';
			} else {
				//$pages .= '<a class="prev">Previous</a>';
			}
			for ($i = 0; $i < $page_count; $i ++){
				/* sataisa lapu sarakstā daudzpunktus (1 2 .. 5 6 7 .. 12 13) */
				if ($i < self::$pageGroup || ($page_count - $i) <= self::$pageGroup || ($i >= $current_page - (self::$pageGroup - 1) && $i <= $current_page + (self::$pageGroup - 1))){
					if ($current_page == $i){
						$pages .= '<a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.$i.(strlen($anchor) > 0 ? $anchor : '').'" class="selected">'.($i+1).'</a>';
					} else {
						$pages .= '<a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.$i.(strlen($anchor) > 0 ? $anchor : '').'">'.($i+1).'</a>';
					}
					$skip = true;
				}	else if ($skip) {
					$pages .=  '<span class="dots">...</span>';
					$skip = false;
				}
			}
			if ($current_page < $page_count - 1){
				$pages .= '<a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.($current_page + 1).(strlen($anchor) > 0 ? $anchor : '').'" class="next">Next</a>';
			} else {
				//$pages .= '<a class="next">Next</a>';
			}
			return $pages;
		} else {
			return '';
		}
	}
  /**
  * UL/LI pagination
  * @param integer $page_count
  * @param integer $current_page
  * @param mixed $add_link
  * @param string $page_param
  * @param boolean $prev_after
  * @param string $anchor
  * @return string
  */
	static function ulPaging($page_count, $current_page, $add_link, $page_param = 'page', $prev_after = false, $anchor = ''){
		if (is_array($add_link)){
			if (isset($add_link[$page_param])){
				unset($add_link[$page_param]);
			}
			$add_link = http_build_query($add_link);
		}
		if ($page_count > 1){
			$pages = '<ul class="pages">';
			$skip = false;
			if ($current_page > 0){
				$prev = '<li class="prev"><a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.($current_page - 1).(strlen($anchor) > 0 ? $anchor : '').'">Previous</a></li>';
			} else {
				$prev = '<li class="prev">Previous</li>';
			}
			if (!$prev_after){
				$pages .= $prev;
			}
			for ($i = 0; $i < $page_count; $i ++){
				/* sataisa lapu sarakstā daudzpunktus (1 2 .. 5 6 7 .. 12 13) */
				if ($i < self::$pageGroup || ($page_count - $i) <= self::$pageGroup || ($i >= $current_page - (self::$pageGroup - 1) && $i <= $current_page + (self::$pageGroup - 1))){
					if ($current_page == $i){
						$pages .= '
				<li class="selected"><a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.$i.(strlen($anchor) > 0 ? $anchor : '').'">'.($i+1).'</a></li>';
					} else {
						$pages .= '
				<li><a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.$i.(strlen($anchor) > 0 ? $anchor : '').'">'.($i+1).'</a></li>';
					}
					$skip = true;
				}	else if ($skip) {
					$pages .=  '
				<li class="empty">...</li>';
					$skip = false;
				}
			}
			if ($prev_after){
				$pages .= $prev;
			}
			if ($current_page < $page_count-1){
				$pages .= '<li class="next"><a href="?'.(strlen($add_link) > 0 ? $add_link.'&' : '').'page='.($current_page + 1).(strlen($anchor) > 0 ? $anchor : '').'">Next</a></li>';
			} else {
				$pages .= '<li class="next">Next</li>';
			}
			return $pages.'</ul>';
		} else {
			return '';
		}
	}
}
?>