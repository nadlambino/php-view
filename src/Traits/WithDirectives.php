<?php

namespace Inspira\View\Traits;

use Closure;
use Inspira\View\View;

trait WithDirectives
{
	private array $directives = [];

	public function registerDirective(string $directive, Closure $callback): View
	{
		$this->directives[$directive] = [
			'callback' => $callback
		];

		return $this;
	}

	public function getDirectiveCallback(string $directive): Closure
	{
		return $this->directives[$directive]['callback'];
	}

	public function hasDirective(string $directive): bool
	{
		return isset($this->directives[$directive]);
	}
}
