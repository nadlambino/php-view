<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use Inspira\View\View;

interface ComponentInterface
{
	public function render(): View;

	public function setComponentProps(array $props): static;

	public function getHiddenProps(): array;

	public function isHiddenProp(string $name): bool;
}
