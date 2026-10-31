<?php

namespace App\Services\Products;

use App\Enums\UserRole;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductContent
{
    public static function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::ADMIN_MANAGER], true), 403);
    }

    public static function ownsImage(Product $product, ?string $path): bool
    {
        return ! $path || (preg_match('#^products/'.$product->id.'/media/[a-z0-9-]+\.(jpg|png|webp)$#', $path) && Storage::disk('public')->exists($path));
    }

    public function save(array $input, ?Product $product = null): Product
    {
        if (trim($input['slug'] ?? '') === '') {
            $input['slug'] = $product?->slug;
            if (! $input['slug']) {
                $base = rtrim(substr(Str::slug($input['name'] ?? ''), 0, 160), '-') ?: 'software';
                $input['slug'] = $base;
                $suffix = 2;
                while (Product::withTrashed()->where('slug', $input['slug'])->exists()) {
                    $input['slug'] = $base.'-'.$suffix++;
                }
            }
        }
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($product?->id)], 'summary' => 'required|string|max:500', 'description' => 'nullable|string|max:50000', 'demo_url' => 'nullable|string|max:2048', 'is_published' => 'sometimes|boolean', 'pricing_enabled' => 'sometimes|boolean', 'show_in_navbar' => 'sometimes|boolean', 'sort_order' => 'sometimes|integer|min:0|max:100000', 'gallery' => 'array|max:30', 'gallery.*.path' => 'required|string|max:255', 'gallery.*.caption' => 'nullable|string|max:500', 'gallery.*.alt' => 'nullable|string|max:200', 'tutorials' => 'array|max:20', 'tutorials.*.title' => 'required|string|max:160', 'tutorials.*.url' => 'required|string|max:2048', 'installation_steps' => 'array|max:40', 'installation_steps.*.title' => 'required|string|max:160', 'installation_steps.*.text' => 'nullable|string|max:10000', 'installation_steps.*.command' => 'nullable|string|max:10000', 'installation_steps.*.screenshot_path' => 'nullable|string|max:255', 'installation_steps.*.caption' => 'nullable|string|max:500', 'limit_definitions' => 'array|max:20', 'limit_definitions.*.key' => 'required|string|max:64|regex:/^[a-z][a-z0-9_]*$/|distinct', 'limit_definitions.*.label' => 'required|string|max:100', 'limit_definitions.*.unit' => 'nullable|string|max:40', 'limit_definitions.*.description' => 'nullable|string|max:500'])->validate();
        if (! ProductPricing::safeUrl($data['demo_url'] ?? null)) {
            throw ValidationException::withMessages(['demo_url' => 'Use an HTTP or HTTPS demo URL without embedded credentials.']);
        }
        foreach ($data['tutorials'] ?? [] as $entry) {
            if (! ProductPricing::safeUrl($entry['url'])) {
                throw ValidationException::withMessages(['tutorials' => 'Use HTTP or HTTPS video links.']);
            }
        }
        foreach ($data['gallery'] ?? [] as $entry) {
            if (! $product || ! self::ownsImage($product, $entry['path'])) {
                throw ValidationException::withMessages(['gallery' => 'Only images uploaded for this product can be selected.']);
            }
        }
        foreach ($data['installation_steps'] ?? [] as $entry) {
            if (! empty($entry['screenshot_path']) && (! $product || ! self::ownsImage($product, $entry['screenshot_path']))) {
                throw ValidationException::withMessages(['installation_steps' => 'Upload this screenshot under the current product.']);
            }
        }
        $keys = array_column($data['limit_definitions'] ?? [], 'key');
        foreach ($product?->limit_definitions ?? [] as $old) {
            if (! in_array($old['key'], $keys, true)) {
                foreach ($product->publishedPlans()->get() as $plan) {
                    if (array_key_exists($old['key'], $plan->limits ?? [])) {
                        throw ValidationException::withMessages(['limit_definitions' => 'A published plan uses '.$old['key'].'. Update or archive that plan first.']);
                    }
                }
            }
        }
        foreach (['gallery' => ['path', 'caption', 'alt'], 'tutorials' => ['title', 'url'], 'installation_steps' => ['title', 'text', 'command', 'screenshot_path', 'caption'], 'limit_definitions' => ['key', 'label', 'unit', 'description']] as $field => $allowed) {
            $data[$field] = array_values(array_map(fn ($row) => array_intersect_key($row, array_flip($allowed)), $data[$field] ?? []));
        }
        if ($product) {
            $product->update($data);

            return $product->fresh();
        }

        return Product::create($data);
    }
}
