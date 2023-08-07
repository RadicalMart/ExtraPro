<?php
/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2023 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $link  Button link.
 * @var  string $text  Button text.
 * @var  string $icon  Button icon.
 * @var  bool   $new   Button target.
 * @var  string $id    Button id.
 * @var  int    $order Button order.
 *
 */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = Factory::getApplication()->getDocument()->getWebAssetManager();
$assets->addInlineStyle('joomla-toolbar-button > a[href="' . $link . '"]:before{display:none;};');

$new        = (isset($new)) ? $new : true;
$attributes = [];
if (!empty($id))
{
	$attributes['id'] = $id;
}
if (!empty($class) || !empty($order))
{
	$attributes['class'] = [];
	if (!empty($class))
	{
		$attributes['class'][] = $class;
	}
	if (!empty($order))
	{
		$attributes['class'][] = 'extrapro_toolbar_order';
		$attributes['style']   = 'order: ' . $order . ';   margin-inline-start: 0.75rem;';
	}
	$attributes['class'] = implode(' ', $attributes['class']);
}

$text = Text::_($text);
$link = Route::_($link, false);
?>
<joomla-toolbar-button <?php echo ArrayHelper::toString($attributes); ?>>
	<a href="<?php echo $link; ?>" class="btn btn-small"<?php echo ($new) ? ' target="_blank"' : ''; ?>
	   title="<?php echo htmlspecialchars($text); ?>">
		<span aria-hidden="true" class="icon-<?php echo $icon; ?>"></span>
		<?php echo $text; ?>
	</a>
	<?php if (!empty($order)): ?>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				let button = document.querySelector('#toolbar a[href="<?php echo $link;?>"]');
				if (button) {
					let toolbar = button.closest("#toolbar"),
						first = toolbar.querySelector('joomla-toolbar-button:not(.extrapro_toolbar_order');
					first.style.marginInlineStart = '0';
				}
			});
		</script>
	<?php endif; ?>
</joomla-toolbar-button>