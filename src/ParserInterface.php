<?php

declare(strict_types=1);

namespace Inspira\View;

interface ParserInterface
{
	public function parse(): string;
}
