<?php

declare(strict_types=1);

namespace Inspira\View;

use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Inspira\Container\Container;
use IvoPetkov\HTML5DOMDocument;

class ComponentParser implements ParserInterface
{
	protected HTML5DOMDocument $document;

	protected const SLOT_TAG = 'slot';

	protected const TEMPLATE_TAG = 'template';

	public function __construct(
		protected Container    $container,
		protected View         $view,
		protected string       $html,
		protected string       $prefix = 'app',
	)
	{
		$this->document = new HTML5DOMDocument();
		$this->safeLoadDocument($this->document, $this->html);
	}

	protected function safeLoadDocument(HTML5DOMDocument $document, string $html)
	{
		libxml_use_internal_errors(true);
		$document->preserveWhiteSpace = false;
		$document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_use_internal_errors(false);
	}

	public function parse(): string
	{
		$html = $this->removeCommentedComponents($this->html);
		$components = $this->getComponents();
		$length = $components->length;

		for ($i = 0; $i < $length; $i++) {
			$component = $components->item($i);

			$children = $component->hasChildNodes() ? $component->childNodes : null;
			$componentName = $this->extractComponentName($component);
			$attributes = $this->extractAttributes($component);
			$contents = $this->renderComponent($componentName, $attributes, $children);

			$document = new HTML5DOMDocument();
			$this->safeLoadDocument($document, $contents);

			foreach($document->childNodes as $node) {
				$component->parentNode->insertBefore($this->document->importNode($node, true), $component);
			}

			$component->parentNode->removeChild($component);

			$html = $this->document->saveHTML();
		}

		return $this->decodeUrls($html);
	}

	private function decodeUrls(string $html): string
	{
		$pattern = '/<(.*?)(href|src|link|action|background|cite|data|formaction|icon|longdesc|manifest|poster|srcset)\s*=\s*"([^"]+)"(.*?\s*)>/is';

		return preg_replace_callback($pattern, function($matches) {
			$opening = $matches[1];
			$attribute = $matches[2];
			$url = html_entity_decode(urldecode($matches[3]));
			$closing = $matches[4];

			return '<' . $opening . $attribute . '="' . $url . '"' . $closing . '>';
		}, $html);
	}

	protected function removeCommentedComponents(string $html): string
	{
		$pattern = '/<!--\s*<' . $this->prefix . '-(.*?)\s*-->/s';

		return preg_replace($pattern, '<!-- this comment means that this component was commented -->', $html);
	}

	protected function getComponents(): DOMNodeList
	{
		$documentXPath = new DOMXPath($this->document);

		return $documentXPath->query("//*[starts-with(name(), '$this->prefix-')]");
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
		$class = $this->view->getComponentClass($component);

		/** @var ComponentInterface $component */
		$component = $this->container->make($class);

		$view = $component->setComponentProps($attributes)->render();
		$visibleAttributes = $this->getComponentVisibleAttributes($component, $attributes);
		$html = $this->setWrapperElementAttributes($view->render(), $visibleAttributes);

		return $children
			? $this->appendComponentChildren($html, $children)
			: $html;
	}

	protected function getComponentVisibleAttributes(ComponentInterface $component, array $attributes): array
	{
		$visibleAttributes = [];

		foreach ($attributes as $name => $value) {
			if ($component->isHiddenProp($name) || $component->shouldPropBeHidden($name, $value)) {
				continue;
			}

			$visibleAttributes[$name] = $value;
		}

		return $visibleAttributes;
	}

	protected function setWrapperElementAttributes(string $html, array $attributes): string
	{
		$document = new HTML5DOMDocument();
		$this->safeLoadDocument($document, $html);

		$wrapperElement = $document->getElementsByTagName('*')->item(0);
		$wrapperElement = $this->createFragmentIfTemplated($wrapperElement, $document);

		$element = match (true) {
			$wrapperElement instanceof DOMDocumentFragment
			&& !$wrapperElement->nextSibling
			&& !$wrapperElement?->firstChild?->nextSibling => $wrapperElement->firstChild,

			$wrapperElement instanceof DOMElement
			&& !$wrapperElement->nextSibling => $wrapperElement,

			default => null
		};

		if ($element === null) {
			return $document->saveHTML($wrapperElement);
		}

		foreach ($attributes as $key => $value) {
			$element->setAttribute($key, $value);
		}

		return $document->saveHTML($element);
	}

	protected function appendComponentChildren(string $html, DOMNodeList $children): string
	{
		$document = new HTML5DOMDocument();
		$this->safeLoadDocument($document, $html);

		$element = $document->getElementsByTagName('*')->item(0);

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
				$child = $this->createFragmentIfTemplated($child, $document);
				$this->replaceSlotWithComponentContents($slots, $child, $templateSlotName, $document);
			} else {
				$element->appendChild($document->importNode($child, true));
			}
		}

		$this->commentUnusedSlots($slots, $document);

		return $document->saveHTML($element);
	}

	protected function createFragmentIfTemplated(DOMNode $template, HTML5DOMDocument $document): DOMNode
	{
		if ($template->nodeName === self::TEMPLATE_TAG) {
			$fragment = $document->createDocumentFragment();
			$childNodes = $template->childNodes;
			$childNodesLength = $childNodes->length;

			for ($childNodeIndex = 0; $childNodeIndex < $childNodesLength; $childNodeIndex++) {
				$node = $childNodes->item($childNodeIndex);

				if (is_null($node) || (!($node instanceof DOMElement) && empty(trim($node->nodeValue)))) {
					continue;
				}

				$imported = $document->importNode($node, true);
				$fragment->appendChild($imported);
			}

			return $fragment;
		}

		return $template;
	}

	protected function replaceSlotWithComponentContents(DOMNodeList $slots, DOMNode $child, ?string $templateSlotName, HTML5DOMDocument $document): void
	{
		$childSlotName = $child->hasAttributes()
			? $child->getAttribute(self::SLOT_TAG)
			: $templateSlotName;

		$length = $slots->length;

		for ($i = 0; $i < $length; $i++) {
			$slot = $slots->item($i);
			$slotName = $slot->getAttribute('name');
			if ((empty($slotName) && empty($childSlotName)) || $slotName === $childSlotName) {
				if ($child->hasAttributes()) {
					$child->removeAttribute(self::SLOT_TAG);
				}

				$slot->parentNode->replaceChild($document->importNode($child, true), $slot);

				break;
			}
		}
	}

	/**
	 * Comment out unused slots. Use for loop instead of foreach to avoid modifying the DOMNodeList object while on the loop.
	 *
	 * @param DOMNodeList $slots
	 * @param HTML5DOMDocument $document
	 * @return void
	 */
	protected function commentUnusedSlots(DOMNodeList $slots, HTML5DOMDocument $document): void
	{
		for ($i = 0; $i < $slots->length; $i++) {
			$slot = $slots->item($i);
			$comment = $document->createComment($document->saveHTML($slot));
			$slot->parentNode->replaceChild($comment, $slot);
		}
	}
}
