<?php

namespace App\Http\Controllers;

use App\Models\NetworkLicense;
use App\Models\Product;
use App\Services\NetworkLicensing;
use App\Services\Products\ProductDownloads;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProductDownloadController extends Controller
{
    public function __construct(
        private readonly NetworkLicensing $licensing,
        private readonly ProductDownloads $downloads,
    ) {}

    public function download(Request $request, string $slug): Response
    {
        $product = Product::published()->where('slug', $slug)->firstOrFail();

        if ($this->licensing->isProduct($product)) {
            return redirect()->route('client.products.download.setup', $product->slug);
        }

        return $this->downloads->response($product, $request->user());
    }

    public function installer(Request $request, string $slug): Response
    {
        $product = Product::published()->where('slug', $slug)->firstOrFail();
        abort_unless($this->licensing->isProduct($product), 404);

        // Recheck the signed-in customer's license before streaming the private installer.
        $license = NetworkLicense::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)->first();
        abort_unless($license, 403, 'Start your trial or activate a paid subscription before downloading.');

        try {
            $this->licensing->limits($license);
        } catch (ValidationException $exception) {
            abort(403, 'Your trial or subscription is inactive. Choose a paid plan to continue.');
        }

        return $this->downloads->response($product, $request->user());
    }
}
