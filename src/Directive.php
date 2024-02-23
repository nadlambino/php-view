<?php

declare(strict_types=1);

namespace Inspira\View;

use Closure;
use RuntimeException;

class Directive
{
	private array $directives = [];

	private static Directive $instance;

	public function __construct()
	{
		self::$instance = $this;
	}

	public static function getInstance(): Directive
	{
		return self::$instance ?? new self();
	}

	public function register(string $directive, Closure $callback): self
	{
		$this->directives[$directive] = [
			'callback' => $callback
		];

		return $this;
	}

	public function get(string $directive)
	{
		if (!$this->has($directive)) {
			throw new RuntimeException("Unknown `$directive` directive.");
		}

		return $this->directives[$directive]['callback'];
	}

	public function has(string $directive): bool
	{
		return isset($this->directives[$directive]);
	}
}
