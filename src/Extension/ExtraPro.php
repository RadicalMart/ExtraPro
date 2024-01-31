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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Button\CustomButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

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
		'child'             => false,
		'images'            => false,
		'unset_modules'     => false,
		'toolbar'           => false,
		'preview'           => false,
		'optimization'      => false,
		'correct_custom_js' => false,
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
	 * Administrator user object.
	 *
	 * @var User|false|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $_administratorUser = null;

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
			'onAfterCleanModuleList'  => 'onAfterCleanModuleList',
			'onRenderModule'          => 'onRenderModule',
			'onBeforeCompileHead'     => 'onBeforeCompileHead',
			'onPageCacheGetKey'       => 'onPageCacheGetKey',
			'onAfterRender'           => 'onAfterRender',
			'onExtensionAfterInstall' => 'onExtensionAfterInstall',
			'onAjaxExtraPro'          => 'onAjax'
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
		$this->loadModuleForm($formName, $form);
	}


	/**
	 * Listener for the `onAfterCleanModuleList` event.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterCleanModuleList(Event $event)
	{
		if (!$this->functions['unset_modules'] || !$this->checkTemplate())
		{
			return;
		}

		$unset   = false;
		$modules = $event->getArgument(0);
		foreach ($modules as $m => $module)
		{
			if ($this->isModuleUnset($module))
			{
				$unset = true;
				unset($modules[$m]);
			}
		}

		if ($unset)
		{
			$event->setArgument(0, array_values($modules));
		}

	}

	/**
	 * Listener for the `onAfterCleanModuleList` event.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRenderModule(Event $event)
	{
		if (!$this->functions['unset_modules'] || !$this->checkTemplate())
		{
			return;
		}

		$module = $event->getArgument(0);
		if ($this->isModuleUnset($module))
		{
			$event->setArgument(0, false);
		}
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
		$this->addYOOthemeChildOverrides();
		$this->addSiteToolbar();
	}

	/**
	 * Listener for the `onPageCacheGetKey` event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onPageCacheGetKey(Event $event)
	{
		$parts = [];
		if ($this->functions['unset_modules'])
		{
			$parts['extrapro_unset_modules'] = ($this->getAdministratorUser()) ? 1 : 0;
		}

		if (empty($parts))
		{
			return;
		}

		$string = [];
		foreach ($parts as $key => $part)
		{
			$string[] = $key . '=' . $part;
		}
		$string = implode('|', $string);

		$result   = $event->getArgument('result', []);
		$result[] = $string;

		$event->setArgument('result', $result);
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
				if (!$this->functions['images'] && !$this->functions['optimization'] && !$this->functions['correct_custom_js'])
				{
					return;
				}

				$body = $this->app->getBody();
				$this->convertImages($body);
				$this->optimization($body);
				$this->correctCustomJS($body);

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
	 * Method to ajax functions.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAjax(Event $event)
	{
		try
		{
			$action = $this->app->input->get('action');
			$method = $action;
			if (empty($action) || !method_exists($this, $method))
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_EXTRAPRO_ERROR_AJAX_METHOD_NOT_FOUND', $method), 500);
			}

			$result = $this->$method();
			$event->setArgument('result', $result);
			$event->setArgument('results', $result);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Method to check core child templates functions enabled and fix if need.
	 *
	 * @param   bool  $run  Force enabled
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function enableChildTemplate(bool $run = false)
	{
		if (!$this->functions['child'])
		{
			return;
		}

		if (!$run && !($this->app->isClient('administrator')
				&& $this->app->input->getCmd('option') === 'com_templates'
				&& in_array($this->app->input->getCmd('view'), ['templates', 'template'])))
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
		if (!empty($update))
		{
			$update->name = 'yootheme';
			$db->updateObject('#__extensions', $update, 'extension_id');
		}

		// Fix xml
		$updateFile = false;
		$filename   = Path::clean(JPATH_ROOT . '/templates/yootheme/templateDetails.xml');
		$xml        = simplexml_load_string(file_get_contents($filename));

		if (isset($xml->element))
		{
			$updateFile = true;
			unset($xml->element);
		}

		if ((string) $xml->name === 'YOOtheme')
		{
			$updateFile   = true;
			$xml->name[0] = 'yootheme';
		}

		if (!isset($xml->inheritable))
		{
			$updateFile = true;
			$xml->addChild('inheritable', 1);
		}

		if ($updateFile)
		{
			$dom                     = new \DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput       = true;
			$dom->loadXML($xml->asXML());

			file_put_contents($filename, $dom->saveXML());
		}

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
			$this->getWebAssetManager()->useScript('plg_system_extrapro.administrator.config');
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
			$html    = LayoutHelper::render('plugins.system.extrapro.administrator.preview.toolbar', [
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

	/**
	 * Method to add YOOtheme overrides to child templates.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addYOOthemeChildOverrides()
	{
		if (!$this->functions['child']
			|| !$this->app->isClient('administrator')
			|| $this->app->input->getCmd('option') !== 'com_templates'
			|| $this->app->input->getCmd('view') !== 'template'
		)
		{
			return;
		}

		$eid      = $this->app->input->getInt('id');
		$db       = $this->db;
		$query    = $db->getQuery(true)
			->select('manifest_cache')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $eid, ParameterType::INTEGER);
		$template = $db->setQuery($query, 0, 1)->loadResult();
		if (!$template)
		{
			return;
		}

		$template = new Registry($template);
		if ($template->get('parent') !== 'yootheme')
		{
			return;
		}

		$assets = $this->getWebAssetManager();
		$assets->useScript('plg_system_extrapro.administrator.overrides');
		$this->app->getDocument()->addScriptOptions('extrapro_overrides', [
			'controller'   => Route::link('administrator',
				'index.php?option=com_ajax&plugin=ExtraPro&group=system&format=json', false),
			'extension_id' => $eid,
		]);
	}

	/**
	 * Method to get overrides files tree
	 *
	 * @throws \Exception
	 *
	 * @return string Overrides files tree.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getOverridesHtml(): string
	{
		$result = '';

		if (!$this->functions['child'])
		{
			return $result;
		}

		if (!$this->app->isClient('administrator') || !$this->app->getIdentity()->authorise('core.admin'))
		{
			return $result;
		}

		$tree = $this->getOverrides();
		if (empty($tree))
		{
			return $result;
		}

		return LayoutHelper::render('plugins.system.extrapro.administrator.overrides.block',
			['tree' => $tree]);
	}

	/**
	 * Method to get overrides files tree.
	 *
	 * @param   string|null  $path    Search path
	 * @param   array        $result  Current tree.
	 *
	 * @return array Overrides files tree.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getOverrides(?string $path = null, array &$result = []): array
	{
		// Add root folders
		if (empty($path))
		{
			$root = JPATH_ROOT . '/templates/yootheme';

			foreach (['html', 'templates'] as $folder)
			{
				$result[$folder] = $this->getOverrides($root . '/' . $folder);
			}

			foreach (['component.php', 'error.php', 'index.php', 'offline.php'] as $file)
			{
				$result[$file] = $root . '/' . $file;
			}

			return $result;
		}

		// Get Folders
		$folders = Folder::folders($path);
		foreach ($folders as $folder)
		{
			if (!isset($result[$folder]))
			{
				$result[$folder] = [];
			}

			$result[$folder] = $this->getOverrides($path . '/' . $folder, $result[$folder]);

			if (empty($result[$folder]))
			{
				unset($result[$folder]);
			}
		}

		// Get files
		$files = Folder::files($path, '.php');
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				$result[$file] = $path . '/' . $file;
			}
		}

		return $result;
	}

	/**
	 * Method to create YOOtheme file override.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function createOverride()
	{
		if (!$this->functions['child'])
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_FUNCTION_DISABLE'), 403);
		}

		if (!$this->app->isClient('administrator') || !$this->app->getIdentity()->authorise('core.admin'))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_ACCESS_DENIED'), 403);
		}

		$file = $this->app->input->getBase64('file');
		if (empty($file))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_FILE_NOT_FOUND'), 404);
		}

		$eid       = $this->app->input->getInt('template_id');
		$db        = $this->db;
		$query     = $db->getQuery(true)
			->select(['element', 'manifest_cache'])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $eid, ParameterType::INTEGER);
		$extension = $db->setQuery($query, 0, 1)->loadObject();
		if (!$extension)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_TEMPLATE_NOT_FOUND'), 404);
		}

		$template = new Registry($extension->manifest_cache);
		if ($template->get('parent') !== 'yootheme')
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_TEMPLATE_IS_NOT_CHILD'), 500);
		}

		$src = base64_decode($file);
		if (empty($src))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_FILE_NOT_FOUND'), 404);
		}

		if (strpos($src, JPATH_ROOT . '/templates/yootheme/') === false)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_ACCESS_DENIED'), 403);
		}

		$dest = Path::clean(str_replace('/templates/yootheme/', '/templates/' . $extension->element . '/', $src));
		$src  = Path::clean($src);

		if (!File::exists($src))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_FILE_NOT_FOUND'), 404);
		}

		if (File::exists($dest))
		{
			File::delete($dest);
		}

		$folder = dirname($dest);
		if (!Folder::exists($folder))
		{
			Folder::create($folder);
		}

		$contents = file_get_contents($src);
		if ($contents === false)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_GET_FILE_CONTENTS'), 404);
		}

		$result = file_put_contents($dest, file_get_contents($src));
		if (!$result)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_EXTRAPRO_ERROR_COPY_FILE'), 500);
		}

		$redirect = Route::link('administrator', 'index.php?option=com_templates&view=template&id=' . $eid, false);
		$this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_EXTRAPRO_OVERRIDE_CREATED',
			str_replace(JPATH_ROOT, '', $dest)));
		$this->app->redirect($redirect);
	}

	/**
	 * Method to add site toolbar script.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addSiteToolbar()
	{
		if (!$this->functions['toolbar'] || !$this->checkTemplate())
		{
			return;
		}

		$context = '';
		$option  = $this->app->input->get('option');
		$view    = $this->app->input->get('view');
		$id      = $this->app->input->getInt('id');
		if ($option === 'com_content')
		{
			if ($view === 'article')
			{
				$context = 'com_content.article.' . $id;
			}
			elseif (in_array($view, ['category', 'categories']) && $id > 1)
			{
				$context = 'com_content.category.' . $id;
			}
		}
		elseif ($option === 'com_radicalmart')
		{
			if ($view === 'product')
			{
				$context = 'com_radicalmart.product.' . $id;
			}
			elseif (in_array($view, ['category', 'categories']))
			{
				$context = 'com_radicalmart.category.' . $id;
			}
		}

		// Trigger `onExtraProGetToolbarContext` event
		$this->app->triggerEvent('onExtraProGetToolbarContext', [&$context]);

		$assets = $this->getWebAssetManager();
		$assets->useScript('plg_system_extrapro.site.toolbar');
		$this->app->getDocument()->addScriptOptions('extrapro_toolbar', [
			'controller' => Route::link('site',
				'index.php?option=com_ajax&plugin=ExtraPro&group=system&format=json', false),
			'context'    => $context,
		]);
	}

	/**
	 * Method to get site toolbar html.
	 *
	 * @return string Site toolbar html.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getToolbarHtml(): string
	{
		if (!$this->functions['toolbar'] || !$this->checkTemplate())
		{
			return '';
		}

		// Check access
		$user = $this->getAdministratorUser();
		if (!$user)
		{
			return '';
		}
		$group = (int) $this->params->get('toolbar_user_group', 0);
		if (!empty($access) && !in_array($group, $user->getAuthorisedGroups()))
		{
			return '';
		}

		// Prepare buttons
		$return  = urldecode($this->app->input->getString('return'));
		$buttons = [
			'customizer'    => [
				'id'       => 'customizer',
				'href'     => Route::link('administrator',
					'index.php?option=com_ajax&p=customizer&format=html'
					. '&templateStyle=' . $this->app->input->getInt('style_id')
					. '&site=' . $return
					. '&return=' . $return),
				'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_CUSTOMIZER'),
				'icon'     => 'settings',
				'ordering' => 999,
			],
			'administrator' => [
				'id'       => 'administrator',
				'href'     => Route::link('administrator', 'index.php'),
				'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_ADMINISTRATOR'),
				'icon'     => 'joomla',
				'ordering' => 1000,
				'target'   => '_blank',
			],
		];

		$context = $this->app->input->get('context');
		$paths   = explode('.', $context);
		if (!empty($paths[0]))
		{
			if ($paths[0] === 'com_content')
			{
				if (!empty($paths[1]))
				{
					if ($paths[1] === 'article' && !empty($paths[2]))
					{
						$buttons['article_builder'] = [
							'id'       => 'article_builder',
							'href'     => Route::link('administrator',
								'index.php?option=com_ajax&p=customizer&section=builder&format=html'
								. '&templateStyle=' . $this->app->input->getInt('style_id')
								. '&site=' . $return
								. '&return=' . $return),
							'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_CONTENT_ARTICLE_BUILDER'),
							'icon'     => 'uikit',
							'ordering' => 1,
						];

						$buttons['article_administrator'] = [
							'id'       => 'article_administrator',
							'href'     => Route::link('administrator',
								'index.php?option=com_content&task=article.edit&id=' . $paths[2]),
							'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_CONTENT_ARTICLE_ADMINISTRATOR'),
							'icon'     => 'pencil',
							'target'   => '_blank',
							'ordering' => 2,
						];
					}
					elseif ($paths[1] === 'category' && !empty($paths[2]) && (int) $paths[2] > 1)
					{
						$buttons['category_administrator'] = [
							'id'       => 'category_administrator',
							'href'     => Route::link('administrator',
								'index.php?option=com_categories&task=category.edit&extension=com_content&id='
								. $paths[2]),
							'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_CONTENT_CATEGORY_ADMINISTRATOR'),
							'icon'     => 'pencil',
							'target'   => '_blank',
							'ordering' => 2,
						];
					}
				}
			}
			elseif ($paths[0] === 'com_radicalmart')
			{
				if ($paths[1] === 'product' && !empty($paths[2]))
				{
					$buttons['product_administrator'] = [
						'id'       => 'product_administrator',
						'href'     => Route::link('administrator',
							'index.php?option=com_radicalmart&task=product.edit&id=' . $paths[2]),
						'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_RADICALMART_PRODUCT_ADMINISTRATOR'),
						'icon'     => 'pencil',
						'target'   => '_blank',
						'ordering' => 2,
					];
				}
				elseif ($paths[1] === 'category' && !empty($paths[2]))
				{
					$buttons['product_administrator'] = [
						'id'       => 'product_administrator',
						'href'     => Route::link('administrator',
							'index.php?option=com_radicalmart&task=category.edit&id=' . $paths[2]),
						'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_RADICALMART_CATEGORY_ADMINISTRATOR'),
						'icon'     => 'pencil',
						'target'   => '_blank',
						'ordering' => 2,
					];
				}
			}
		}

		if (ComponentHelper::isEnabled('com_quantummanager'))
		{
			$buttons['quantummanager'] = [
				'id'       => 'quantummanager',
				'href'     => Route::link('administrator', 'index.php?option=com_quantummanager'),
				'title'    => Text::_('PLG_SYSTEM_EXTRAPRO_TOOLBAR_QUANTUMMANAGER'),
				'icon'     => 'nut',
				'target'   => '_blank',
				'ordering' => 998,
			];
		}

		// Trigger `onExtraProGetToolbarButtons` event
		$this->app->triggerEvent('onExtraProGetToolbarButtons', [$context, &$buttons]);

		usort($buttons, function ($a, $b) {
			return $a['ordering'] <=> $b['ordering'];
		});

		$displayData = [
			'buttons'  => $buttons,
			'position' => $this->params->get('toolbar_position', 'center-right'),
		];

		return LayoutHelper::render('plugins.system.extrapro.site.toolbar.buttons', $displayData);
	}

	/**
	 * Method to get WebAssetManager with load ExtraPro Plugin.
	 *
	 * @return WebAssetManager Joomla WebAsset Manager.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getWebAssetManager(): WebAssetManager
	{
		$assets = $this->app->getDocument()->getWebAssetManager();
		$assets->getRegistry()->addExtensionRegistryFile('plg_system_extrapro');

		return $assets;
	}

	/**
	 * Check is need unset module.
	 *
	 * @param   mixed  $module  The module object.
	 *
	 * @return bool True if need unset, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function isModuleUnset($module): bool
	{
		if (!$this->functions['unset_modules'])
		{
			return false;
		}

		if (!is_object($module))
		{
			return true;
		}

		$params = ($module->params instanceof Registry) ? $module->params : new Registry($module->params);
		if (!empty($params->get('extrapro_unset_modules_components')))
		{
			$values = (new Registry($params->get('extrapro_unset_modules_components')))->toArray();

			$option = $this->app->input->getCmd('option');
			if (in_array($option, $values))
			{
				return true;
			}

			$view = $option . '.' . $this->app->input->getCmd('view');
			if (in_array($view, $values))
			{
				return true;
			}

			$layout = $view . '.' . $this->app->input->getCmd('layout', 'default');
			if (in_array($layout, $values))
			{
				return true;
			}
		}

		if ((int) $params->get('extrapro_unset_modules_administrator') === 1 && $this->getAdministratorUser())
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to get current login administrator user object.
	 *
	 * @return User|false Administrator user object if found, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getAdministratorUser()
	{
		if ($this->_administratorUser === null)
		{
			$this->_administratorUser = false;

			$sessions = [];
			foreach ($this->app->input->cookie->getArray() as $key => $value)
			{
				if (strlen($key) === 32)
				{
					$sessions[] = $value;
				}
			}
			if (empty($sessions))
			{
				return false;
			}

			$db         = $this->db;
			$query      = $db->getQuery(true)
				->select('userid')
				->from($db->quoteName('#__session'))
				->whereIn($db->quoteName('session_id'), $sessions, ParameterType::STRING)
				->where('client_id = 1')
				->where('userid > 0');
			$identifier = (int) $db->setQuery($query, 0, 1)->loadResult();

			if ($identifier === 0)
			{
				return $this->_administratorUser;
			}
			$user = new User($identifier);

			if (empty($user->id))
			{
				return $this->_administratorUser;
			}

			if (!$user->authorise('core.login.admin'))
			{
				return $this->_administratorUser;
			}

			$this->_administratorUser = $user;
		}

		return $this->_administratorUser;
	}

	/**
	 * Method load administrator module form.
	 *
	 * @param   string  $formName  The form name.
	 * @param   Form    $form      The form to be altered.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadModuleForm(string $formName, Form $form)
	{
		if (!$this->functions['unset_modules'] || $formName !== 'com_modules.module')
		{
			return;
		}

		$form->loadFile(JPATH_PLUGINS . '/system/extrapro/forms/com_modules.module.xml');
	}

	/**
	 * Method to convert site images.
	 *
	 * @param   string  $body  Current page html.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function optimization(string &$body = '')
	{
		if (!$this->functions['optimization'])
		{
			return;
		}

		$items = (new Registry($this->params->get('optimization_items')))->toArray();
		if (empty($items))
		{
			return;
		}

		if (!preg_match('|<head>(.*)</head>|si', $body, $matches))
		{
			return;
		}

		$search  = $matches[1];
		$replace = $search;
		$footer  = [];

		$needReplace = false;
		foreach ($items as $item)
		{
			if (empty($item['source']))
			{
				continue;
			}

			$source = str_replace('/', '\\/', $item['source']);
			if ($item['type'] === 'script')
			{
				preg_match_all('#<script.*src=".*' . $source . '.*".*<\/script>#i', $search, $find);
			}
			else
			{
				preg_match_all('#<link.*href=".*' . $source . '.*".*rel="stylesheet".*/>#i', $search, $find);
			}

			if (empty($find) || empty($find[0]))
			{
				continue;
			}

			foreach ($find[0] as $value)
			{
				if ($item['action'] === 'footer')
				{
					$footer[] = $value;
				}
				$needReplace = true;
				$replace     = str_replace($value, '', $replace);
			}
		}

		if (!$needReplace)
		{
			return;
		}

		$lines = explode(PHP_EOL, $replace);
		foreach ($lines as $l => $line)
		{
			if (empty(trim($line)))
			{
				unset($lines[$l]);
			}
		}
		$replace = implode(PHP_EOL, $lines);
		$body    = str_replace($search, $replace, $body);

		if (!empty($footer))
		{
			$footer = PHP_EOL . implode(PHP_EOL, $footer);
			$body   = str_replace('</body>', PHP_EOL . $footer . PHP_EOL . '</body>', $body);
		}
	}

	/**
	 * Correct template custom.js src.
	 *
	 * @param   string  $body  Current page html.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function correctCustomJS(string &$body = '')
	{
		if (!$this->functions['correct_custom_js'])
		{
			return;
		}

		$pattern = '/<script src="(\/templates\/(yootheme(?:_[^\/]+)?)\/js\/custom\.js(?:\?[^"]*)?)">/';
		preg_match($pattern, $body, $matches);
		if (empty($matches) || empty($matches[1]) || empty($matches[2]))
		{
			return;
		}

		$mediaVersion = $this->app->getDocument()->getMediaVersion();
		$src          = HTMLHelper::script('templates/' . $matches[2] . '/js/custom.js', [
			'pathOnly' => true,
			'relative' => false,
		], ['version' => 'auto']);
		$src          .= (strpos($src, '?') === false) ? '?' . $mediaVersion : '&' . $mediaVersion;
		$body         = str_replace($matches[1], $src, $body);
	}
}