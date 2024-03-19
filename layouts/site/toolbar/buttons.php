<?php
/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
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
if (strpos($position, 'left') !== false)
{
	$pos = 'right';
}
elseif (strpos($position, 'right') !== false)
{
	$pos = 'left';
}
elseif (strpos($position, 'top') !== false)
{
	$pos = 'bottom';
}
elseif (strpos($position, 'bottom') !== false)
{
	$pos = 'top';
}

$center = (strpos($position, '-center') !== false);
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
