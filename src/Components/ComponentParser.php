<?php

declare(strict_types=1);

namespace Inspira\View\Components;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Inspira\View\View;

class ComponentParser implements ComponentParserInterface
{
	protected DOMXPath $documentXPath;

	protected const SLOT_TAG = 'slot';

	protected const TEMPLATE_TAG = 'template';

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

	protected function safeLoadDocument(string $html, bool $preserveWhiteSpace = false, bool $noDoctype = true)
	{
		libxml_use_internal_errors(true);
		$this->document->preserveWhiteSpace = $preserveWhiteSpace;
		$noDoctype ? $this->document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD) : $this->document->loadHTML($html);
		libxml_use_internal_errors(false);
	}

	public function parse(): string
	{
		$parsedHtml = $this->html;
		$componentTags = $this->getComponentTags();
		$length = $componentTags->length;

		for ($i = 0; $i < $length; $i++) {
			$element = $componentTags->item($i);
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

	protected function extractComponentName(DOMNode $element): string
	{
		return str_replace("$this->prefix-", '', $element->tagName);
	}

	protected function extractAttributes(DOMNode $element): array
	{
		if (!$element->hasAttributes()) {
			return [];
		}

		$attributes = [];

		foreach ($element->attributes as $attribute) {
			$attributes[$attribute->name] = $attribute->value;
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
		$this->safeLoadDocument($html);

		$wrapperElement = $this->document->getElementsByTagName('*')->item(0);
		$wrapperElement = $this->createFragmentIfTemplated($wrapperElement);

		$element = match (true) {
			$wrapperElement instanceof DOMDocumentFragment
			&& !$wrapperElement->nextSibling
			&& !$wrapperElement?->firstChild?->nextSibling
			&& $wrapperElement->nodeName !== 'html' => $wrapperElement->firstChild,

			$wrapperElement instanceof DOMElement
			&& !$wrapperElement->nextSibling
			&& $wrapperElement->nodeName !== 'html' => $wrapperElement,

			default => null
		};

		if ($element === null) {
			return $this->document->saveHTML($wrapperElement);
		}

		foreach ($attributes as $key => $value) {
			$element->setAttribute($key, $value);
		}

		return $this->document->saveHTML($element);
	}

	protected function appendComponentChildren(string $html, DOMNodeList $children): string
	{
		$this->safeLoadDocument($html);

		$element = $this->document->getElementsByTagName('*')->item(0);

		if (is_null($element)) {
			return $html;
		}

		/** @var DOMNodeList $slots */
		$slots = $element->getElementsByTagName(self::SLOT_TAG);
		$hasSlots = $slots->length > 0;
		$childrenLength = $children->length;

		/** @var DOMNode|DOMElement|DOMText $element */
		for ($childIndex = 0; $childIndex < $childrenLength; $childIndex++) {
			$child = $children->item($childIndex);
			if (trim($child->nodeValue) === '') {
				continue;
			}

			if ($hasSlots === true) {
				$templateSlotName = $child->hasAttributes() ? $child->getAttribute(self::SLOT_TAG) : null;
				$child = $this->createFragmentIfTemplated($child);
				$this->replaceSlotWithComponentContents($slots, $child, $templateSlotName);
			} else {
				$element->appendChild($this->document->importNode($child, true));
			}
		}

		$this->commentUnusedSlots($slots);

		return $this->document->saveHTML($element);
	}

	protected function createFragmentIfTemplated(DOMNode $template): DOMNode
	{
		if ($template->nodeName === self::TEMPLATE_TAG) {
			$fragment = $this->document->createDocumentFragment();
			$childNodes = $template->childNodes;
			$childNodesLength = $childNodes->length;

			for ($childNodeIndex = 0; $childNodeIndex < $childNodesLength; $childNodeIndex++) {
				$node = $childNodes->item($childNodeIndex);
				if (is_null($node) || trim($node->nodeValue) === '') {
					continue;
				}

				$imported = $this->document->importNode($node, true);
				$fragment->appendChild($imported);
			}

			return $fragment;
		}

		return $template;
	}

	protected function replaceSlotWithComponentContents(DOMNodeList $slots, DOMNode $child, ?string $templateSlotName): void
	{
		$childSlotName = $child->hasAttributes()
			? $child->getAttribute(self::SLOT_TAG)
			: $templateSlotName;

		$length = $slots->length;

		for ($i = 0; $i < $length; $i++) {
			$slot = $slots->item($i);
			$slotName = $slot->getAttribute('name');
			if ((empty($slotName) && empty($childSlotName)) || $slotName === $childSlotName) {
				$child->removeAttribute(self::SLOT_TAG);
				$slot->parentNode->replaceChild($this->document->importNode($child, true), $slot);

				break;
			}
		}
	}

	/**
	 * Comment out unused slots. Use for loop instead of foreach to avoid modifying the DOMNodeList object while on the loop.
	 *
	 * @param DOMNodeList $slots
	 * @return void
	 */
	protected function commentUnusedSlots(DOMNodeList $slots): void
	{
		for ($i = 0; $i < $slots->length; $i++) {
			$slot = $slots->item($i);
			$comment = $this->document->createComment($this->document->saveHTML($slot));
			$slot->parentNode->replaceChild($comment, $slot);
		}
	}

	protected function replaceComponentTag(DOMNode $element, string $replacement, string $html): string
	{
		return preg_replace(
			'/<' . $element->tagName . '(.*?)>(.*?)<\/' . $element->tagName . '>|<' . $element->tagName . '(.*?)\/>/s',
			$replacement,
			$html,
			1
		);
	}
}
