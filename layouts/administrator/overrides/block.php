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
 * @var  array $tree Folders and files tree.
 *
 */
?>
<div class="col-md-3">
	<fieldset class="options-form">
		<legend>YOOtheme</legend>
		<?php echo LayoutHelper::render('plugins.system.extrapro.administrator.overrides.folder',
			['tree' => $tree]); ?>
	</fieldset>
</div>