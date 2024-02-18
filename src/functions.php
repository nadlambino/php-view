<?php

declare(strict_types=1);

use Inspira\View\ComponentInterface;
use Inspira\View\View;

if (!function_exists('view')) {
	function view(ComponentInterface|string $view, array $data = []): View
	{
		return View::getInstance()?->make($view, $data);
	}
}
