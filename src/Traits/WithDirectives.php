<?php

namespace Inspira\View\Traits;

use Closure;
use Inspira\View\Exceptions\DirectiveAlreadyRegisteredException;
use Inspira\View\View;
use InvalidArgumentException;

trait WithDirectives
{
	private array $directives = [];

	public function registerDirective(string $directive, Closure $callback): View
	{
		$directive = trim($directive);

		if (empty($directive)) {
			throw new InvalidArgumentException("Directive can't be empty.");
		}

		if ($this->hasDirective($directive)) {
			throw new DirectiveAlreadyRegisteredException("Directive `$directive` is already registered.");
		}

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
