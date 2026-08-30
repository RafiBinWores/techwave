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

    public function domainRedirect(int $domainId): RedirectResponse
    {
        $user = Auth::user();
        $account = $user->whmcsAccount;

        if (! $account || ! $account->whmcs_client_id) {
            return redirect()->route('account.domains')->with('error', 'WHMCS account is not properly linked.');
        }

        try {
            $ssoUrl = app(WhmcsApi::class)->getSsoUrl(
                $account->whmcs_client_id,
                'clientarea:domain_details',
                $domainId,
            );

            return redirect($ssoUrl);
        } catch (WhmcsApiException) {
            return redirect()->route('account.domains')->with('error', 'Unable to connect to billing system. Please try again later.');
        }
    }

    public function serviceRedirect(int $serviceId): RedirectResponse
    {
        $user = Auth::user();
        $account = $user->whmcsAccount;

        if (! $account || ! $account->whmcs_client_id) {
            return redirect()->route('account.billing-services')->with('error', 'WHMCS account is not properly linked.');
        }

        try {
            $ssoUrl = app(WhmcsApi::class)->getSsoUrl($account->whmcs_client_id);

            return redirect($ssoUrl);
        } catch (WhmcsApiException) {
            return redirect()->route('account.billing-services')->with('error', 'Unable to connect to billing system. Please try again later.');
        }
    }
}
