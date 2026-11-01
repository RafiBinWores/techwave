<?php

namespace App\Support\Seo;

final class Finding
{
    public static function check(string $label, string $status, string $message, string $recommendation = ''): array
    {
        return compact('label', 'status', 'message', 'recommendation');
    }

    public static function rule(string $label, bool $passed, string $good, string $bad, string $recommendation, string $failure = 'warning'): array
    {
        return self::check($label, $passed ? 'pass' : $failure, $passed ? $good : $bad, $recommendation);
    }

    public static function make(string $id, string $category, string $label, array $checks, bool $premium = false, array $details = [], int $priority = 2, int $weight = 1): array
    {
        $states = array_column($checks, 'status');
        $status = in_array('fail', $states, true) ? 'fail' : (in_array('warning', $states, true) ? 'warning' : (count(array_filter($states, fn ($state) => $state !== 'unknown')) === 0 ? 'unknown' : 'pass'));
        $recommendation = '';
        foreach ($checks as $check) {
            if (in_array($check['status'], ['fail', 'warning'], true) && $check['recommendation'] !== '') {
                $recommendation = $check['recommendation'];
                break;
            }
        }
        $evidence = implode(' ', array_column($checks, 'message'));

        return compact('id', 'category', 'label', 'status', 'premium', 'checks', 'priority', 'weight', 'recommendation') + [
            'evidence' => mb_substr($evidence, 0, 2000), 'details' => array_slice($details, 0, 50),
            'detail_count' => count($details), 'passed' => count(array_filter($states, fn ($state) => $state === 'pass')),
            'total' => count(array_filter($states, fn ($state) => $state !== 'unknown')),
        ];
    }
}
