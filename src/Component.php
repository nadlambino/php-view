<?php

declare(strict_types=1);

namespace Inspira\View;

use ReflectionClass;
use ReflectionProperty;

abstract class Component implements ComponentInterface
{
	protected ?string $directory = 'components';

	protected ?string $view;

	protected ?string $cacheFilename;

	public function html(): ?string
	{
		return null;
	}

	public function render(array $data = []): View
	{
		$properties = $this->getProperties() + $data;

		if ($html = $this->html()) {
			return View::getInstance()
				->cacheFilename($this->cacheFilename ?? static::class)
				->html($html, $properties);
		}

		$this->view ??= camel_to_kebab(class_basename(static::class));
		$view = $this->directory . DIRECTORY_SEPARATOR . $this->view;

		return View::getInstance()->make(trim($view, DIRECTORY_SEPARATOR), $properties);
	}

	protected function getProperties() : array
	{
		$class = new ReflectionClass($this);
		$properties = [];

		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$properties[$property->getName()] = $this->{$property->getName()};
		}

		return $properties;
	}
}
