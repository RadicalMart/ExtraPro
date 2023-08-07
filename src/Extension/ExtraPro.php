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

namespace Joomla\Plugin\System\ExtraPro\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Button\CustomButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class ExtraPro extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  DatabaseDriver
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db = null;

	/**
	 * Plugin functions status.
	 *
	 * @var array|bool[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected array $functions = [
		'child'         => false,
		'images'        => false,
		'unset_modules' => false,
		'toolbar'       => false,
		'preview'       => false,
	];

	/**
	 * Is site yootheme template.
	 *
	 * @var bool|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected ?bool $isYOOtheme = null;

	/**
	 * The plugin id.
	 *
	 * @var    int|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?int $_id = null;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  &$subject  The object to observe.
	 * @param   array                 $config   An optional associative array of configuration settings.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		// Get the plugin id.
		if (isset($config['id']))
		{
			$this->_id = (int) $config['id'];
		}

		// Set wish functions enabled
		foreach (array_keys($this->functions) as $name)
		{
			$this->functions[$name] = ((int) $this->params->get($name) === 1);
		}
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterInitialise'       => 'onAfterInitialise',
			'onAfterRoute'            => 'onAfterRoute',
			'onContentPrepareForm'    => 'onContentPrepareForm',
			'onBeforeCompileHead'     => 'onBeforeCompileHead',
			'onAfterRender'           => 'onAfterRender',
			'onExtensionAfterInstall' => 'onExtensionAfterInstall',
		];
	}

	/**
	 * Listener for the `onAfterInitialise` event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */

	public function onAfterInitialise()
	{
		// Check if YOOtheme Pro is loaded
		if (!class_exists(\YOOtheme\Application::class, false))
		{
			return;
		}

		// Load a single module from the same directory
		\YOOtheme\Application::getInstance()->load(JPATH_PLUGINS . '/system/extrapro/yootheme/loader.php');
	}

	/**
	 * Listener for the `onAfterRoute` event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterRoute()
	{
		$this->enableChildTemplate();
	}

	/**
	 * Listener for the `onContentPrepareForm` event.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm(Event $event)
	{
		/** @var Form $form */
		$form     = $event->getArgument(0);
		$formName = $form->getName();
		$data     = $event->getArgument(1);

		$this->addPreviewButton($formName, $form, $data);
	}

	/**
	 * Listener for the `onBeforeCompileHead` event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onBeforeCompileHead()
	{
		$this->loadConfigWebAsset();
	}

	/**
	 * Listener for the `onAfterRender` event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterRender()
	{
		if ($this->app->isClient('site'))
		{
			if ($this->checkTemplate() && $this->app->input->getCmd('format', 'html') === 'html')
			{
				$body = $this->app->getBody();
				$this->convertImages($body);

				$this->app->setBody($body);
			}
		}
	}

	/**
	 * Listener for the `onExtensionAfterInstall` event.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onExtensionAfterInstall(Event $event)
	{
		$eid = $event->getArgument('eid', false);
		if (empty($eid))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select(['name', 'type', 'element'])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('template'))
			->where($db->quoteName('element') . ' = ' . $db->quote('yootheme'))
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $eid, ParameterType::INTEGER);
		if ($db->setQuery($query, 0, 1)->loadResult())
		{
			$this->enableChildTemplate(true);
		}
	}

	/**
	 * Method to check core child templates functions enabled and fix if need.
	 *
	 * @param   bool  $force  Force enabled
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function enableChildTemplate(bool $force = false)
	{
		if (!$this->functions['child'])
		{
			return;
		}

		$runMethod = $force;
		if (!$runMethod
			&& $this->app->isClient('administrator')
			&& $this->app->input->getCmd('option') !== 'com_templates'
			&& in_array($this->app->input->getCmd('view'), ['templates', 'template']))
		{
			$runMethod = true;
		}

		if (!$runMethod)
		{
			return;
		}

		// Fix db
		$db     = $this->db;
		$query  = $db->getQuery(true)
			->select(['extension_id', 'name'])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('YOOtheme'))
			->where($db->quoteName('type') . ' = ' . $db->quote('template'))
			->where($db->quoteName('element') . ' = ' . $db->quote('yootheme'));
		$update = $db->setQuery($query)->loadObject();
		if (empty($update))
		{
			return;
		}
		$update->name = 'yootheme';
		$db->updateObject('#__extensions', $update, 'extension_id');

		// Fix xml
		$filename = Path::clean(JPATH_ROOT . '/templates/yootheme/templateDetails.xml');
		$xml      = simplexml_load_string(file_get_contents($filename));

		if (isset($xml->element))
		{
			unset($xml->element);
		}

		if ((string) $xml->name === 'YOOtheme')
		{
			$xml->name[0] = 'yootheme';
		}

		if (!isset($xml->inheritable))
		{
			$xml->addChild('inheritable', 1);
		}

		$dom                     = new \DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = true;
		$dom->loadXML($xml->asXML());

		file_put_contents($filename, $dom->saveXML());

		// Fix previews
		$srcFolder  = JPATH_ROOT . '/templates/yootheme';
		$destFolder = JPATH_ROOT . '/media/templates/site/yootheme/images';
		if (!Folder::exists($destFolder))
		{
			Folder::create($destFolder);
		}
		foreach (['template_preview.jpg', 'template_preview.png', 'template_thumbnail.png'] as $image)
		{
			$src  = Path::clean($srcFolder . '/' . $image);
			$dest = Path::clean($destFolder . '/' . $image);

			if (File::exists($src) && !File::exists($dest))
			{
				file_put_contents($dest, file_get_contents($src));
			}
		}
	}

	/**
	 * Method to convert site images.
	 *
	 * @param   string  $body  Current page html.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function convertImages(string &$body = '')
	{
		if (!$this->functions['images'])
		{
			return;
		}

		// Replace images
		$searchBody = $body;

		// Unset picture
		if (preg_match_all('#<picture[^>]*>(.*?)</picture>#s', $searchBody, $matches))
		{
			$pictures = (!empty($matches[0])) ? $matches[0] : [];
			foreach ($pictures as $picture)
			{
				$searchBody = str_replace($picture, '', $searchBody);
			}
		}

		if (preg_match_all('/<img[^>]+>/i', $searchBody, $matches))
		{
			$images = (!empty($matches[0])) ? $matches[0] : [];
			$view   = \YOOtheme\app(\YOOtheme\View::class);

			foreach ($images as $image)
			{
				$skip = false;
				foreach (['no-lazy', 'no-handler', 'uk-img', 'uk-svg', 'data-src', 'srcset'] as $value)
				{
					if (preg_match('/' . $value . '/', $image))
					{
						$skip = true;
						break;
					}
				}

				if ($skip)
				{
					continue;
				}

				if (preg_match_all('/([a-z\-]+)="([^"]*)"/i', $image, $matches2))
				{
					$attrs = [];
					foreach ($matches2[1] as $key => $name)
					{
						$attrs[$name] = $matches2[2][$key];
					}

					$src = (!empty($attrs['src'])) ? $attrs['src'] : '';
					unset($attrs['src']);

					if (!empty($src))
					{
						// Clean src
						$src = trim(str_replace(Uri::root(), '', $src), '/');
						if (!empty($src))
						{
							$src = HTMLHelper::cleanImageURL($src)->url;
						}

						// Get attributes
						$width  = (!empty($attrs['width'])) ? $attrs['width'] : '';
						$height = (!empty($attrs['height'])) ? $attrs['height'] : '';
						if (isset($attrs['width']))
						{
							unset($attrs['width']);
						}
						if (isset($attrs['height']))
						{
							unset($attrs['height']);
						}

						foreach ($attrs as &$attr)
						{
							if (empty($attr))
							{
								$attr = true;
							}
						}

						// Render new image
						$data     = [
							'src'    => $src,
							'width'  => $width,
							'height' => $height,
							'attrs'  => $attrs,
						];
						$newImage = $view('~extrapro/templates/image', $data);

						// Replace image
						$body = str_replace($image, $newImage, $body);
					}
				}
			}
		}
	}

	/**
	 * Method to check is yootheme site template.
	 *
	 * @return bool True if is, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function checkTemplate(): bool
	{
		if ($this->isYOOtheme === null)
		{
			if (!$this->app->isClient('site'))
			{
				$this->isYOOtheme = false;

				return false;
			}

			$template         = $this->app->getTemplate(true);
			$this->isYOOtheme = (!empty($template->parent)) ?
				($template->parent === 'yootheme') : $template->template === 'yootheme';
		}

		return $this->isYOOtheme;
	}

	/**
	 * Method to load ExtraPro config Web asset.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadConfigWebAsset()
	{
		if ($this->app->isClient('administrator')
			&& $this->app->input->getCmd('option') === 'com_plugins'
			&& $this->app->input->getCmd('view') === 'plugin'
			&& $this->app->input->getInt('extension_id') === $this->_id)
		{
			$assets = $this->app->getDocument()->getWebAssetManager();
			$assets->getRegistry()->addExtensionRegistryFile('plg_system_extrapro');
			$assets->useScript('plg_system_extrapro.administrator.config');
		}
	}

	/**
	 * Method to and add preview toolbar.
	 *
	 * @param   string  $formName  The form name.
	 * @param   Form    $form      The form to be altered.
	 * @param   mixed   $data      The associated data for the form.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function addPreviewButton(string $formName, Form $form, $data = [])
	{
		if (!$this->functions['preview'] || !$this->app->isClient('administrator') || !is_object($data))
		{
			return;
		}

		$preview = false;
		if ($formName === 'com_content.article' && !empty($data->id))
		{
			$preview = 'index.php?option=com_content&view=article&id=' . $data->id . ':' . $data->alias . '&catid=' . $data->catid;
			if (!empty($data->language) && $data->language !== '*')
			{
				$preview .= '&lang=' . $data->language;
			}
		}
		elseif ($formName === 'com_categories.categorycom_content' && !empty($data->id))
		{
			$preview = 'index.php?option=com_content&view=category&id=' . $data->id . ':' . $data->alias;
			if (!empty($data->language) && $data->language !== '*')
			{
				$preview .= '&lang=' . $data->language;
			}
		}
		elseif ($formName === 'com_menus.item' && !empty($data->id))
		{
			$preview = 'index.php?Itemid=' . $data->id;
			if (!empty($data->language) && $data->language !== '*')
			{
				$preview .= '&lang=' . $data->language;
			}
		}

		if ($preview)
		{
			$toolbar = Toolbar::getInstance();

			$preview = Route::link('site', $preview);
			$html    = LayoutHelper::render('plugins.system.extrapro.administrator.toolbar.link', [
				'link'  => $preview,
				'text'  => 'PLG_SYSTEM_EXTRAPRO_PREVIEW',
				'icon'  => 'eye',
				'id'    => 'ExtraProPreview',
				'order' => 99,
			]);
			$button  = new CustomButton('ExtraProPreview', 'PLG_SYSTEM_EXTRAPRO_PREVIEW', ['html' => $html]);
			$toolbar->appendButton($button);
		}
	}
}