<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\Container\Container;
use Inspira\Contracts\Renderable;
use Inspira\View\Exceptions\ComponentNotFoundException;
use Inspira\View\Exceptions\ExtendedViewLayoutNotFoundException;
use Inspira\View\Exceptions\ViewNotFoundException;

/**
 * @author Ronald Lambino
 */
class View implements Renderable
{
	private static View $instance;

	private string $cacheDirectory;

	private string $contents = '';

	private array $components = [];

	private string $componentPrefix = 'app';

	private ?string $componentNamespace = null;

	private string $componentViewsPath = 'components';

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
			$contents = (new ComponentParser($this->container, $this, $contents, $this->componentPrefix))->parse();
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
			$contents = (new ComponentParser($this->container, $this, $contents, $this->componentPrefix))->parse();
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

	private function includeFiles(string $file, bool $asHtml = false): string
	{
		$contents = $asHtml ? $file : $this->getFileContents($file);

		preg_match_all('/<!--\s*(extend|include)\s+([a-zA-Z\d_\/-]+)\s*-->/i', $contents, $matches, PREG_SET_ORDER);

		foreach ($matches as $value) {
			$includedFileContents = $this->includeFiles(end($value));

			if (empty($includedFileContents)) {
				continue;
			}

			$contents = str_replace($value[0], $includedFileContents, $contents);
		}

		return $contents ?: '';
	}

	private function getFileContents(string $fileOrContents): string
	{
		$file = $this->getFullFilePath($fileOrContents);

		if (!file_exists($file)) {
			throw new ExtendedViewLayoutNotFoundException("Extended view `$file` is not found");
		}

		return file_get_contents($file);
	}

	public function setComponentViewsPath(string $path): static
	{
		$this->componentViewsPath = $path;

		return $this;
	}

	public function setComponentPrefix(string $prefix): static
	{
		$this->componentPrefix = $prefix;

		return $this;
	}

	public function autoloadComponentsFrom(string $namespace): self
	{
		$this->componentNamespace = $namespace;

		return $this;
	}

	public function registerComponent(string $key, string $component): self
	{
		$this->components[$key] = $component;

		return $this;
	}

	public function getComponentClass(string $key): string
	{
		if (isset($this->components[$key])) {
			return $this->components[$key];
		}

		$suggestions = [];

		if ($this->componentNamespace) {
			$class = kebab_to_pascal($key);
			$component = $this->componentNamespace . '\\' . $class;

			if (class_exists($component)) {
				return $component;
			}

			$suggestions[] = "You are auto-loading your components from `$this->componentNamespace` namespace. Make sure the component is under this namespace or the component name is correct.";
		}

		$message = "Component `$key` is not found.";
		$closest = closest_match($key, array_keys($this->components));

		if (!empty($closest)) {
			array_unshift($suggestions, "Did you register this component or do you mean `$closest`?");
		}

		throw new ComponentNotFoundException($message, suggestions: $suggestions);
	}

	private function save(string $filename, string $contents): void
	{
		file_put_contents(
			$filename,
			'<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $contents
		);
	}

	private static function requireView(string $path, array $data = []): string
	{
		extract($data);
		ob_start();
		require $path;

		return (string)ob_get_clean();
	}
}
