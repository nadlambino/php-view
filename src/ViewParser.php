<?php

declare(strict_types=1);

namespace Inspira\View;

class ViewParser implements ParserInterface
{
	/**
	 * An array of block contents where key is the block name and value is the content
	 *
	 * @var array $codeBlocks
	 */
	private array $codeBlocks = [];

	public function __construct(protected string $html)
	{
	}

	public function parse(): string
	{
		$this->parseBlocks()
			->parseYields()
			->parseBlockDirectives()
			->parseSingleLineDirectives()
			->parseEscapedEchos()
			->parseUnescapedEchos();

		return $this->html;
	}

	private function parseSingleLineDirectives(): self
	{
		$pattern = '/(^|\s+)@(.*?)\((.*?)\)($|\s+)/';
		[$matched, $directive, $expression] = $this->extractSingleLineDirective($pattern);

		if (!Directive::getInstance()->has($directive)) {
			return $this;
		}

		$callback = Directive::getInstance()->get($directive);

		$this->html = str_replace($matched, $callback($expression) . PHP_EOL, $this->html);

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

		if (!Directive::getInstance()->has($directive)) {
			return $this;
		}

		$callback = Directive::getInstance()->get($directive);

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

	private function parseBlocks(): self
	{
		preg_match_all('/<!--\s*block\s*(.*?)\s*-->(.*?)<!--\s*endblock\s*-->/is', $this->html, $matches, PREG_SET_ORDER);

		foreach ($matches as $value) {
			$marker = $value[0] ?? '';
			$blockName = $value[1] ?? '';
			$content = $value[2] ?? '';
			$this->codeBlocks[$blockName] = $content;

			// Remove block marker
			$this->html = str_replace($marker, '', $this->html);
		}

		return $this;
	}

	/**
	 * Replace <!-- yield --> markers with block's contents
	 *
	 * @return $this
	 */
	private function parseYields(): self
	{
		foreach ($this->codeBlocks as $block => $value) {
			$this->html = preg_replace('/<!--\s*yield\s*' . $block . '\s*-->/i', $value, $this->html);
		}

		// Remove unmatched yield markers
		$this->html = preg_replace('/<!--\s*yield\s*(.*?)\s*-->/i', '', $this->html);

		return $this;
	}

	/**
	 * Convert {{ ... }} expressions into PHP echo htmlentities statements
	 *
	 * @return $this
	 */
	private function parseEscapedEchos(): self
	{
		$this->html = preg_replace(
			'~\{{\s*(.+?)\s*}}~is',
			'<?php echo is_null($1) ? $1 : htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>',
			$this->html
		);

		return $this;
	}

	/**
	 * Convert {!! ... !!} expressions into PHP echo statements
	 *
	 * @return $this
	 */
	private function parseUnescapedEchos(): self
	{
		$this->html = preg_replace(
			'~\{!!\s*(.+?)\s*!!}~is',
			'<?php echo $1 ?>',
			$this->html
		);

		return $this;
	}
}
