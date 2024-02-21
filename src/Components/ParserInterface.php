<?php

declare(strict_types=1);

namespace Inspira\View\Components;

interface ParserInterface
{
	public function parse(): string;
}
