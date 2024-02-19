<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\View\Components\ComponentParser;
use Inspira\View\Exceptions\ComponentNotFoundException;

/**
 * @property string $fileContents
 */
trait Component
{
	protected array $components = [];

	protected string $prefix = 'app';

	protected ?string $namespace = null;

	public function setComponentPrefix(string $prefix): static
	{
		$this->prefix = $prefix;

		return $this;
	}

	public function autoloadComponentsFrom(string $namespace): self
	{
		$this->namespace = $namespace;

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

		if ($this->namespace) {
			$class = kebab_to_camel($key);
			$namespace = str_replace(['/', '.'], ["\\", ''], $this->namespace);
			$component = $namespace . '\\' . $class;

			if (class_exists($component)) {
				return $component;
			}
		}

		throw new ComponentNotFoundException("Component `$key` is not found. Did you register this component?");
	}

	protected function compileComponents(): self
	{
		$this->fileContents = (new ComponentParser($this, $this->fileContents, $this->prefix))->parse();

		return $this;
	}
}
