<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @phpstan-type Diagnostics array{
 *     unresolved_links: list<string>,
 *     missing_media: list<string>,
 *     suspicious_markup: list<string>,
 *     transformations: list<string>
 * }
 */
final class WordPressContentNormalizer
{
    /**
     * @param  array<string, string>  $linkMappings
     * @param  array<string, string>  $mediaMappings
     * @param  list<string>  $internalHosts
     * @return array{
     *     content: string,
     *     unresolved_links: list<string>,
     *     missing_media: list<string>,
     *     suspicious_markup: list<string>,
     *     transformations: list<string>
     * }
     */
    public function normalize(
        string $html,
        string $sourceUrl,
        array $linkMappings,
        array $mediaMappings,
        array $internalHosts,
    ): array {
        $diagnostics = $this->emptyDiagnostics();

        $html = preg_replace_callback(
            '/\[(?:\/?)[a-zA-Z][^\]\r\n]*\]/',
            function (array $match) use (&$diagnostics): string {
                $this->record($diagnostics['transformations'], 'WordPress-shortcode verwijderd: '.$match[0]);

                return '';
            },
            $html,
        ) ?? $html;

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="dds-wordpress-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (! $loaded) {
            $diagnostics['suspicious_markup'][] = 'HTML kon niet door DOMDocument worden gelezen.';

            return [
                'content' => $this->normalizeWhitespace(strip_tags($html)),
                'unresolved_links' => $diagnostics['unresolved_links'],
                'missing_media' => $diagnostics['missing_media'],
                'suspicious_markup' => $diagnostics['suspicious_markup'],
                'transformations' => $diagnostics['transformations'],
            ];
        }

        $root = $document->getElementById('dds-wordpress-root');
        $content = $root instanceof DOMElement
            ? $this->renderChildren($root, $sourceUrl, $linkMappings, $mediaMappings, $internalHosts, $diagnostics)
            : strip_tags($html);

        return [
            'content' => $this->normalizeWhitespace($content),
            'unresolved_links' => $diagnostics['unresolved_links'],
            'missing_media' => $diagnostics['missing_media'],
            'suspicious_markup' => $diagnostics['suspicious_markup'],
            'transformations' => $diagnostics['transformations'],
        ];
    }

    /**
     * @param  array<string, string>  $linkMappings
     * @param  array<string, string>  $mediaMappings
     * @param  list<string>  $internalHosts
     * @param  Diagnostics  $diagnostics
     */
    private function renderNode(
        DOMNode $node,
        string $sourceUrl,
        array $linkMappings,
        array $mediaMappings,
        array $internalHosts,
        array &$diagnostics,
    ): string {
        if ($node instanceof DOMText) {
            return html_entity_decode($node->wholeText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $this->record($diagnostics['transformations'], 'HTML-commentaar verwijderd.');
            }

            return '';
        }

        $tag = strtolower($node->tagName);
        $className = strtolower($node->getAttribute('class'));

        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (str_starts_with($name, 'on') || $name === 'style') {
                $this->record($diagnostics['suspicious_markup'], "Inline attribuut {$name} op <{$tag}> verwijderd.");
            }
        }

        if (in_array($tag, ['script', 'style', 'noscript', 'form', 'nav', 'footer', 'aside'], true)) {
            $this->record($diagnostics['suspicious_markup'], "Element <{$tag}> met inhoud verwijderd.");

            return '';
        }

        if ($className !== '' && preg_match('/(?:^|\s)(?:social|share|sharing|addtoany|heateor|sd-content|widget)(?:[-_\s]|$)/', $className) === 1) {
            $this->record($diagnostics['transformations'], "Social- of widgetmarkup verwijderd: {$className}.");

            return '';
        }

        if ($tag === 'a') {
            return $this->renderLink($node, $sourceUrl, $linkMappings, $mediaMappings, $internalHosts, $diagnostics);
        }

        if ($tag === 'img') {
            return $this->renderImage($node, $sourceUrl, $mediaMappings, $internalHosts, $diagnostics);
        }

        if ($tag === 'iframe') {
            return $this->renderIframe($node, $sourceUrl, $diagnostics);
        }

        $content = $this->renderChildren(
            $node,
            $sourceUrl,
            $linkMappings,
            $mediaMappings,
            $internalHosts,
            $diagnostics,
        );

        if (preg_match('/(?:elementor|wp-block-group|et_pb_|vc_|wpb_)/', $className) === 1) {
            $this->record($diagnostics['transformations'], "Themawrapper uitgepakt: {$className}.");
        }

        return match (true) {
            in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) => "\n\n".trim($content)."\n\n",
            in_array($tag, ['p', 'div', 'section', 'article', 'header', 'main', 'blockquote', 'pre'], true) => "\n\n".$content."\n\n",
            $tag === 'br' => "\n",
            $tag === 'li' => "\n- ".trim($content),
            $tag === 'tr' => "\n".trim($content),
            in_array($tag, ['td', 'th'], true) => trim($content).' | ',
            default => $content,
        };
    }

    /**
     * @param  array<string, string>  $linkMappings
     * @param  array<string, string>  $mediaMappings
     * @param  list<string>  $internalHosts
     * @param  Diagnostics  $diagnostics
     */
    private function renderChildren(
        DOMNode $node,
        string $sourceUrl,
        array $linkMappings,
        array $mediaMappings,
        array $internalHosts,
        array &$diagnostics,
    ): string {
        $content = '';

        foreach ($node->childNodes as $childNode) {
            $content .= $this->renderNode(
                $childNode,
                $sourceUrl,
                $linkMappings,
                $mediaMappings,
                $internalHosts,
                $diagnostics,
            );
        }

        return $content;
    }

    /**
     * @param  array<string, string>  $linkMappings
     * @param  array<string, string>  $mediaMappings
     * @param  list<string>  $internalHosts
     * @param  Diagnostics  $diagnostics
     */
    private function renderLink(
        DOMElement $node,
        string $sourceUrl,
        array $linkMappings,
        array $mediaMappings,
        array $internalHosts,
        array &$diagnostics,
    ): string {
        $label = trim($this->renderChildren(
            $node,
            $sourceUrl,
            $linkMappings,
            $mediaMappings,
            $internalHosts,
            $diagnostics,
        ));
        $href = $this->attributeValue($node, 'href');

        if ($href === '' || str_starts_with($href, '#')) {
            return $label;
        }

        if ($this->isUnsafeUrl($href)) {
            $this->record($diagnostics['suspicious_markup'], "Onveilige link verwijderd: {$href}.");

            return $label;
        }

        $url = $this->absoluteUrl($href, $sourceUrl);
        $mappedUrl = $linkMappings[$this->canonicalUrl($url)]
            ?? $mediaMappings[$this->canonicalMediaUrl($url)]
            ?? null;

        if (is_string($mappedUrl)) {
            $this->record($diagnostics['transformations'], "Link herschreven: {$url} → {$mappedUrl}.");

            return $this->labelledUrl($label, $mappedUrl);
        }

        if ($this->isInternalUrl($url, $internalHosts)) {
            $this->record($diagnostics['unresolved_links'], $url);
        }

        return $this->labelledUrl($label, $url);
    }

    /**
     * @param  array<string, string>  $mediaMappings
     * @param  list<string>  $internalHosts
     * @param  Diagnostics  $diagnostics
     */
    private function renderImage(
        DOMElement $node,
        string $sourceUrl,
        array $mediaMappings,
        array $internalHosts,
        array &$diagnostics,
    ): string {
        $src = $this->attributeValue($node, 'src');
        $alt = $this->attributeValue($node, 'alt');

        if ($src === '' || $this->isUnsafeUrl($src)) {
            $this->record($diagnostics['suspicious_markup'], 'Afbeelding zonder veilige bron verwijderd.');

            return '';
        }

        $url = $this->absoluteUrl($src, $sourceUrl);
        $mappedUrl = $mediaMappings[$this->canonicalMediaUrl($url)] ?? null;
        $label = $alt !== '' ? $alt : 'Afbeelding';

        if (is_string($mappedUrl)) {
            $this->record($diagnostics['transformations'], "Media herschreven: {$url} → {$mappedUrl}.");

            return "\n\nAfbeelding: {$label} ({$mappedUrl})\n\n";
        }

        if ($this->isInternalUrl($url, $internalHosts)) {
            $this->record($diagnostics['missing_media'], $url);
        }

        return "\n\nAfbeelding: {$label} ({$url})\n\n";
    }

    /** @param Diagnostics $diagnostics */
    private function renderIframe(DOMElement $node, string $sourceUrl, array &$diagnostics): string
    {
        $src = $this->absoluteUrl($this->attributeValue($node, 'src'), $sourceUrl);
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be'], true)) {
            $this->record($diagnostics['transformations'], "YouTube-embed als link behouden: {$src}.");

            return "\n\nVideo: {$src}\n\n";
        }

        $this->record($diagnostics['suspicious_markup'], "Niet-ondersteunde iframe verwijderd: {$src}.");

        return '';
    }

    private function normalizeWhitespace(string $content): string
    {
        $content = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $content);
        $content = preg_replace('/[ \t]+/', ' ', $content) ?? $content;
        $content = preg_replace('/ *\n */', "\n", $content) ?? $content;
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        return trim($content);
    }

    private function labelledUrl(string $label, string $url): string
    {
        return $label === '' || $label === $url ? $url : "{$label} ({$url})";
    }

    private function attributeValue(DOMElement $node, string $attribute): string
    {
        return trim(
            html_entity_decode(trim($node->getAttribute($attribute)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            " \t\n\r\0\x0B\\\"'",
        );
    }

    private function isUnsafeUrl(string $url): bool
    {
        return preg_match('/^\s*(?:javascript|data|vbscript):/i', $url) === 1;
    }

    /** @param list<string> $internalHosts */
    private function isInternalUrl(string $url, array $internalHosts): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && in_array($host, $internalHosts, true);
    }

    private function absoluteUrl(string $url, string $sourceUrl): string
    {
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return ((string) parse_url($sourceUrl, PHP_URL_SCHEME) ?: 'https').':'.$url;
        }

        if (parse_url($url, PHP_URL_SCHEME) !== null || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        $scheme = (string) parse_url($sourceUrl, PHP_URL_SCHEME);
        $host = (string) parse_url($sourceUrl, PHP_URL_HOST);
        $port = parse_url($sourceUrl, PHP_URL_PORT);
        $origin = $scheme.'://'.$host.($port !== null ? ':'.$port : '');

        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }

        $path = (string) parse_url($sourceUrl, PHP_URL_PATH);

        return $origin.rtrim(str_replace('\\', '/', dirname($path)), '/').'/'.$url;
    }

    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return rtrim($url, '/');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = rtrim($parts['path'] ?? '', '/');

        return "{$scheme}://{$host}{$port}{$path}";
    }

    private function canonicalMediaUrl(string $url): string
    {
        $url = preg_replace('/-\d+x\d+(?=\.[a-z0-9]+(?:\?|$))/i', '', $url) ?? $url;

        return $this->canonicalUrl($url);
    }

    /** @return Diagnostics */
    private function emptyDiagnostics(): array
    {
        return [
            'unresolved_links' => [],
            'missing_media' => [],
            'suspicious_markup' => [],
            'transformations' => [],
        ];
    }

    /** @param list<string> $values */
    private function record(array &$values, string $value): void
    {
        if (! in_array($value, $values, true)) {
            $values[] = $value;
        }
    }
}
