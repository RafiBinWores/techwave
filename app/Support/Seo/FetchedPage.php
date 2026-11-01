<?php

namespace App\Support\Seo;

readonly class FetchedPage
{
    public function __construct(
        public string $finalUrl,
        public string $body,
        public int $status,
        public array $headers = [],
        public array $redirects = [],
        public int $elapsedMilliseconds = 0,
    ) {}
}
