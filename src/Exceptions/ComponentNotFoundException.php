<?php

declare(strict_types=1);

namespace Inspira\View\Exceptions;

use Inspira\Contracts\ExceptionWithSuggestions;
use RuntimeException;
use Throwable;

class ComponentNotFoundException extends RuntimeException implements ExceptionWithSuggestions
{
	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, protected array $suggestions = [])
	{
		parent::__construct($message, $code, $previous);
	}

	public function getSuggestions(): array
	{
		return $this->suggestions;
	}
}
