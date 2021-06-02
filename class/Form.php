<?php

namespace is\Masters\Modules\Isengine;

use is\Helpers\System;
use is\Helpers\Objects;
use is\Helpers\Strings;

use is\Masters\Modules\Master;
use is\Masters\View;

use is\Components\Config;
use is\Components\State;
use is\Components\Dom;
use is\Components\Uri;

// все операции, связанные с обработкой данных теперь находятся в апи
// если раньше форма фактически управляла этими операциями,
// то теперь это просто виджет формы
// однако здесь все еще не хватает передачи данных:
// - ключ с именем пользователя и паролем апи
// - спам-защита в пустой скрытой ячейке
// - отлов и вывод ошибок и возврат значей в ячейки
// - информация о форме, с которой был запущен процесс
// последнее нужно для очистки и валидации входных данных
// хотя, возможно, это как раз не нужно,
// т.к. управляться должно опять же не формой, а апи
// соответственно, именно в настройках процессов апи мы задаем
// очистку, валидацию и прочее

class Form extends Master {
	
	public $returns;
	
	public function launch() {
		
		// если нет ключа, пробуем взять ключ из СЕО
		
		$uri = Uri::getInstance();
		$this -> returns = $uri -> getData();
		unset($uri);
		
		$sets = &$this -> settings;
		
		$this -> buildFields();
		$this -> buildForm();
		//$this -> printFields();
		
	}
	
	public function buildFields() {
		
		//$view = View::getInstance();
		//return( $view -> get('state|settings') );
		
		$sets = &$this -> settings;
		
		$tags = ['select', 'textarea', 'list'];
		
		if (!System::typeIterable($sets['data'])) {
			return;
		}
		
		foreach ($sets['data'] as $item) {
			
			$attr = $item;
			unset($attr['data'], $attr['options']);
			
			$tag = Objects::match($tags, $item['type']) ? $item['type'] : (System::typeIterable($item['data']) ? 'div' : 'input');
			$rval = $this -> returns[$item['name']];
			$returns = [
				'value' => !System::typeOf($rval, 'iterable') ? $rval : null,
				'array' => System::typeOf($rval, 'iterable') ? $rval : []
			];
			unset($rval);
			
			if (
				$attr['name'] &&
				(Objects::match(Objects::keys($attr), 'multiple') || $attr['type'] === 'checkbox')
			) {
				$attr['name'] .= '[]';
			}
			
			$this -> ecreate($item['name'], $tag);
			//$this -> elements[ $item['name'] ] = new Dom($tag);
			
			if (System::typeIterable($item['data'])) {
				
				$content = null;
				
				if ($tag === 'div') {
					
					foreach ($item['data'] as $k => $i) {
						$it = new Dom('input');
						$it -> addCustom('value', $k);
						$it -> addContent($i);
						if (
							(
								$item['type'] === 'checkbox' ||
								$item['type'] === 'radio'
							) &&
							(
								(
									$k === $item['options']['default'] &&
									!$returns['value'] &&
									!$returns['array']
								) ||
								$k === $returns['value'] ||
								Objects::match($returns['array'], $k)
							)
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
					
				} elseif ($tag === 'list') {
					
					$this -> eget($item['name']) -> setTag('input');
					$this -> eget($item['name']) -> setCustom('list', $this -> get('instance') . '-' . $item['name'] . '-datalist');
					
					$print = null;
					
					foreach ($item['data'] as $k => $i) {
						$it = new Dom('option');
						if (System::type($k, 'numeric')) {
							$it -> addCustom('value', $i);
						} else {
							$it -> addCustom('value', $k);
							$it -> addContent($i);
						}
						if (System::set($item['options']['default'])) {
							$this -> eget($item['name']) -> addCustom('placeholder', $item['options']['default']);
						}
						if (System::set($returns['value'])) {
							$this -> eget($item['name']) -> addCustom('value', $returns['value']);
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
					
					$content = $item['options']['before'] . '<datalist id="' . $this -> get('instance') . '-' . $item['name'] . '-datalist">' . $print . '</datalist>' . $item['options']['after'];
					unset($print);
					
				} else {
					
					$print = null;
					
					foreach ($item['data'] as $k => $i) {
						$it = new Dom('option');
						$it -> addCustom('value', $k);
						$it -> addContent($i);
						if (
							(
								$k === $item['options']['default'] &&
								!$returns['value'] &&
								!$returns['array']
							) ||
							$k === $returns['value'] ||
							Objects::match($returns['array'], $k)
						) {
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
				
				if (System::set($item['options']['default'])) {
					$this -> eget($item['name']) -> addCustom('placeholder', $item['options']['default']);
				}
				if (System::set($returns['value'])) {
					$this -> eget($item['name']) -> addCustom('value', $returns['value']);
				}
				
				$content = $item['options']['before'] . $print . $item['options']['after'];
				unset($print);
				
			}
			
			$this -> eget($item['name']) -> addContent($content);
			
		}
		unset($item);
		
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
	
	public function buildForm() {
		
		$this -> ecreate('form', 'form');
		
		$sets = &$this -> settings;
		
		if (!$sets['api']) {
			return;
		}
		
		$config = Config::getInstance();
		$action = '/' . $config -> get('api:name') . '/' . Strings::replace($sets['api'], ':', '/') . '/';
		
		Objects::each($sets['form'], function($i, $k){
			$this -> eget('form') -> addCustom($k, $i);
		});
		
		$this -> eget('form') -> addCustom('method', $sets['method'] === 'post' || $sets['method'] === 'files' ? 'post' : 'get');
		
		if ($sets['method'] === 'files') {
			$this -> eget('form') -> addCustom('enctype', 'multipart/form-data');
		}
		
		$this -> eget('form') -> addCustom('action', $action);
		
	}
	
	public function printForm() {
		
		$this -> eget('form') -> open(true);
		$this -> printFields();
		$this -> eget('form') -> close(true);
		
	}
	
}

?>