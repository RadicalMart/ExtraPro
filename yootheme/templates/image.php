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


/**
 * Templates variables.
 * -----------------
 * @var  string       $src    Image source.
 * @var  int          $width  Image width.
 * @var  int          $height Image height.
 * @var  array        $attrs  Image attributes.
 * @var  string|array $url    Legacy image url parameters.
 */

// Legacy convert
if (!empty($url))
{
	if (is_array($url))
	{
		$src = $url[0];
		if (isset($url['thumbnail']))
		{
			$width  = (isset($url['thumbnail'][0])) ? $url['thumbnail'][0] : '';
			$height = (isset($url['thumbnail'][1])) ? $url['thumbnail'][1] : '';
		}
	}
	else
	{
		$src = $url;
	}
}

// Check params
if (empty($src))
{
	return;
}
if (!isset($width))
{
	$width = '';
}
if (!isset($height))
{
	$height = '';
}
if (!isset($attrs))
{
	$attrs = [];
}

$attrs['uk-img'] = true;
if ($this->isImage($src) === 'gif')
{
	$attrs['uk-gif'] = true;
}

// Prepare params
if ($this->isImage($src) === 'svg')
{
	$url   = $src;
	$attrs = array_merge($attrs, compact('width', 'height'));
}
else
{
	$url = [$src];
	if ($width || $height)
	{
		$url['thumbnail'] = array($width, $height);
	}
	$url['srcset'] = true;
}

// Display image
echo $this->image($url, $attrs);
