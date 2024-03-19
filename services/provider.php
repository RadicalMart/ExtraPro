<?php
/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\ExtraPro\Extension\ExtraPro;

return new class implements ServiceProviderInterface {

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0.0
	 */
	public function register(Container $container)
	{
		$container->set(PluginInterface::class,
			function (Container $container) {
				// Create plugin class
				$plugin  = PluginHelper::getPlugin('system', 'extrapro');
				$subject = $container->get(DispatcherInterface::class);
				$plugin  = new ExtraPro($subject, (array) $plugin);

				// Set application
				$app = Factory::getApplication();
				$plugin->setApplication($app);

				return $plugin;
			}
		);
	}
};