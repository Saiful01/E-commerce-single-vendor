<?php

namespace App\Support;

/**
 * Cleans the HTML typed into the page editor, so a page can never carry a script.
 * Only simple text tags survive, every attribute is removed (links keep a checked href).
 */
class HtmlSanitizer
{
    private const ALLOWED = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'del', 's', 'ul', 'ol', 'li',
                             'h2', 'h3', 'h4', 'blockquote', 'pre', 'code', 'a', 'hr'];

    /** Removed together with everything inside them. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea',
                          'select', 'link', 'meta', 'svg', 'math', 'noscript', 'template', 'base', 'img', 'video', 'audio'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::walk($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(\DOMNode $parent): void
    {
        $unwrapped = false;

        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMComment) {
                $parent->removeChild($node);
                continue;
            }
            if (! $node instanceof \DOMElement) {
                continue;   // plain text is safe
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROP, true)) {
                $parent->removeChild($node);
                continue;
            }

            if ($tag === 'h1') {                       // the page title is the only h1 on the page
                $h2 = $node->ownerDocument->createElement('h2');
                while ($node->firstChild) {
                    $h2->appendChild($node->firstChild);
                }
                $parent->replaceChild($h2, $node);
                $node = $h2;
                $tag  = 'h2';
            }

            if (! in_array($tag, self::ALLOWED, true)) {
                // unknown tag: keep its text, lose the tag
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                $unwrapped = true;
                continue;
            }

            self::cleanAttributes($node, $tag);
            self::walk($node);
        }

        if ($unwrapped) {
            self::walk($parent);
        }
    }

    private static function cleanAttributes(\DOMElement $node, string $tag): void
    {
        $href = $tag === 'a' ? trim((string) $node->getAttribute('href')) : '';

        foreach (iterator_to_array($node->attributes) as $attr) {
            $node->removeAttributeNode($attr);
        }

        if ($tag !== 'a') {
            return;
        }

        $compact = preg_replace('/[\x00-\x20]+/', '', $href);
        if ($compact !== '' && preg_match('~^(https?:|mailto:|tel:|/|#)~i', $compact)) {
            $node->setAttribute('href', $compact);

            if (preg_match('~^https?:~i', $compact)) {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }
}
