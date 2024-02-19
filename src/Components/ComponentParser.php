<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Inspira\View\View;

class ComponentParser implements ComponentParserInterface
{
	protected DOMXPath $documentXPath;

	public function __construct(
		protected View $view,
		protected string $html,
		protected string $prefix = 'app',
		protected ?DOMDocument $document = null
	) {
		$this->document ??= new DOMDocument();
		$this->loadDocument();
		$this->documentXPath = new DOMXPath($this->document);
	}

	protected function loadDocument(): void
	{
		libxml_use_internal_errors(true);
		$this->document->loadHTML($this->html);
		libxml_use_internal_errors(false);
	}

	public function parse(): string
	{
		$parsedHtml = $this->html;
		$componentTags = $this->getComponentTags();

		foreach ($componentTags as $element) {
			$component = $this->extractComponentName($element);
			$attributes = $this->extractAttributes($element);
			$componentHtml = $this->renderComponent($component, $attributes);
			$parsedHtml = $this->replaceComponentTag($element, $componentHtml, $parsedHtml);
		}

		return $parsedHtml;
	}

	protected function getComponentTags(): DOMNodeList
	{
		return $this->documentXPath->query("//*[starts-with(name(), '$this->prefix-')]");
	}

	protected function extractComponentName(DOMElement $element): string
	{
		return str_replace("$this->prefix-", '', $element->tagName);
	}

	protected function extractAttributes(DOMElement $element): array
	{
		$attributes = [];

		if ($element->hasAttributes()) {
			foreach ($element->attributes as $attribute) {
				$attributes[$attribute->name] = $attribute->value;
			}
		}

		return $attributes;
	}

	protected function renderComponent(string $component, array $attributes): string
	{
		$componentClass = $this->view->getComponentClass($component);
		$view = $this->view->make($componentClass, $attributes);

		return $view->render();
	}

	protected function replaceComponentTag(DOMElement $element, string $replacement, string $html): string
	{
		return preg_replace(
			'/<'. $element->tagName .'(.*?)>(.*?)<\/'. $element->tagName .'>/s',
			$replacement,
			$html,
			1
		);
	}
}
