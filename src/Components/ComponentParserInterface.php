<?php

declare(strict_types=1);

namespace Inspira\View\Components;

interface ComponentParserInterface
{
	public function parse(): string;
}
