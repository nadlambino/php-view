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
		$this->safeLoadDocument($this->html);
		$this->documentXPath = new DOMXPath($this->document);
	}

	protected function safeLoadDocument(string $html, bool $preserveWhiteSpace = false, bool $asXml = false)
	{
		libxml_use_internal_errors(true);
		$this->document->preserveWhiteSpace = $preserveWhiteSpace;
		$asXml ? $this->document->loadXML($html) : $this->document->loadHTML($html);
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

		return $children
			? $this->appendComponentChildren($html, $children)
			: $html;
	}

	protected function setWrapperElementAttributes(string $html, array $attributes): string
	{
		$this->safeLoadDocument($html, asXml: true);

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
		$this->safeLoadDocument($html, asXml: true);

		$element = $this->document->getElementsByTagName('*')->item(0);

		if (is_null($element)) {
			return $html;
		}

		foreach ($children as $child) {
			$element->appendChild($this->document->importNode($child, true));
		}

		return $this->document->saveHTML();
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
