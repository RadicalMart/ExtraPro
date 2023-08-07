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

use Joomla\CMS\Layout\LayoutHelper;

\defined('_JEXEC') or die;

extract($displayData);
?>
<a href="#" class="folder-url">
	<span class="icon-folder icon-fw" aria-hidden="true"></span>&nbsp;<?php echo $name; ?>
</a>
<ul class="list-unstyled hidden">
	<?php foreach ($tree as $name => $folder) : ?>
		<li>
			<?php if (is_array($folder))
			{
				echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.folder',
					['name' => $name, 'tree' => $folder]);
			}
			elseif (!empty($folder))
			{
				echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.file',
					['name' => $name, 'path' => $folder]);
			} ?>
		</li>
	<?php endforeach; ?>
</ul>