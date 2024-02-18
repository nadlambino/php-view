<?php

declare(strict_types=1);

namespace Inspira\View;

interface ComponentInterface
{
	public function render(array $data = []): View;
}
