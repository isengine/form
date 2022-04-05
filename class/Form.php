<?php

namespace is\Masters\Modules\Isengine;

use is\Helpers\System;
use is\Helpers\Objects;
use is\Helpers\Strings;
use is\Helpers\Prepare;

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

// позднее мы добавили еще несколько типов полей:
// instance - передает instance модуля, также должен иметь name instance
// spam - создает пустое поле для доп.защиты от спам-ботов на скрытое поле, которое должно быть оставлено пустым, боты его как правило заполняют
// позднее мы добавим еще несколько типов полей:
// для капчи
// также для обработки поля - сделаем класс со стандартными обработчиками,
// который можно будет наследовать и расширять

class Form extends Master {
	
	public $returns; // это возвращенные значения из адресной строки
	public $wrappers; // это массив блоков, которые используются для обертки
	
	public function launch() {
		
		// если нет ключа, пробуем взять ключ из СЕО
		
		$uri = Uri::getInstance();
		$this -> returns = $uri -> getData();
		unset($uri);
		
		Objects::recurse($this -> returns, function($i){
			return Prepare::urldecode($i);
		});
		
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
			
			// объявляем конент - это будет встроенное содержимое элемента
			
			$content = null;
			
			// здесь объявляем атрибуты поля
			
			$attr = $item;
			unset($attr['data'], $attr['options']);
			
			// если тип входит в группу тэгов (выше), то тег назначается согласно этому типу,
			// иначе мы берем input для одиночных данных и div для группы/массива
			
			$tag = Objects::match($tags, $item['type']) ? $item['type'] : (System::typeIterable($item['data']) ? 'div' : 'input');
			
			// здесь мы берем значения для полей из адресной строки
			// чтобы затем вставить их в форму
			
			// этот код ниже был добавлен из-за сбоя, который теперь устранен
			// оставляем его на всякий случай
			// предыдущий комментарий распространяется ниже
			//if (System::typeOf($item['name'], 'iterable')) {
			//	$item['name'] = Strings::join($item['name'], ':');
			//}
			
			$rval = $this -> returns[$item['name']];
			$returns = [
				'value' => !System::typeOf($rval, 'iterable') ? $rval : null,
				'array' => System::typeOf($rval, 'iterable') ? $rval : []
			];
			unset($rval);
			
			// здесь мы заранее делаем из имени поля массив
			// потому что здесь на самом деле будет использовано несколько однотипных полей
			// и нужно, чтобы данные с них отправлялись тоже в виде массива
			
			// если хотите использовать поле input для массива значений,
			// поставьте в настройках поля атрибут multiple
			// сам массив можно поместить в раздел data
			
			if (
				$attr['name'] &&
				(Objects::match(Objects::keys($attr), 'multiple') || $attr['type'] === 'checkbox')
			) {
				$attr['name'] .= '[]';
			}
			
			$this -> ecreate($item['name'], $tag);
			//$this -> elements[ $item['name'] ] = new Dom($tag);
			
			if (System::typeIterable($item['data'])) {
				
				if ($tag === 'div') {
					
					foreach ($item['data'] as $k => $i) {
						
						$it = new Dom('input');
						
						if (
							$item['type'] === 'checkbox' ||
							$item['type'] === 'radio'
						) {
							$it -> addCustom('value', $k);
							//$it -> addContent($i);
							if (
								(
									$k === $item['options']['default'] &&
									!$returns['value'] &&
									!$returns['array']
								) ||
								$k === $returns['value'] ||
								Objects::match($returns['array'], $k)
							) {
								$it -> addAttr('checked');
							}
						} else {
							$it -> addCustom('value', $i);
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
								(string) $k === (string) $item['options']['default'] &&
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
				
				if (System::typeIterable($attr)) {
					foreach ($attr as $kk => $ii) {
						if ($ii === true) {
							$this -> eget($item['name']) -> addAttr($kk);
						} elseif (System::set($ii)) {
							$this -> eget($item['name']) -> addCustom($kk, $ii);
						}
					}
					unset($kk, $ii);
				}
				
				if (System::set($returns['value'])) {
					if ($tag === 'textarea') {
						$content = $returns['value'];
					} else {
						$this -> eget($item['name']) -> addCustom('value', $returns['value']);
					}
				}
				if (System::set($item['options']['default'])) {
					$this -> eget($item['name']) -> addCustom($item['name'] === 'submit' ? 'value' : 'placeholder', $item['options']['default']);
				}
				
				if ($item['type'] === 'instance') {
					$this -> eget($item['name']) -> addCustom('type', 'text');
					$this -> eget($item['name']) -> addCustom('value', $this -> instance);
				}
				if ($item['type'] === 'spam') {
					$this -> eget($item['name']) -> addCustom('type', 'text');
					$this -> eget($item['name']) -> addCustom('value', null);
				}
				
				$content = $item['options']['before'] . $content . $item['options']['after'];
				
			}
			
			$this -> eget($item['name']) -> addContent($content);
			
			// здесь мы записываем блоки в свойство wrapper объекта
			// дальше они будут браться оттуда
			
			if ($item['options']['block']) {
				$this -> wrappers[ $item['name'] ] = $item['options']['block'];
			}
			
		}
		unset($item);
		
	}
	
	public function printFields() {
		
		if (!System::typeIterable($this -> elements)) {
			return;
		}
		
		$index = -1;
		foreach ($this -> elements as $key => $item) {
			
			// здесь мы читаем блоки из свойства wrapper объекта
			
			$blocks = $this -> wrappers[$key];
			
			$index++;
			if ($key === 'form') {
				continue;
			} elseif ($blocks) {
				
				// здесь фишка в том, что если заданы блоки,
				// то идет их вызов, без печати
				// блоки могут быть указаны как одиночное значение,
				// так и массивом
				// печать элемента должна идти в самом блоке
				
				if (is_array($blocks)) {
					foreach ($blocks as $i) {
						$this -> block($i, [$item, $this -> settings['data'][$index]]);
					}
					unset($i);
				} else {
					$this -> block($blocks, [$item, $this -> settings['data'][$index]]);
				}
				
			} else {
				
				// если блоки не заданы,
				// идет печать элемента
				
				$item -> print();
				
			}
		}
		unset($key, $item);
		
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
			return $i;
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