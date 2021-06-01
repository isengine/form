<?php

namespace is\Masters\Modules\Isengine;

use is\Helpers\System;
use is\Helpers\Objects;
use is\Helpers\Strings;

use is\Masters\Modules\Master;
use is\Masters\View;

use is\Components\Dom;

class Form extends Master {
	
	public function launch() {
		
		// если нет ключа, пробуем взять ключ из СЕО
		
		$sets = &$this -> settings;
		
		$this -> buildFields();
		$this -> printFields();
		
	}
	
	public function buildFields() {
		
		//$view = View::getInstance();
		//return( $view -> get('state|settings') );
		
		$sets = &$this -> settings;
		
		$tags = ['select', 'textarea'];
		
		if (!System::typeIterable($sets['data'])) {
			return;
		}
		
		foreach ($sets['data'] as $item) {
			
			$attr = $item;
			unset($attr['data'], $attr['options']);
			
			$tag = Objects::match($tags, $item['type']) ? $item['type'] : (System::typeIterable($item['data']) ? 'div' : 'input');
			
			if (
				$attr['name'] &&
				(Objects::match(Objects::keys($attr), 'multiple') || $attr['type'] === 'checkbox')
			) {
				$attr['name'] .= '[]';
			}
			
			$this -> elements[ $item['name'] ] = new Dom($tag);
			
			if (System::typeIterable($item['data'])) {
				
				$content = null;
				
				if ($tag === 'div') {
					
					foreach ($item['data'] as $k => $i) {
						$it = new Dom('input');
						$it -> addCustom('value', $k);
						$it -> addContent($i);
						if (
							($item['type'] === 'checkbox' || $item['type'] === 'radio') &&
							$k === $item['options']['default']
						) {
							$it -> addAttr('checked');
						}
						
						if (System::typeIterable($attr)) {
							foreach ($attr as $kk => $ii) {
								if ($ii === true) {
									$it -> addAttr($kk);
								} else {
									$it -> addCustom($kk, $ii);
								}
							}
							unset($kk, $ii);
						}
						
						$print = $item['options']['before'] . $it -> get() . $item['options']['after'];
						$content .= Strings::replace($print, ['{k}', '{i}'], [$k, $i]);
						unset($print);
						
						//$print = $it -> get();
						//$print = Strings::replace($print, ['{k}', '{i}'], [$k, $i]);
						//$content .= $print;
						//unset($print);
						//echo htmlentities(print_r($print, 1));
						
					}
					unset($k, $i);
					
				} else {
					
					$print = null;
					
					foreach ($item['data'] as $k => $i) {
						$it = new Dom('option');
						$it -> addCustom('value', $k);
						$it -> addContent($i);
						if ($k === $item['options']['default']) {
							$it -> addAttr('selected');
						}
						$print .= $it -> get();
					}
					unset($k, $i, $it);
					
					if (System::typeIterable($attr)) {
						foreach ($attr as $kk => $ii) {
							if ($ii === true) {
								$this -> eget($item['name']) -> addAttr($kk);
							} else {
								$this -> eget($item['name']) -> addCustom($kk, $ii);
							}
						}
						unset($kk, $ii);
					}
					
					$content = $item['options']['before'] . $print . $item['options']['after'];
					unset($print);
					
				}
				
				//echo htmlentities($content);
				//echo '<br><br>';
				//echo '+++';
				
				//$this -> elements[ $item['name'] ] = new Dom( Objects::match($tags, $item['type']) ? $item['type'] : 'input' );
				
			} else {
				
				$print = null;
				
				if (System::typeIterable($attr)) {
					foreach ($attr as $kk => $ii) {
						if ($ii === true) {
							$this -> eget($item['name']) -> addAttr($kk);
						} else {
							$this -> eget($item['name']) -> addCustom($kk, $ii);
						}
					}
					unset($kk, $ii);
				}
				
				$content = $item['options']['before'] . $print . $item['options']['after'];
				unset($print);
				
			}
			
			$this -> eget($item['name']) -> addContent($content);
			
		}
		unset($item);
		
		//echo htmlentities(print_r($this -> elements, 1)) . '<br>';
		//echo htmlentities(print_r($this -> elements, 1)) . '<br>';
		
	}
	
	public function printFields() {
		
		if (!System::typeIterable($this -> elements)) {
			return;
		}
		
		foreach ($this -> elements as $item) {
			$item -> print();
		}
		unset($item);
		
	}
	
}

?>