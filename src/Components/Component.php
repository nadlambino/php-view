<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use Inspira\View\View;
use ReflectionClass;
use ReflectionProperty;

abstract class Component implements ComponentInterface
{
	protected ?string $view;

	protected bool $mergeProps = false;

	private array $componentProps = [];

	protected array $hiddenProps = [];

	public function html(): ?string
	{
		return null;
	}

	public function render(): View
	{
		$data = $this->getData();

		if ($html = $this->html()) {
			return View::getInstance()
				->generateCacheFilename(static::class)
				->html($html, $data);
		}

		$this->view ??= camel_to_kebab(class_basename(static::class));

		return component($this->view, $data);
	}

	private function getData(): array
	{
		$componentProps = $this->getComponentProps();
		$classProps = $this->getClassProps();

		if ($this->mergeProps) {
			$props = [];

			foreach ($componentProps as $name => $prop) {
				$props[kebab_to_camel($name)] = $prop;
			}

			return array_merge($classProps, $props);
		}

		$classProps['props'] = $componentProps;

		return $classProps;
	}

	protected function getComponentProps(): array
	{
		return $this->componentProps;
	}

	public function setComponentProps(array $props): static
	{
		$this->componentProps = $props;

		return $this;
	}

	protected function getClassProps() : array
	{
		$class = new ReflectionClass($this);
		$properties = [];

		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			$properties[$name] = $this->$name;
		}

		return $properties;
	}

	public function getHiddenProps(): array
	{
		return $this->hiddenProps;
	}

	public function isHiddenProp(string $name): bool
	{
		return in_array($name, $this->hiddenProps);
	}

	public function shouldPropBeHidden(string $name, mixed $value): bool
	{
		return false;
	}
}
