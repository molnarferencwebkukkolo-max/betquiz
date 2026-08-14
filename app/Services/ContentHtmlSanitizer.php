<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class ContentHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'h2', 'h3', 'h4', 'strong', 'em', 'u', 's', 'blockquote', 'ul', 'ol', 'li',
        'a', 'hr', 'br', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'pre', 'code', 'img',
    ];

    public function sanitize(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div id="kwizzgo-content-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $document->getElementById('kwizzgo-content-root');

        if (! $root) {
            return '';
        }

        $this->cleanChildren($root);

        return collect(iterator_to_array($root->childNodes))
            ->map(fn (DOMNode $node) => $document->saveHTML($node))
            ->implode('');
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $parent->removeChild($node);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->cleanChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $this->cleanAttributes($node, $tag);
            $this->cleanChildren($node);
        }
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'alt', 'title'],
            'th', 'td' => ['colspan', 'rowspan', 'style'],
            'p', 'h2', 'h3', 'h4' => ['style'],
            default => [],
        };

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($element->hasAttribute('style') && ! preg_match('/^text-align:\s*(left|center|right|justify);?$/i', $element->getAttribute('style'))) {
            $element->removeAttribute('style');
        }

        if ($tag === 'a') {
            $href = trim($element->getAttribute('href'));
            if (! preg_match('#^(https?://|mailto:|/)#i', $href)) {
                $element->removeAttribute('href');
            }
            $element->setAttribute('rel', 'noopener noreferrer');
            if ($element->getAttribute('target') !== '_blank') {
                $element->removeAttribute('target');
            }
        }

        if ($tag === 'img' && ! preg_match('#^(https?://|/storage/content/)#i', trim($element->getAttribute('src')))) {
            $element->parentNode?->removeChild($element);
        }
    }
}
