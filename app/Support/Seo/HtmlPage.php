<?php

namespace App\Support\Seo;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

final class HtmlPage
{
    public readonly DOMDocument $dom;

    public readonly DOMXPath $xpath;

    private ?array $linkRows = null;

    public function __construct(public readonly FetchedPage $response)
    {
        $this->dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $this->dom->loadHTML('<?xml encoding="UTF-8">'.$response->body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $this->xpath = new DOMXPath($this->dom);
    }

    public function nodes(string $query): \DOMNodeList
    {
        return $this->xpath->query($query);
    }

    public function text(string $query): string
    {
        return self::clean($this->xpath->evaluate('string('.$query.')'), 2000);
    }

    public static function clean(string $value, int $length = 500): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', mb_convert_encoding($value, 'UTF-8', 'UTF-8')) ?? ''), 0, $length);
    }

    public function meta(string $name, string $attribute = 'name'): string
    {
        return $this->text('//meta[translate(@'.$attribute.',"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="'.$name.'"]/@content');
    }

    public function rel(string $name): string
    {
        return '//link[contains(concat(" ", normalize-space(translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")), " "), " '.$name.' ")]';
    }

    public function url(string $value, ?string $base = null): ?string
    {
        try {
            $uri = UriResolver::resolve(new Uri($base ?? $this->response->finalUrl), new Uri(trim($value)));

            return in_array(strtolower($uri->getScheme()), ['http', 'https'], true) && $uri->getHost() !== '' && $uri->getUserInfo() === '' ? (string) $uri : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function base(): string
    {
        $base = $this->text('//base/@href');

        return $base !== '' ? ($this->url($base) ?? $this->response->finalUrl) : $this->response->finalUrl;
    }

    public function visibleText(): string
    {
        $parts = [];
        foreach ($this->nodes('//body//text()[not(ancestor::script or ancestor::style or ancestor::noscript or ancestor::template or ancestor::textarea or ancestor::*[@hidden] or ancestor::*[@aria-hidden="true"])]') as $node) {
            $parts[] = $node->textContent;
        }

        return self::clean(implode(' ', $parts), 2097152);
    }

    public function words(): int
    {
        return preg_match_all('/[\p{L}\p{N}]+/u', $this->visibleText()) ?: 0;
    }

    public function headings(): array
    {
        $rows = [];
        foreach ($this->nodes('//h1|//h2|//h3|//h4|//h5|//h6') as $node) {
            $rows[] = ['level' => strtoupper($node->nodeName), 'text' => self::clean($node->textContent)];
        }

        return $rows;
    }

    public function links(): array
    {
        if ($this->linkRows !== null) {
            return $this->linkRows;
        }
        $rows = [];
        $host = strtolower(parse_url($this->response->finalUrl, PHP_URL_HOST) ?? '');
        $base = $this->base();
        foreach ($this->nodes('//a[@href]') as $node) {
            $href = trim($node->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }
            $url = $this->url($href, $base);
            if ($url === null) {
                continue;
            }
            $anchor = self::clean($node->textContent);
            $accessible = self::clean($node->getAttribute('aria-label')) ?: $anchor;
            if ($accessible === '') {
                foreach ($this->xpath->query('.//img[@alt]', $node) as $img) {
                    $accessible .= ' '.self::clean($img->getAttribute('alt'));
                }
            }
            $rows[] = [
                'url' => mb_substr($url, 0, 2048), 'text' => $anchor, 'accessible_text' => trim($accessible),
                'rel' => strtolower(self::clean($node->getAttribute('rel'))),
                'internal' => strtolower(parse_url($url, PHP_URL_HOST) ?? '') === $host,
                'query' => parse_url($url, PHP_URL_QUERY) !== null,
            ];
            if (count($rows) >= 2000) {
                break;
            }
        }

        return $this->linkRows = $rows;
    }

    public function metadata(): array
    {
        $rows = [];
        foreach ($this->nodes('//meta') as $node) {
            $rows[] = ['name' => self::clean($node->getAttribute('name') ?: $node->getAttribute('property') ?: $node->getAttribute('http-equiv') ?: 'charset'), 'content' => self::clean($node->getAttribute('content') ?: $node->getAttribute('charset'))];
            if (count($rows) >= 100) {
                break;
            }
        }

        return $rows;
    }
}
