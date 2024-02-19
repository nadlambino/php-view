<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Inspira\View\View;

class ComponentParser implements ComponentParserInterface
{
	protected DOMXPath $documentXPath;

	public function __construct(
		protected View         $view,
		protected string       $html,
		protected string       $prefix = 'app',
		protected ?DOMDocument $document = null
	)
	{
		$this->document ??= new DOMDocument();
		$this->safeLoadDocument(fn() => $this->document->loadHTML($this->html));
		$this->documentXPath = new DOMXPath($this->document);
	}

	protected function safeLoadDocument(Closure $closure)
	{
		libxml_use_internal_errors(true);
		$closure();
		libxml_use_internal_errors(false);
	}

	public function parse(): string
	{
		$parsedHtml = $this->html;
		$componentTags = $this->getComponentTags();

		foreach ($componentTags as $element) {
			$children = $element->hasChildNodes() ? $element->childNodes : null;
			$component = $this->extractComponentName($element);
			$attributes = $this->extractAttributes($element);
			$componentHtml = $this->renderComponent($component, $attributes, $children);
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

	protected function renderComponent(string $component, array $attributes, ?DOMNodeList $children): string
	{
		$componentClass = $this->view->getComponentClass($component);
		$view = $this->view->make($componentClass, $attributes);
		$html = $this->setWrapperElementAttributes($view->render(), $attributes);

		if ($children) {
			return $this->appendComponentChildren($html, $children);
		}

		return $html;
	}

	protected function setWrapperElementAttributes(string $html, array $attributes): string
	{
		$this->safeLoadDocument(fn() => $this->document->loadXML($html));

		$wrapperElement = $this->document->getElementsByTagName('*')->item(0);

		if ($wrapperElement instanceof DOMElement && !$wrapperElement->nextSibling && $wrapperElement->tagName !== 'html') {
			foreach ($attributes as $key => $value) {
				$wrapperElement->setAttribute($key, $value);
			}

			$html = $this->document->saveHTML();
		}

		return $html ?: '';
	}

	protected function appendComponentChildren(string $html, DOMNodeList $children): string
	{
		/** @var DOMDocument $document */
		$document = new $this->document();
		$this->safeLoadDocument(fn() => $document->loadXML($html));

		$element = $document->getElementsByTagName('*')->item(0);

		if (is_null($element)) {
			return $html;
		}

		foreach ($children as $child) {
			$element->appendChild($document->importNode($child, true));
		}

		return $document->saveHTML();
	}

	protected function replaceComponentTag(DOMElement $element, string $replacement, string $html): string
	{
		return preg_replace(
			'/<' . $element->tagName . '(.*?)>(.*?)<\/' . $element->tagName . '>/s',
			$replacement,
			$html,
			1
		);
	}
}
