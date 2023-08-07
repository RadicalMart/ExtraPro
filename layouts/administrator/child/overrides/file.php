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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

extract($displayData);

$input = Factory::getApplication()->input;

$url = 'index.php?option=com_ajax&plugin=ExtraPro&group=system&format=html&action=createOverride'
	. '&template_id=' . Factory::getApplication()->input->getInt('id')
	. '&file=' . base64_encode($path);
?>

<a href="<?php echo Route::_($url, false); ?>">
	<span class="icon-copy icon-fw" aria-hidden="true"></span>&nbsp;<?php echo $name; ?>
</a>