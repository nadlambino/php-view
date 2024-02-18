<?php

declare(strict_types=1);

namespace Inspira\View;

use Inspira\View\Exceptions\ViewComponentNotSet;
use ReflectionClass;
use ReflectionProperty;

abstract class Component implements ComponentInterface
{
	protected ?string $view = null;

	protected ?string $cacheFilename;

	public function html(): ?string
	{
		return null;
	}

	public function render(array $data = []): View
	{
		if ($html = $this->html()) {
			return View::getInstance()
				->cacheFilename($this->cacheFilename ?? static::class)
				->html($html);
		}

		if (! $this->view) {
			throw new ViewComponentNotSet("View component " . static::class . " must have a view or html");
		}

		$properties = $this->getData();

		return View::getInstance()->make($this->view, $properties + $data);
	}

	protected function getData() : array
	{
		$class = new ReflectionClass($this);
		$properties = [];

		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$properties[$property->getName()] = $this->{$property->getName()};
		}

		return $properties;
	}
}
