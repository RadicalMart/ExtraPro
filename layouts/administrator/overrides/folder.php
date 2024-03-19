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

\defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  array  $tree Folders and files tree.
 * @var  string $name Folder name.
 *
 */
?>
<?php if (!empty($name)): ?>
	<a href="#" class="folder-url">
		<span class="icon-folder icon-fw" aria-hidden="true"></span>&nbsp;<?php echo $name; ?>
	</a>
<?php endif; ?>
<ul class="list-unstyled <?php if (!empty($name)) echo 'hidden'; ?>">
	<?php foreach ($tree as $key => $value) :
		if (empty($value))
		{
			continue;
		}
		?>
		<li>
			<?php echo (is_array($value))
				? LayoutHelper::render('plugins.system.extrapro.administrator.overrides.folder',
					['name' => $key, 'tree' => $value])
				: LayoutHelper::render('plugins.system.extrapro.administrator.overrides.file',
					['name' => $key, 'path' => $value]);
			?>
		</li>
	<?php endforeach; ?>
</ul>