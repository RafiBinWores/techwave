<?php

namespace App\Http\Controllers;

use App\Exceptions\LicenseSigningException;
use App\Services\NetworkLicensing;
use App\Services\Products\ProductContent;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NetworkLicenseController extends Controller
{
    public function publicKey()
    {
        ProductContent::authorizeAdmin();
        $path = config('network-licenses.public_key_path');
        abort_unless(is_string($path) && is_file($path), 404);
        $public = file_get_contents($path);
        abort_unless(str_contains($public, '-----BEGIN PUBLIC KEY-----') && ! str_contains($public, 'PRIVATE KEY'), 404);

        return response($public, 200, ['Content-Type' => 'application/x-pem-file',
            'Content-Disposition' => 'attachment; filename="netwatch-public.pem"', 'Cache-Control' => 'private, no-store']);
    }

    public function activate(Request $request, NetworkLicensing $licenses)
    {
        $data = $request->validate(['activation_key' => ['required', 'string', 'min:20', 'max:128'], 'installation_id' => ['required', 'uuid']]);

        return $this->reply(fn () => $licenses->activate($data['activation_key'], $data['installation_id']));
    }

    public function refresh(Request $request, NetworkLicensing $licenses)
    {
        return $this->authenticated($request, $licenses);
    }

    public function deactivate(Request $request, NetworkLicensing $licenses)
    {
        return $this->authenticated($request, $licenses, true);
    }

    private function authenticated(Request $request, NetworkLicensing $licenses, bool $deactivate = false)
    {
        $data = $request->validate(['installation_id' => ['required', 'uuid']]);
        $credential = $request->bearerToken();
        abort_unless(is_string($credential) && strlen($credential) === 64 && ctype_xdigit($credential), 401);

        return $this->reply(fn () => $licenses->refresh($credential, $data['installation_id'], $deactivate));
    }

    private function reply(callable $operation)
    {
        try {
            return response()->json($operation())->header('Cache-Control', 'private, no-store');
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'License inactive, expired, revoked, or incorrectly configured.'], 403);
        } catch (LicenseSigningException $exception) {
            report($exception);

            return response()->json(['message' => 'Licensing is temporarily unavailable.'], 503);
        }
    }
}
