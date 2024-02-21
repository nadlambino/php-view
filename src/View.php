<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\Container\Container;
use Inspira\Contracts\Renderable;
use Inspira\View\Components\ComponentInterface;
use Inspira\View\Components\ComponentParser;
use Inspira\View\Exceptions\ExtendedViewLayoutNotFoundException;
use Inspira\View\Exceptions\ViewNotFoundException;

/**
 * @author Ronald Lambino
 */
class View implements Renderable
{
	use Component;

	/**
	 * Current view instance
	 *
	 * @var View $instance
	 */
	private static View $instance;

	/**
	 * The parent directory where cached views are stored
	 * The cached files will be stored under `/views`
	 *
	 * @var string $cacheDirectory
	 */
	private string $cacheDirectory;

	/**
	 * The content of the cached file
	 *
	 * @var string $contents
	 */
	private string $contents = '';

	/**
	 * The file to render when the view file is not existing
	 * Only render this file when the $throwNotFound is false
	 *
	 * @var string $notFoundView
	 */
	private string $notFoundView = 'resources/errors/404.php';

	public function __construct(
		protected string $viewsPath = '',
		string $cachePath = 'cache',
		protected bool $useCached = false,
		protected bool $throwNotFound = true,
		protected ?Container $container = null
	)
	{
		$this->cacheDirectory = $cachePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
		$this->container ??= Container::getInstance();
		self::$instance = $this;
	}

	/**
	 * Set the view to render when the view file is not found
	 *
	 * @param string $view
	 * @return $this
	 * @throws
	 */
	public function setNotFoundView(string $view): static
	{
		$file = str_ends_with($view, '.php') ? $view : $view . '.php';

		if (!file_exists($file)) {
			throw new ViewNotFoundException("NotFoundView view `$view` is not found.");
		}

		$this->notFoundView = $file;

		return $this;
	}

	public function setViewsPath(string $path): static
	{
		$this->viewsPath = trim($path, DIRECTORY_SEPARATOR);

		return $this;
	}

	public static function getInstance(): View
	{
		return self::$instance ?? new self();
	}

	public function __toString(): string
	{
		return $this->render();
	}

	/**
	 * Create a cache file of the view file with extracted data as variables
	 *
	 * @param string $view
	 * @param array $data
	 * @return self
	 * @throws ViewNotFoundException
	 */
	public function make(string $view, array $data = []): self
	{
		$this->contents = self::requireView(
			$this->createCacheFile($view),
			$data
		);

		return $this;
	}

	public function component(ComponentInterface|string $component, array $data = []): self
	{
		if ($component instanceof ComponentInterface) {
			return $component->setComponentProps($data)->render();
		}

		if (class_exists($component) && ($componentInstance = $this->container->make($component)) && $componentInstance instanceof ComponentInterface) {
			return $componentInstance->setComponentProps($data)->render();
		}

		$view = trim($this->componentViewsPath . DIRECTORY_SEPARATOR . $component, DIRECTORY_SEPARATOR);

		return $this->make($view, $data);
	}

	/**
	 * Render a view from the given html.
	 *
	 * @param string $html
	 * @param array $data
	 * @param string $filename
	 * @return $this
	 * @throws ViewNotFoundException
	 */
	public function html(string $html, array $data = [], string $filename = ''): self
	{
		try {
			if (empty($filename)) {
				$filename = $this->createEncodedFilename((string)microtime());
			}

			if ($this->useCached && file_exists($filename)) {
				$this->contents = self::requireView($filename, $data);

				return $this;
			}

			$contents = $this->includeFiles($html, true);
			$contents = (new ComponentParser($this->container, $this, $contents, $this->prefix))->parse();
			$contents = (new ViewParser($contents))->parse();

			$this->save($filename, $contents);
		} catch (ViewNotFoundException $exception) {
			if ($this->throwNotFound) {
				throw $exception;
			}

			$filename =  $this->notFoundView;
		}

		$this->contents = self::requireView($filename, $data);

		return $this;
	}

	/**
	 * @param string $path
	 * @param array $data
	 * @return $this
	 * @throws
	 */
	public function raw(string $path, array $data = []): self
	{
		$this->contents = self::requireView(
			$this->createCacheFile($path, false),
			$data
		);

		return $this;
	}

	public function render(): string
	{
		$contents = $this->contents;
		$this->contents = '';

		return $contents;
	}

	public function clearCache(): bool
	{
		foreach (scandir($this->cacheDirectory) as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$path = $this->cacheDirectory . $file;
			if (!is_file($path)) {
				continue;
			}

			$deleted = unlink($path);
			if (!$deleted) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Read the content of the view file
	 *
	 * @param string $view
	 * @param bool $fromViews
	 * @return string
	 * @throws ExtendedViewLayoutNotFoundException
	 * @throws ViewNotFoundException
	 */
	private function createCacheFile(string $view, bool $fromViews = true): string
	{
		try {
			$file = $this->getFullFilePath($view, $fromViews);

			if (!file_exists($file)) {
				throw new ViewNotFoundException(sprintf("View `%s` is not found", $view));
			}

			if (!file_exists($this->cacheDirectory)) {
				mkdir($this->cacheDirectory, 0744, true);
			}

			$filename = $this->createEncodedFilename($file);

			if ($this->useCached && file_exists($filename)) {
				return $file;
			}

			$contents = $this->includeFiles($file);
			$contents = (new ComponentParser($this->container, $this, $contents, $this->prefix))->parse();
			$contents = (new ViewParser($contents))->parse();

			$this->save($filename, $contents);

			return $filename;
		} catch (ViewNotFoundException $exception) {
			if ($this->throwNotFound) {
				throw $exception;
			}

			return $this->notFoundView;
		}
	}

	public function createEncodedFilename(string $filename): string
	{
		$filename = str_replace([DIRECTORY_SEPARATOR, '\\', '/', '..'], ['_'], $filename);
		$encoded = base64_encode($filename);

		return $this->cacheDirectory . substr($encoded, -100);
	}

	/**
	 * Get the full path of given view file
	 *
	 * @param string $view
	 * @param bool $fromViews
	 * @return string
	 */
	private function getFullFilePath(string $view, bool $fromViews = true): string
	{
		$file = match (true) {
			$fromViews === false => $view,
			str_contains($view, $this->viewsPath) => $view,
			default => $this->viewsPath . DIRECTORY_SEPARATOR . $view
		};

		$file = str_replace(' ', '', $file);

		return !str_ends_with($file, '.php') ? $file . '.php' : $file;
	}

	/**
	 * Recursively resolve the included files and it's code blocks
	 *
	 * @param string $file
	 * @param bool $asHtml
	 * @return string
	 * @throws ExtendedViewLayoutNotFoundException
	 */
	private function includeFiles(string $file, bool $asHtml = false): string
	{
		$contents = $this->getFileContents($file, $asHtml);

		// Find and replace <!-- include|extend -->
		preg_match_all('/<!--\s*(extend|include)\s+([a-zA-Z\d_\/-]+)\s*-->/i', $contents, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			// Recursively include files
			$includedFileContents = $this->includeFiles(end($value));

			if (empty($includedFileContents)) {
				continue;
			}

			$contents = str_replace($value[0], $includedFileContents, $contents);
		}

		return $contents ?: '';
	}

	private function getFileContents(string $fileOrContents, bool $asHtml): string
	{
		if ($asHtml === true) {
			return $fileOrContents;
		}

		$file = $this->getFullFilePath($fileOrContents);

		if (!file_exists($file)) {
			throw new ExtendedViewLayoutNotFoundException("Extended view `$file` is not found");
		}

		return file_get_contents($file);
	}

	/**
	 * Save file into cache
	 *
	 * @param string $filename
	 * @param string $contents
	 * @return void
	 */
	private function save(string $filename, string $contents): void
	{
		file_put_contents(
			$filename,
			'<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $contents
		);
	}

	/**
	 * Require view file and extract the data to make it available within the required file
	 * Used static method so that the `$this` variable won't be available in the view file
	 *
	 * @param string $path
	 * @param array $data
	 * @return string
	 */
	private static function requireView(string $path, array $data = []): string
	{
		extract($data);
		ob_start();
		require $path;

		return (string)ob_get_clean();
	}
}
