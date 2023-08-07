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
<a href="#" class="folder-url">
	<span class="icon-folder icon-fw" aria-hidden="true"></span>&nbsp;<?php echo $name; ?>
</a>
<ul class="list-unstyled hidden">
	<?php foreach ($tree as $name => $value) : ?>
		<li>
			<?php if (is_array($value))
			{
				echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.folder',
					['name' => $name, 'tree' => $value]);
			}
			elseif (!empty($value))
			{
				echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.file',
					['name' => $name, 'path' => $value]);
			} ?>
		</li>
	<?php endforeach; ?>
</ul>