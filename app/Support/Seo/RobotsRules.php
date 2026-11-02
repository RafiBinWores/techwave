<?php

namespace App\Support\Seo;

final class RobotsRules
{
    /** @return array{allowed: bool, rule: ?string} */
    public function evaluate(string $body, string $url, string $agent): array
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $hasRules = false;
        foreach (preg_split('/\r\n|\r|\n/', ltrim($body, "\xEF\xBB\xBF")) as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if (! str_contains($line, ':')) {
                continue;
            }
            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);
            if ($field === 'user-agent') {
                if ($hasRules) {
                    $groups[] = compact('agents', 'rules');
                    $agents = [];
                    $rules = [];
                    $hasRules = false;
                }
                if ($value === '*' || preg_match('/^[a-z_-]+$/iD', $value)) {
                    $agents[] = strtolower($value);
                }
            } elseif (in_array($field, ['allow', 'disallow'], true) && $agents !== []) {
                $hasRules = true;
                if (str_starts_with($value, '/')) {
                    $rules[] = ['allow' => $field === 'allow', 'path' => $value];
                }
            }
        }
        $groups[] = compact('agents', 'rules');
        $bestAgent = -1;
        $applicable = [];
        foreach ($groups as $group) {
            $specificity = -1;
            foreach ($group['agents'] as $candidate) {
                if ($candidate === '*') {
                    $specificity = max($specificity, 0);
                } elseif (stripos($agent, $candidate) !== false) {
                    $specificity = max($specificity, strlen($candidate));
                }
            }
            if ($specificity < 0 || $specificity < $bestAgent) {
                continue;
            }
            if ($specificity > $bestAgent) {
                $applicable = [];
                $bestAgent = $specificity;
            }
            $applicable = array_merge($applicable, $group['rules']);
        }
        $target = $this->normalize((parse_url($url, PHP_URL_PATH) ?: '/').(($query = parse_url($url, PHP_URL_QUERY)) !== null ? '?'.$query : ''));
        $matched = null;
        $longest = -1;
        foreach ($applicable as $rule) {
            $pattern = $this->normalize($rule['path']);
            $anchored = str_ends_with($pattern, '$');
            $pattern = $anchored ? substr($pattern, 0, -1) : $pattern;
            $regex = '~^'.str_replace('\\*', '.*', preg_quote($pattern, '~')).($anchored ? '$' : '').'~D';
            $length = strlen(str_replace('*', '', $pattern));
            if (preg_match($regex, $target) && ($length > $longest || ($length === $longest && $rule['allow']))) {
                $matched = $rule;
                $longest = $length;
            }
        }

        return ['allowed' => $matched === null || $matched['allow'], 'rule' => $matched === null ? null : ($matched['allow'] ? 'Allow: ' : 'Disallow: ').$matched['path']];
    }

    private function normalize(string $path): string
    {
        $path = preg_replace_callback('/[^\x21-\x7E]/', fn ($match) => rawurlencode($match[0]), $path);

        return preg_replace_callback('/%([0-9a-f]{2})/i', function ($match) {
            $character = chr(hexdec($match[1]));

            return preg_match('/[a-z0-9._~-]/i', $character) ? $character : '%'.strtoupper($match[1]);
        }, $path);
    }
}
