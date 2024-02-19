<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use Inspira\View\View;
use ReflectionClass;
use ReflectionProperty;

abstract class Component implements ComponentInterface
{
	protected ?string $view;

	protected ?string $cacheFilename;

	private array $data = [];

	public function html(): ?string
	{
		return null;
	}

	public function render(): View
	{
		$data = [...$this->getProperties(), ...$this->getData()];

		if ($html = $this->html()) {
			return View::getInstance()
				->cacheFilename($this->cacheFilename ?? static::class)
				->html($html, $data);
		}

		$this->view ??= camel_to_kebab(class_basename(static::class));
		$view = View::getInstance()->getComponentViewsDirectory() . DIRECTORY_SEPARATOR . $this->view;

		return View::getInstance()->make(trim($view, DIRECTORY_SEPARATOR), $data);
	}

	protected function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): static
	{
		$this->data = $data;

		return $this;
	}

	protected function getProperties() : array
	{
		$class = new ReflectionClass($this);
		$properties = [];

		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			$properties[$name] = $this->$name;
		}

		return $properties;
	}
}
