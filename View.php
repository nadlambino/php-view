<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\Contracts\Renderable;
use Inspira\View\Exceptions\ViewNotFoundException;

/**
 * @author Ronald Lambino
 */
class View implements Renderable
{
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
	private string $notFoundView = './resources/errors/404';

	public function __construct(protected string $viewsPath, string $cachePath, protected bool $useCached = false, protected bool $throwNotFound = true)
	{
		$this->cacheDirectory = $cachePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
	}

	public function __toString(): string
	{
		return $this->cachedContents;
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

	public function render(): string
	{
		return (string) $this;
	}

	/**
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

		$this->notFoundView = $view;

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
		$fileExists = file_exists($file);
		if (!$fileExists) {
			throw new ViewNotFoundException(sprintf("View `%s` is not found", $view));
		}

		if (!file_exists($this->cacheDirectory)) {
			mkdir($this->cacheDirectory, 0744, true);
		}

		$this->generateCacheFilename($file);
		if ($this->useCached && file_exists($this->cacheFilename)) {
			return;
		}

		$this->fileContents = $this->includeFiles($file);
		$this->compileBlocks()
			->compileYields()
			->compileEscapedEchos()
			->compileUnescapedEchos()
			->compilePHPs()
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
	 * Recursively resolve the included files and it's code blocks
	 *
	 * @param string $file
	 * @return array|string|string[]|null
	 */
	private function includeFiles(string $file): array|string|null
	{
		$file = $this->getViewFile($file);
		$contents = file_get_contents($file);

		// Find and replace [# @extends #] and [# @includes #] directives
		preg_match_all('/\[#\s*(@extends|@includes) ?(.*?)\s*#]/i', $contents, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			// Recursively include files
			$contents = str_replace($value[0], $this->includeFiles($value[2]), $contents);
		}

		// Remove includes|extends directives then return the contents
		return preg_replace('/\[#\s*(@extends|@includes) ?(.*?)\s*#]/i', '', $contents);
	}

	/**
	 * Get the full path of given view file
	 *
	 * @param string $view
	 * @return string
	 */
	private function getViewFile(string $view): string
	{
		$file = str_contains($view, $this->viewsPath) ? $view : $this->viewsPath . DIRECTORY_SEPARATOR . $view;
		$file = str_replace(' ', '', $file);

		return !str_contains($file, '.php') ? $file . '.php' : $file;
	}

	/**
	 * Process [# @block #] and [# @endblock #] directives
	 *
	 * @return $this
	 */
	private function compileBlocks(): self
	{
		preg_match_all('/\[#\s*@block ?(.*?) ?#](.*?)\[# ?@endblock\s*#]/is', $this->fileContents, $matches, PREG_SET_ORDER);

		foreach ($matches as $value) {
			// Create or extend template codeBlocks
			if (!array_key_exists($value[1], $this->codeBlocks)) $this->codeBlocks[$value[1]] = '';

			if (!str_contains($value[2], '@parent')) {
				$this->codeBlocks[$value[1]] = $value[2];
			} else {
				$this->codeBlocks[$value[1]] = str_replace('@parent', $this->codeBlocks[$value[1]], $value[2]);
			}

			// Remove block directives
			$this->fileContents = str_replace($value[0], '', $this->fileContents);
		}

		return $this;
	}

	/**
	 * Replace [# @yield #] directives with block content
	 *
	 * @return $this
	 */
	private function compileYields(): self
	{
		foreach ($this->codeBlocks as $block => $value) {
			$this->fileContents = preg_replace('/\[#\s*@yield ?' . $block . '\s*#]/i', $value, $this->fileContents);
		}

		// Remove unmatched yield directives
		$this->fileContents = preg_replace('/\[#\s*@yield ?(.*?)\s*#]/i', '', $this->fileContents);

		return $this;
	}

	/**
	 * Convert [!! ... !!] expressions into PHP echo statements
	 *
	 * @return $this
	 */
	private function compileUnescapedEchos(): self
	{
		$this->fileContents = preg_replace(
			'~\[!!\s*(.+?)\s*!!]~is',
			'<?php echo $1 ?>',
			$this->fileContents
		);

		return $this;
	}

	/**
	 * Convert [[ ... ]] expressions into PHP echo htmlentities statements
	 *
	 * @return $this
	 */
	private function compileEscapedEchos(): self
	{
		$this->fileContents = preg_replace(
			'~\[\[\s*(.+?)\s*]]~is',
			'<?php echo is_null($1) ? $1 : htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>',
			$this->fileContents
		);

		return $this;
	}

	/**
	 * Convert [@php] ... [@endphp] codeBlocks into PHP code
	 * Convert [...] codeBlocks into PHP code
	 *
	 * @return $this
	 */
	private function compilePHPs(): self
	{
		$this->fileContents = preg_replace(
			'/\[\s*@php\s*](.*?)\[\s*@endphp\s*]/is',
			'<?php $1 ?>',
			$this->fileContents
		);

		$this->fileContents = preg_replace(
			'/\[\s*@(.*?)\s*]/is',
			'<?php $1 ?>',
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
		return (string) ob_get_clean();
	}
}
