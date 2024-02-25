<?php

declare(strict_types=1);

namespace Inspira\View\Traits;

use Inspira\Container\Container;
use Inspira\View\ComponentInterface;
use Inspira\View\Exceptions\ComponentAlreadyRegisteredException;
use Inspira\View\Exceptions\ComponentNotFoundException;
use Inspira\View\View;
use InvalidArgumentException;
use function Inspira\Utils\closest_match;

/**
 * @property-read Container $container
 * @method View make(string $view, array $data = [])
 */
trait WithComponents
{
	private array $components = [];

	private string $componentsDirectory = 'components';

	protected string $componentPrefix = 'app';

	public function component(ComponentInterface|string $component, array $data = []): View
	{
		if ($component instanceof ComponentInterface) {
			return $component->setComponentProps($data)->render();
		}

		if (class_exists($component) && ($componentInstance = $this->container->make($component)) && $componentInstance instanceof ComponentInterface) {
			return $componentInstance->setComponentProps($data)->render();
		}

		$view = trim($this->componentsDirectory . DIRECTORY_SEPARATOR . $component, DIRECTORY_SEPARATOR);

		return $this->make($view, $data);
	}

	public function setComponentPrefix(string $prefix): static
	{
		$this->componentPrefix = $prefix;

		return $this;
	}

	public function registerComponent(string $key, string $component): self
	{
		if (empty($key)) {
			throw new InvalidArgumentException("Key can't be empty.");
		}

		if (isset($this->components[$key])) {
			throw new ComponentAlreadyRegisteredException("`$key` component is already registered.");
		}

		$this->components[$key] = $component;

		return $this;
	}

	public function getComponentClass(string $key): string
	{
		if (isset($this->components[$key])) {
			return $this->components[$key];
		}

		$suggestions = [];

		if ($closest = closest_match($key, array_keys($this->components))) {
			$suggestions[] = "Did you register this component or do you mean `$closest`?";
		}

		throw new ComponentNotFoundException("Component `$key` is not found.", suggestions: $suggestions);
	}
}
