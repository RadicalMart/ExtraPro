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

namespace Joomla\Plugin\System\ExtraPro\Field\UnsetModules;

\defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Field\ListField;

class ComponentsField extends ListField
{
	/**
	 * Method to get the field options.
	 *
	 * @throws  \Exception
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$items = [];

		// Get components
		$componentsFolder = JPATH_ROOT . '/components';
		foreach (Folder::folders(Path::clean($componentsFolder)) as $component)
		{
			$items[]     = $component;
			$viewsFolder = $componentsFolder . '/' . $component . '/tmpl';
			if (Folder::exists($viewsFolder))
			{
				foreach (Folder::folders(Path::clean($viewsFolder)) as $view)
				{
					$items[] = $component . '.' . $view;

					foreach (Folder::files(Path::clean($viewsFolder . '/' . $view), '.php') as $tmpl)
					{
						if (strpos($tmpl, '_') !== false)
						{
							continue;
						}

						$items[] = $component . '.' . $view . '.' . str_replace('.php', '', $tmpl);
					}
				}
			}

			$viewsFolder = $componentsFolder . '/' . $component . '/views';
			if (Folder::exists($viewsFolder))
			{
				foreach (Folder::folders(Path::clean($viewsFolder)) as $view)
				{
					$items[] = $component . '.' . $view;

					foreach (Folder::files(Path::clean($viewsFolder . '/' . $view . '/tmpl'), '.php') as $tmpl)
					{
						if (strpos($tmpl, '_') !== false)
						{
							continue;
						}

						$items[] = $component . '.' . $view . '.' . str_replace('.php', '', $tmpl);
					}
				}
			}
		}

		// Get overrides
		$templatesFolder = JPATH_ROOT . '/templates';
		foreach (Folder::folders(Path::clean($templatesFolder)) as $template)
		{
			$componentsFolder = $templatesFolder . '/' . $template . '/html';
			if (!Folder::exists(Path::clean($componentsFolder)))
			{
				continue;
			}

			foreach (Folder::folders(Path::clean($componentsFolder), 'com_') as $component)
			{
				if (!in_array($component, $items))
				{
					continue;
				}

				$viewsFolder = $componentsFolder . '/' . $component;
				if (!Folder::exists(Path::clean($viewsFolder)))
				{
					continue;
				}

				foreach (Folder::folders(Path::clean($viewsFolder)) as $view)
				{
					if (!in_array($component . '.' . $view, $items))
					{
						continue;
					}

					$viewFolder = $viewsFolder . '/' . $view;
					foreach (Folder::files(Path::clean($viewFolder), '.php') as $tmpl)
					{
						if (strpos($tmpl, '_') !== false)
						{
							continue;
						}

						$key = $component . '.' . $view . '.' . str_replace('.php', '', $tmpl);
						if (!in_array($key, $items))
						{
							$items[] = $key;
						}
					}
				}
			}
		}

		asort($items);
		foreach ($items as $value)
		{
			$parts = explode('.', $value);
			$text  = str_replace('com_', '', $parts[0]);
			if (!empty($parts[1]))
			{
				$text .= ' : ' . $parts[1];
			}
			if (!empty($parts[2]))
			{
				$text .= ' - ' . $parts[2];
			}

			$option        = new \stdClass();
			$option->value = $value;
			$option->text  = ucwords(str_replace('_', ' ', $text));

			$options[] = $option;
		}

		return $options;
	}
}