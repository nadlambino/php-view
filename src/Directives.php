<?php

declare(strict_types=1);

namespace Inspira\View;

use InvalidArgumentException;

class Directives
{
	public function __construct(private View $view)
	{
	}

	public function register(): void
	{
		$this->registerIf()
			->registerElseIf()
			->registerElse()
			->registerSwitch()
			->registerCase()
			->registerDefault()
			->registerForeach()
			->registerFor()
			->registerWhile()
			->registerDoWhile()
			->registerBreak()
			->registerPhp()
			->registerScript();
	}

	private function registerIf(): self
	{
		$this->view->registerDirective('if', function ($expression, $body) {
			return "<?php if ($expression): ?>$body<?php endif; ?>";
		});

		return $this;
	}

	private function registerElseIf(): self
	{
		$this->view->registerDirective('elseif', function ($expression, $body) {
			return "<?php elseif ($expression): ?>$body";
		});

		return $this;
	}

	private function registerElse(): self
	{
		$this->view->registerDirective('else', function ($expression, $body) {
			return "<?php else: ?>$body";
		});

		return $this;
	}

	private function registerSwitch(): self
	{
		$this->view->registerDirective('switch', function ($expression, $body) {
			return "<?php switch ($expression): ?>$body<?php endswitch; ?>";
		});

		return $this;
	}

	private function registerCase(): self
	{
		$this->view->registerDirective('case', function ($expression, $body) {
			return "<?php case $expression: ?>$body";
		});

		return $this;
	}

	private function registerDefault(): self
	{
		$this->view->registerDirective('default', function ($expression, $body) {
			return "<?php default: ?>$body";
		});

		return $this;
	}

	private function registerForeach(): self
	{
		$this->view->registerDirective('foreach', function ($expression, $body) {
			return "<?php foreach ($expression): ?>$body<?php endforeach; ?>";
		});

		return $this;
	}

	private function registerFor(): self
	{
		$this->view->registerDirective('for', function ($expression, $body) {
			return "<?php for ($expression): ?>$body<?php endfor; ?>";
		});

		return $this;
	}

	private function registerWhile(): self
	{
		$this->view->registerDirective('while', function ($expression, $body) {
			return "<?php while ($expression): ?>$body<?php endwhile; ?>";
		});

		return $this;
	}

	private function registerDoWhile(): self
	{
		$this->view->registerDirective('do-while', function ($expression, $body) {
			return "<?php do: ?>$body<?php while ($expression); ?>";
		});

		return $this;
	}

	private function registerBreak(): self
	{
		$this->view->registerDirective('break', function () {
			return "<?php break; ?>";
		});

		return $this;
	}

	private function registerPhp(): self
	{
		$this->view->registerDirective('php', function ($expression, $body) {
			return "<?php $expression ?>" . PHP_EOL . "<?php $body ?>";
		});

		return $this;
	}

	private function registerScript(): self
	{
		$this->view->registerDirective('script', function ($expression) {
			if (empty($expression)) {
				throw new InvalidArgumentException('Missing script source');
			}

			if (preg_match('/^\'(.*?)\'$/', $expression, $matches)) {
				$expression = $matches[1];
			} else if (preg_match('/^"(.*?)"$/', $expression, $matches)) {
				$expression = $matches[1];
			}

			return "<script src='$expression'></script>";
		});

		return $this;
	}
}
