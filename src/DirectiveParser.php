<?php

declare(strict_types=1);

namespace Inspira\View;

class DirectiveParser implements ParserInterface
{
	public function __construct(private string $html, private View $view)
	{
	}

	public function parse(): string
	{
		$this->parseBlockDirectives()
			->parseSingleLineDirectives();

		return $this->html;
	}

	private function parseSingleLineDirectives(): self
	{
		$pattern = '/(^|\s+)@(.*?)\((.*?)\)($|\s+)/';
		[$matched, $directive, $expression] = $this->extractSingleLineDirective($pattern);

		if (!$this->view->hasDirective($directive)) {
			return $this;
		}

		$callback = $this->view->getDirectiveCallback($directive);

		$this->html = str_replace($matched, $callback($expression, null) . PHP_EOL, $this->html);

		if (preg_match($pattern, $this->html)) {
			$this->parseSingleLineDirectives();
		}

		return $this;
	}

	private function extractSingleLineDirective(string $pattern): array
	{
		preg_match($pattern, $this->html, $matches);

		if (empty($matches)) {
			return ['', '', ''];
		}

		[$matched, $space, $directive, $expression] = $matches;
		unset($space);

		return [$matched, $directive, $expression];
	}

	private function parseBlockDirectives(): self
	{
		$pattern = '/(^|\s+)@(.*?)\s*\(\s*(.*?)\s*\)(.*?)@end(\2)/s';
		[$matched, $directive, $expression, $body] = $this->extractBlockDirective($pattern);

		if (!$this->view->hasDirective($directive)) {
			return $this;
		}

		$callback = $this->view->getDirectiveCallback($directive);

		$this->html = str_replace($matched, $callback($expression, $body) . PHP_EOL, $this->html);

		if (preg_match($pattern, $this->html)) {
			$this->parseBlockDirectives();
		}

		return $this;
	}

	private function extractBlockDirective(string $pattern): array
	{
		preg_match($pattern, $this->html, $matches);

		if (empty($matches)) {
			return ['', '', ''];
		}

		[$matched, $space, $directive, $expression, $body] = $matches;
		unset($space);

		return [$matched, $directive, $expression, $body];
	}
}
