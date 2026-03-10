<?php
/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     1.1.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

use Joomla\Utilities\ArrayHelper;

\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  array  $buttons  Buttons array.k.
 * @var  string $position Toolbar position.
 *
 */

$pos = '';
if (str_contains($position, 'left'))
{
	$pos = 'right';
}
elseif (str_contains($position, 'right'))
{
	$pos = 'left';
}
elseif (str_contains($position, 'top'))
{
	$pos = 'bottom';
}
elseif (str_contains($position, 'bottom'))
{
	$pos = 'top';
}

$center = (str_contains($position, '-center'));
?>
<div id="ExtraProToolbar"
	 class="uk-position-fixed uk-position-small uk-position-<?php echo $position; ?> <?php echo ($center) ? 'uk-flex' : ''; ?>">
	<?php foreach ($buttons as $button):
		$attributes = (!empty($button['attributes'])) ? $button['attributes'] : [];
		if (!empty($button['href']))
		{
			$attributes['href'] = $button['href'];
		}
		if (!empty($button['title']))
		{
			$attributes['title']      = $button['title'];
			$attributes['uk-tooltip'] = ($pos) ? 'pos:' . $pos : '';
		}
		if (!empty($button['icon']) && !isset($attributes['icon']))
		{
			$attributes['uk-icon'] = 'icon:' . $button['icon'] . '; ratio: 1.2';
		}
		if (!isset($attributes['class']))
		{
			$attributes['class'] = 'uk-icon-button uk-text-danger';
		}
		if (!empty($button['target']))
		{
			$attributes['target'] = $button['target'];
		}
		?>
		<div class="uk-margin-small-<?php echo ($center) ? 'right' : 'bottom'; ?>">
			<a <?php echo ArrayHelper::toString($attributes); ?>></a>
		</div>
	<?php endforeach; ?>
</div>
