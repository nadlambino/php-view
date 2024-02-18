<?php

declare(strict_types=1);

use Inspira\View\View;

if (!function_exists('view')) {
	/**
	 * @throws
	 */
	function view($view, array $data = []): View
	{
		return View::getInstance()?->make($view, $data);
	}
}
