<?php

namespace App\Http\Controllers;

use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhmcsSsoController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $user = Auth::user();

        $account = $user->whmcsAccount;

        if (! $account || ! $account->whmcs_client_id) {
            return redirect()->route('account.dashboard')->with('error', 'WHMCS account is not properly linked.');
        }

        try {
            $ssoUrl = app(WhmcsApi::class)->getSsoUrl($account->whmcs_client_id);

            return redirect($ssoUrl);
        } catch (WhmcsApiException $e) {
            return redirect()->route('account.dashboard')->with('error', 'Unable to connect to billing system. Please try again later.');
        }
    }
}
