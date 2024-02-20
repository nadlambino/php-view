<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\Container\Container;
use Inspira\Container\Exceptions\NonInstantiableBindingException;
use Inspira\Container\Exceptions\UnresolvableBindingException;
use Inspira\Container\Exceptions\UnresolvableBuiltInTypeException;
use Inspira\Container\Exceptions\UnresolvableMissingTypeException;
use Inspira\Contracts\Renderable;
use Inspira\View\Components\ComponentInterface;
use Inspira\View\Exceptions\ExtendedViewLayoutNotFoundException;
use Inspira\View\Exceptions\RawViewPathNotFoundException;
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
	 * An array of block contents where key is the block name and value is the content
	 *
	 * @var array $codeBlocks
	 */
	private array $codeBlocks = [];

	/**
	 * Compiled content of the view file including from extended and included view files
	 * View markers such as block content, yield, echoes, unescaped echoes, etc. were swapped
	 * with their corresponding values
	 *
	 * @var string $fileContents
	 */
	private string $fileContents = '';

	/**
	 * The content of the cached file
	 *
	 * @var string $cachedContents
	 */
	private string $cachedContents = '';

	/**
	 * The view cache filename
	 *
	 * @var string $cacheFilename
	 */
	private string $cacheFilename;

	/**
	 * The file to render when the view file is not existing
	 * Only render this file when the $throwNotFound is false
	 *
	 * @var string $notFoundView
	 */
	private string $notFoundView = 'resources/errors/404.php';

	public function __construct(protected string $viewsPath = '', string $cachePath = 'cache', protected bool $useCached = false, protected bool $throwNotFound = true, protected ?Container $container = null)
	{
		$this->cacheDirectory = $cachePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
		$this->container ??= Container::getInstance();
		self::setInstance($this);
	}

	public function setViewsPath(string $path): static
	{
		$this->viewsPath = trim($path, DIRECTORY_SEPARATOR);

		return $this;
	}

	private static function setInstance(View $instance)
	{
		self::$instance = $instance;
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
		try {
			$this->createCacheFile($view);
		} catch (ViewNotFoundException $exception) {
			if ($this->throwNotFound) {
				throw $exception;
			}

			$file = $this->notFoundView;
		}

		$this->cachedContents = self::requireView($file ?? $this->cacheFilename, $data);

		return $this;
	}

	public function component(ComponentInterface|string $component, array $data = []): self
	{
		if ($component instanceof ComponentInterface) {
			return $component->setComponentProps($data)->render();
		}

		if (
			class_exists($component)
			&& ($componentInstance = $this->container->make($component))
			&& $componentInstance instanceof ComponentInterface
		) {
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
	 * @return $this
	 */
	public function html(string $html, array $data = []): self
	{
		if (!isset($this->cacheFilename)) {
			$this->generateCacheFilename((string)microtime());
		}

		$this->fileContents = $html;

		$this->compileComponents()
			->compileBlocks()
			->compileYields()
			->compileEscapedEchos()
			->compileUnescapedEchos()
			->save();

		$this->cachedContents = self::requireView($this->cacheFilename, $data);

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
		$path = $this->getViewFile($path, false);
		if (!file_exists($path)) {
			throw new RawViewPathNotFoundException("Raw view path `$path` is not found.");
		}

		$this->fileContents = self::requireView($path, $data);
		$this->generateCacheFilename($path);
		$this->save();
		$this->cachedContents = $this->fileContents;

		return $this;
	}

	public function cacheFilename(string $filename): self
	{
		$this->generateCacheFilename($filename);

		return $this;
	}

	public function render(): string
	{
		$contents = $this->cachedContents;
		$this->clean();

		return $contents;
	}

	private function clean()
	{
		$this->cachedContents = '';
		$this->fileContents = '';
		$this->codeBlocks = [];
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
		$file = $this->viewsPath . DIRECTORY_SEPARATOR . $view;
		$file = str_ends_with($file, '.php') ? $file : $file . '.php';
		if (!file_exists($file)) {
			throw new ViewNotFoundException("NotFoundView view `$view` is not found.");
		}

		$this->notFoundView = $file;

		return $this;
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
	 * @return void
	 * @throws ViewNotFoundException
	 */
	private function createCacheFile(string $view): void
	{
		$file = $this->getViewFile($view);
		if (!file_exists($file)) {
			throw new ViewNotFoundException(sprintf("View `%s` is not found", $view));
		}

		if (!file_exists($this->cacheDirectory)) {
			mkdir($this->cacheDirectory, 0744, true);
		}

		$this->generateCacheFilename($file);
		if ($this->useCached && file_exists($this->cacheFilename)) {
			return;
		}

		$this->compileIncludedFile($file)
			->compileComponents()
			->compileBlocks()
			->compileYields()
			->compileEscapedEchos()
			->compileUnescapedEchos()
			->save();
	}

	/**
	 * Generate a base64 encoded filename and get only the last 100 characters
	 *
	 * @param string $file
	 * @return void
	 */
	private function generateCacheFilename(string $file): void
	{
		$filename = str_replace([DIRECTORY_SEPARATOR, '\\', '/', '..'], ['_'], $file);
		$encoded = base64_encode($filename);
		$this->cacheFilename = $this->cacheDirectory . substr($encoded, -100);
	}

	/**
	 * Get the full path of given view file
	 *
	 * @param string $view
	 * @param bool $fromViewsPath
	 * @return string
	 */
	private function getViewFile(string $view, bool $fromViewsPath = true): string
	{
		$file = match (true) {
			$fromViewsPath === false => $view,
			str_contains($view, $this->viewsPath) => $view,
			default => $this->viewsPath . DIRECTORY_SEPARATOR . $view
		};
		$file = str_replace(' ', '', $file);

		return !str_ends_with($file, '.php') ? $file . '.php' : $file;
	}

	/**
	 * Recursively compile all include/extend files
	 *
	 * @param string $file
	 * @return self
	 * @throws ExtendedViewLayoutNotFoundException
	 */
	private function compileIncludedFile(string $file): self
	{
		$this->fileContents = $this->includeFiles($file);

		return $this;
	}

	/**
	 * Recursively resolve the included files and it's code blocks
	 *
	 * @param string $file
	 * @return string
	 * @throws
	 */
	private function includeFiles(string $file): string
	{
		$file = $this->getViewFile($file);
		if (!file_exists($file)) {
			throw new ExtendedViewLayoutNotFoundException("Extended view `$file` is not found");
		}

		$contents = file_get_contents($file);

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

	/**
	 * Process the content between the <!-- block --> and <!-- endblock --> markers
	 *
	 * @return $this
	 */
	private function compileBlocks(): self
	{
		preg_match_all('/<!--\s*block\s*(.*?)\s*-->(.*?)<!--\s*endblock\s*-->/is', $this->fileContents, $matches, PREG_SET_ORDER);

		foreach ($matches as $value) {
			$marker = $value[0] ?? '';
			$blockName = $value[1] ?? '';
			$content = $value[2] ?? '';
			$this->codeBlocks[$blockName] = $content;

			// Remove block marker
			$this->fileContents = str_replace($marker, '', $this->fileContents);
		}

		return $this;
	}

	/**
	 * Replace <!-- yield --> markers with block's contents
	 *
	 * @return $this
	 */
	private function compileYields(): self
	{
		foreach ($this->codeBlocks as $block => $value) {
			$this->fileContents = preg_replace('/<!--\s*yield\s*' . $block . '\s*-->/i', $value, $this->fileContents);
		}

		// Remove unmatched yield markers
		$this->fileContents = preg_replace('/<!--\s*yield\s*(.*?)\s*-->/i', '', $this->fileContents);

		return $this;
	}

	/**
	 * Convert {!! ... !!} expressions into PHP echo statements
	 *
	 * @return $this
	 */
	private function compileUnescapedEchos(): self
	{
		$this->fileContents = preg_replace(
			'~\{!!\s*(.+?)\s*!!}~is',
			'<?php echo $1 ?>',
			$this->fileContents
		);

		return $this;
	}

	/**
	 * Convert {{ ... }} expressions into PHP echo htmlentities statements
	 *
	 * @return $this
	 */
	private function compileEscapedEchos(): self
	{
		$this->fileContents = preg_replace(
			'~\{{\s*(.+?)\s*}}~is',
			'<?php echo is_null($1) ? $1 : htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>',
			$this->fileContents
		);

		return $this;
	}

	/**
	 * Save file into cache
	 *
	 * @return void
	 */
	private function save(): void
	{
		file_put_contents(
			$this->cacheFilename,
			'<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $this->fileContents
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
