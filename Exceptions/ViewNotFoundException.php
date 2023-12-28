<?php

declare(strict_types=1);

namespace Inspira\View\Exceptions;

use Exception;
use Inspira\Contracts\ExceptionWithSuggestions;

class ViewNotFoundException extends Exception implements ExceptionWithSuggestions
{
	public function getSuggestions(): array
	{
		return [
			'Check if you have correctly typed the view name in your controller',
			'Check if the view file exists on your resources directory'
		];
	}
}
