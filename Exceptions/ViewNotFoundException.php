<?php

declare(strict_types=1);

namespace Inspira\View\Exceptions;

use Exception;
use Inspira\Contracts\ExceptionWithSuggestions;
use Inspira\Contracts\RenderableException;
use Inspira\View\View;

class ViewNotFoundException extends Exception implements ExceptionWithSuggestions, RenderableException
{
	public function __construct(string $message = '', int $code = 404)
	{
		parent::__construct($message, $code);
	}

	public function getSuggestions(): array
	{
		return [
			'Check if you have correctly typed the view name in your controller',
			'Check if the view file exists on your views directory'
		];
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function render(): string
	{
		return View::getInstance()->raw(dirname(__DIR__) . '/resources/errors/404')->render();
	}
}
