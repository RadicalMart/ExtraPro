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

<div class="col-md-3">
	<fieldset class="options-form">
		<legend>YOOtheme</legend>
		<ul class="list-unstyled">
			<?php foreach ($tree as $name => $folder) : ?>
				<li>
					<?php if (is_array($folder))
					{
						echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.folder',
							['name' => $name, 'tree' => $folder]);
					}
					else
					{
						echo LayoutHelper::render('plugins.system.extrapro.administrator.child.overrides.file',
							['name' => $name, 'path' => $folder]);
					} ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
</div>
