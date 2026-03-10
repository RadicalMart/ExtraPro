<?php
/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     1.1.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use YOOtheme\Builder;
use YOOtheme\Path;
use YOOtheme\View;

return [
	'extend' => [
		View::class => function (View $view) {
			$view->addLoader(function ($name, $parameters, callable $next) {
				if (str_contains($name, '~extrapro'))
				{
					$name = str_replace('~extrapro', __DIR__, $name);
				}

				return $next($name, $parameters);
			});
		},

		Builder::class => function (Builder $builder) {
			$builder->addTypePath(Path::get(__DIR__ . '/element/*/element.json'));
		}
	]
];