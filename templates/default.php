<?php

namespace is\Masters\Modules\Isengine\Form;

use is\Helpers\System;
use is\Helpers\Objects;
use is\Helpers\Strings;

use is\Components\Uri;

$uri = Uri::getInstance();

$instance = $object -> get('instance');
$sets = &$object -> settings;

echo print_r($uri, 1);

//echo print_r($object, 1);

//$object -> eget('container') -> addClass('new');
//$object -> eget('container') -> open(true);
//$object -> eget('container') -> close(true);
//$object -> eget('container') -> print();



?>

<form method="post" action="">
<select name="asfd[]" multiple>
<?php
foreach ($d as $key => $item) {
?>
<option<?= System::set($key) ? ' value="' . $key . '"' : null; ?>><?= $item; ?></option>
<?php
}
unset($key, $item);
?>
</select>

<input type="submit">
</form>

<div class="<?= $instance; ?>">
	
	<p><?= $sets['key']; ?></p>
	
	<?php
		if (System::typeIterable($sets['array'])) {
	?>
	<ul>
	<?php
		foreach ($sets['array'] as $item) {
	?>
		<li><?= $item; ?></li>
	<?php
		}
		unset($item);
	?>
	</ul>
	<?php
		}
	?>
	
	<?php $object -> blocks('block'); ?>
	
</div>
