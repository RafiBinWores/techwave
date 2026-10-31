<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductPricing
{
    public static function price(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = (string) $value;
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $text)) {
            throw ValidationException::withMessages([$field => 'Enter a positive price with at most two decimal places.']);
        }
        $parts = explode('.', $text);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        $cents = str_pad($parts[1] ?? '', 2, '0');
        if ($whole === '0' && $cents === '00') {
            throw ValidationException::withMessages([$field => 'The price must be greater than zero.']);
        }

        return $whole.'.'.$cents;
    }

    public static function safeUrl(?string $url): bool
    {
        return ! $url || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) && ! parse_url($url, PHP_URL_USER) && ! parse_url($url, PHP_URL_PASS));
    }

    /** @return array<string,mixed> */
    public function validate(Product $product, array $input, ?ProductPlan $plan = null): array
    {
        if ($plan && $plan->product_id !== $product->id) {
            throw ValidationException::withMessages(['plan' => 'This plan belongs to another product.']);
        }
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'badge' => 'nullable|string|max:80', 'description' => 'nullable|string|max:3000', 'features' => 'array|max:40', 'features.*' => 'string|max:500', 'pricing_mode' => ['required', Rule::in(['subscription', 'one_time', 'quotation'])], 'currency' => ['sometimes', Rule::in(['BDT'])], 'is_active' => 'sometimes|boolean', 'sort_order' => 'sometimes|integer|min:0|max:100000', 'limits' => 'array|max:20', 'confirm_pricing_change' => 'sometimes|boolean'])->validate();
        $mode = $data['pricing_mode'];
        $data['currency'] = 'BDT';
        foreach (['monthly', 'yearly', 'one_time'] as $cycle) {
            $key = $cycle.'_price';
            $data[$key] = ($mode === 'subscription' && $cycle !== 'one_time' || $mode === 'one_time' && $cycle === 'one_time') ? self::price($input[$key] ?? null, $key) : null;
            $url = $input[$cycle.'_checkout_url'] ?? null;
            if (! is_string($url) && $url !== null || is_string($url) && strlen($url) > 2048 || ! self::safeUrl($url)) {
                throw ValidationException::withMessages([$cycle.'_checkout_url' => 'Use an HTTP or HTTPS checkout URL without embedded credentials.']);
            }
            $data[$cycle.'_checkout_url'] = $url ?: null;
        }
        if ($mode === 'subscription' && ! $data['monthly_price'] && ! $data['yearly_price']) {
            throw ValidationException::withMessages(['monthly_price' => 'Enable at least one monthly or yearly price.']);
        }
        if ($mode === 'one_time' && ! $data['one_time_price']) {
            throw ValidationException::withMessages(['one_time_price' => 'Enter the one-time price.']);
        }
        if ($plan && $plan->is_active && $product->is_published && $plan->pricing_mode !== $mode && ! ($input['confirm_pricing_change'] ?? false)) {
            throw ValidationException::withMessages(['confirm_pricing_change' => 'Confirm this published plan pricing-mode change.']);
        }
        $known = array_column($product->limit_definitions ?? [], 'key');
        $limits = [];
        foreach ($input['limits'] ?? [] as $key => $value) {
            if (! in_array($key, $known, true) || ! is_array($value)) {
                throw ValidationException::withMessages(['limits' => 'Use only this product’s defined limit keys.']);
            }
            $unlimited = $value['unlimited'] ?? false;
            if (! in_array($unlimited, [true, false, 0, 1, '0', '1'], true)) {
                throw ValidationException::withMessages(['limits' => 'Invalid unlimited option.']);
            }
            $amount = $value['amount'] ?? null;
            if ($unlimited && $amount !== null && $amount !== '') {
                throw ValidationException::withMessages(['limits' => 'Choose a finite amount or unlimited, not both.']);
            }
            if (! $unlimited && ($amount === null || $amount === '')) {
                continue;
            }
            if (! $unlimited && (filter_var($amount, FILTER_VALIDATE_INT) === false || (int) $amount < 1 || (int) $amount > 1000000000000)) {
                throw ValidationException::withMessages(['limits' => 'Limits must be positive whole numbers, or explicitly unlimited.']);
            }
            $limits[$key] = $unlimited ? ['unlimited' => true] : ['amount' => (int) $amount];
        }
        $data['limits'] = $limits;
        unset($data['confirm_pricing_change']);

        return $data;
    }

    public function savePlan(Product $product, array $input, ?ProductPlan $plan = null): ProductPlan
    {
        $data = $this->validate($product, $input, $plan);

        return DB::transaction(function () use ($product, $plan, $data): ProductPlan {
            $data['product_id'] = $product->id;
            if ($plan) {
                $plan->update($data);

                return $plan->fresh();
            }

            return ProductPlan::create($data);
        });
    }

    /** @return array<string,string|null>|null */
    public static function summary(Product $product): ?array
    {
        if (! $product->pricing_enabled) {
            return null;
        }
        $offers = [];
        $quote = false;
        foreach ($product->publishedPlans as $plan) {
            if ($plan->pricing_mode === 'quotation') {
                $quote = true;
            }
            foreach ($plan->effectivePricing() as $cycle => $price) {
                if ($price !== null) {
                    $parts = explode('.', $price);
                    $offers[] = ['minor' => (int) $parts[0] * 100 + (int) ($parts[1] ?? 0), 'price' => $price, 'period' => ['monthly' => 'month', 'yearly' => 'year', 'one_time' => 'one-time'][$cycle]];
                }
            }
        }
        if (! $offers) {
            return $quote ? ['price' => null, 'period' => null] : null;
        }
        usort($offers, fn ($a, $b) => $a['minor'] <=> $b['minor']);

        return ['price' => $offers[0]['price'], 'period' => $offers[0]['period']];
    }
}
