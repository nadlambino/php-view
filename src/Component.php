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

	public function setComponentPrefix(string $prefix): static
	{
		$this->prefix = $prefix;

		return $this;
	}

	public function registerComponent(string $key, string $component): self
	{
		$this->components[$key] = $component;

		return $this;
	}

	public function getComponentClass(string $key): string
	{
		if (!isset($this->components[$key])) {
			throw new ComponentNotFoundException("Component `$key` is not found. Did you register this component?");
		}

		return $this->components[$key];
	}

	protected function compileComponents(): self
	{
		$this->fileContents = (new ComponentParser($this, $this->fileContents, $this->prefix))->parse();

		return $this;
	}
}
