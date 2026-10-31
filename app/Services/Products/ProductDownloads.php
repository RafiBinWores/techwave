<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductDownloads
{
    public function eligible(?User $user, Product $product): bool
    {
        return $user !== null;
    }

    public function available(Product $product): bool
    {
        return $product->is_published
            && ! $product->trashed()
            && $product->release_path
            && preg_match('#^products/'.$product->id.'/releases/[a-z0-9-]+\.(exe|msi|zip)$#', $product->release_path)
            && $this->releaseDisk($product) !== null;
    }

    public function response(Product $product, ?User $user): Response
    {
        abort_unless($product->is_published && ! $product->trashed(), 404);
        abort_unless($this->eligible($user, $product), $user ? 403 : 401);
        abort_unless($this->available($product), 404, 'No software release has been published yet.');

        $disk = Storage::disk($this->releaseDisk($product));
        $response = $disk->download(
            $product->release_path,
            basename($product->release_name),
            [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
                'X-Accel-Buffering' => 'no',
            ],
        );

        // Keep downloads authenticated while allowing large transfers to outlast PHP's normal page timeout.
        $transfer = $response->getCallback();
        $response->setCallback(static function () use ($transfer): void {
            if (function_exists('set_time_limit')) {
                set_time_limit(0);
            }

            $transfer();
        });

        return $response;
    }

    /**
     * Prefer configured cloud storage while preserving releases saved before that integration.
     */
    private function releaseDisk(Product $product): ?string
    {
        foreach (['documents', 'local'] as $disk) {
            if (Storage::disk($disk)->exists($product->release_path)) {
                return $disk;
            }
        }

        return null;
    }
}
