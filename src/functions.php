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

if (!function_exists('component')) {
	function component(ComponentInterface|string $component, array $data = []): View
	{
		return View::getInstance()?->component($component, $data);
	}
}
