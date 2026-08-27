<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\WhmcsApi;
use App\Services\WhmcsApiException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class WhmcsInvoiceController extends Controller
{
    public function download(string $invoiceId): RedirectResponse|Response
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        $account = $user->whmcsAccount;

        /*
    |--------------------------------------------------------------------------
    | Make Sure Billing Account Is Connected
    |--------------------------------------------------------------------------
    */

        if (! $account) {
            return back()->withErrors([
                'billing' => 'No billing account linked.',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | WHMCS Client ID
    |--------------------------------------------------------------------------
    |
    | WHMCS invoices belong to Client Accounts.
    | Do NOT use whmcs_user_id for invoice ownership.
    |
    */

        $clientId = $account->whmcs_client_id;

        if (! $clientId) {
            return back()->withErrors([
                'billing' => 'WHMCS Client ID is missing from the linked billing account.',
            ]);
        }

        $api = app(WhmcsApi::class);

        /*
    |--------------------------------------------------------------------------
    | Get Invoice
    |--------------------------------------------------------------------------
    */

        try {
            $invoiceResponse = $api->request(
                'GetInvoice',
                [
                    'invoiceid' => $invoiceId,
                ]
            );
        } catch (WhmcsApiException $e) {
            logger()->warning(
                'WHMCS GetInvoice failed.',
                [
                    'invoice_id' => $invoiceId,
                    'techwave_user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]
            );

            abort(404, 'Invoice not found.');
        }

        /*
    |--------------------------------------------------------------------------
    | Normalize Invoice
    |--------------------------------------------------------------------------
    */

        $invoice = (array) (
            $invoiceResponse['invoice']
            ?? $invoiceResponse
        );

        /*
    |--------------------------------------------------------------------------
    | Verify Invoice Ownership
    |--------------------------------------------------------------------------
    */

        if (
            (string) ($invoice['userid'] ?? '')
            !== (string) $clientId
        ) {
            logger()->warning(
                'Unauthorized WHMCS invoice access attempt.',
                [
                    'techwave_user_id' => $user->id,
                    'whmcs_client_id' => $clientId,
                    'invoice_userid' => $invoice['userid'] ?? null,
                    'invoice_id' => $invoiceId,
                ]
            );

            abort(403);
        }

        /*
    |--------------------------------------------------------------------------
    | Get WHMCS Client Details
    |--------------------------------------------------------------------------
    */

        $billingEmail =
            $account->whmcs_email
            ?? $account->email
            ?? $user->email;

        $clientData = null;

        try {
            $clientResponse = $api->getClientDetails(
                $clientId,
                $billingEmail,
            );

            if (is_array($clientResponse)) {
                /*
             * WHMCS may return:
             *
             * [
             *     'result' => 'success',
             *     'client' => [...]
             * ]
             *
             * Or your API wrapper may already return the client array.
             */

                if (
                    isset($clientResponse['client'])
                    && is_array($clientResponse['client'])
                ) {
                    $clientData = $clientResponse['client'];
                } else {
                    $clientData = $clientResponse;
                }
            }
        } catch (WhmcsApiException $e) {
            logger()->warning(
                'WHMCS client details could not be retrieved.',
                [
                    'whmcs_client_id' => $clientId,
                    'message' => $e->getMessage(),
                ]
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Map Client Billing Information
    |--------------------------------------------------------------------------
    |
    | We only fill missing invoice fields.
    |
    | This helps preserve historical invoice data if WHMCS has stored an
    | invoice snapshot.
    |
    */

        if (is_array($clientData)) {
            $mappedClientFields = [
                'clientfirstname' => $clientData['firstname']
                    ?? '',

                'clientlastname' => $clientData['lastname']
                    ?? '',

                'clientname' => $clientData['fullname']
                    ?? trim(
                        ($clientData['firstname'] ?? '')
                            .' '.
                            ($clientData['lastname'] ?? '')
                    ),

                'clientcompanyname' => $clientData['companyname']
                    ?? '',

                'clientemail' => $clientData['email']
                    ?? '',

                'clientphonenumber' => $clientData['phonenumberformatted']
                    ?? $clientData['phonenumber']
                    ?? '',

                'clientaddress1' => $clientData['address1']
                    ?? '',

                'clientaddress2' => $clientData['address2']
                    ?? '',

                'clientcity' => $clientData['city']
                    ?? '',

                'clientstate' => $clientData['fullstate']
                    ?? $clientData['state']
                    ?? '',

                'clientpostcode' => $clientData['postcode']
                    ?? '',

                'clientcountry' => $clientData['countryname']
                    ?? $clientData['country']
                    ?? '',
            ];

            foreach ($mappedClientFields as $key => $value) {
                if (
                    ! isset($invoice[$key])
                    || trim((string) $invoice[$key]) === ''
                ) {
                    $invoice[$key] = $value;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Currency Fallback
        |--------------------------------------------------------------------------
        */

            if (
                empty($invoice['currencycode'])
                && ! empty($clientData['currency_code'])
            ) {
                $invoice['currencycode'] =
                    $clientData['currency_code'];
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Default Pay To Information
    |--------------------------------------------------------------------------
    */

        $payTo = [
            'name' => config(
                'services.whmcs.company_name',
                'TechWave'
            ),

            'address' => config(
                'services.whmcs.company_address',
                ''
            ),
        ];

        /*
    |--------------------------------------------------------------------------
    | WHMCS Configuration Helper
    |--------------------------------------------------------------------------
    */

        $getWhmcsConfigurationValue = function (
            array $settingNames
        ) use ($api): ?string {
            foreach ($settingNames as $settingName) {
                try {
                    $response = $api->request(
                        'GetConfigurationValue',
                        [
                            'setting' => $settingName,
                        ]
                    );

                    $value = trim(
                        (string) (
                            $response['value']
                            ?? ''
                        )
                    );

                    if ($value !== '') {
                        return $value;
                    }
                } catch (WhmcsApiException $e) {
                    logger()->warning(
                        'Unable to retrieve WHMCS configuration value.',
                        [
                            'setting' => $settingName,
                            'message' => $e->getMessage(),
                        ]
                    );
                }
            }

            return null;
        };

        /*
    |--------------------------------------------------------------------------
    | WHMCS Company Name
    |--------------------------------------------------------------------------
    */

        $whmcsCompanyName =
            $getWhmcsConfigurationValue([
                'CompanyName',
            ]);

        if ($whmcsCompanyName !== null) {
            $payTo['name'] =
                trim($whmcsCompanyName);
        }

        /*
    |--------------------------------------------------------------------------
    | WHMCS Pay To Address
    |--------------------------------------------------------------------------
    |
    | WHMCS:
    | General Settings -> General -> Pay To Text
    |
    | InvoicePayTo is checked first.
    |
    */

        $whmcsPayToAddress =
            $getWhmcsConfigurationValue([
                'InvoicePayTo',
                'PayTo',
                'PayToText',
            ]);

        if ($whmcsPayToAddress !== null) {
            /*
        |--------------------------------------------------------------------------
        | Normalize WHMCS Pay To Formatting
        |--------------------------------------------------------------------------
        |
        | Convert <br>, <br/>, <br /> into line breaks in case WHMCS stores
        | HTML-style line breaks.
        |
        */

            $normalizedPayTo = preg_replace(
                '/<br\s*\/?>/i',
                "\n",
                $whmcsPayToAddress
            );

            $normalizedPayTo = trim(
                (string) $normalizedPayTo
            );

            /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Company Name
        |--------------------------------------------------------------------------
        |
        | Example WHMCS Pay To:
        |
        | TechWave
        | House 10
        | Dhaka
        |
        | CompanyName already gives us TechWave.
        |
        | Therefore remove the first Pay To line if it matches CompanyName.
        |
        */

            if (
                $payTo['name'] !== ''
                && $normalizedPayTo !== ''
            ) {
                $lines = preg_split(
                    '/\r\n|\r|\n/',
                    $normalizedPayTo
                );

                $lines = is_array($lines)
                    ? $lines
                    : [];

                /*
            |--------------------------------------------------------------------------
            | Remove Leading Empty Lines
            |--------------------------------------------------------------------------
            */

                while (
                    isset($lines[0])
                    && trim((string) $lines[0]) === ''
                ) {
                    array_shift($lines);
                }

                /*
            |--------------------------------------------------------------------------
            | Remove Company Name If First Line Matches
            |--------------------------------------------------------------------------
            */

                if (
                    isset($lines[0])
                    && strcasecmp(
                        trim(
                            strip_tags(
                                (string) $lines[0]
                            )
                        ),
                        trim(
                            strip_tags(
                                (string) $payTo['name']
                            )
                        )
                    ) === 0
                ) {
                    array_shift($lines);
                }

                /*
            |--------------------------------------------------------------------------
            | Remove New Leading Empty Lines
            |--------------------------------------------------------------------------
            */

                while (
                    isset($lines[0])
                    && trim((string) $lines[0]) === ''
                ) {
                    array_shift($lines);
                }

                $normalizedPayTo = trim(
                    implode(
                        "\n",
                        $lines
                    )
                );
            }

            if ($normalizedPayTo !== '') {
                $payTo['address'] =
                    $normalizedPayTo;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Invoice Payment Method
    |--------------------------------------------------------------------------
    */

        $paymentMethodModule = trim(
            (string) (
                $invoice['paymentmethod']
                ?? ''
            )
        );

        $paymentMethodName = '';

        /*
    |--------------------------------------------------------------------------
    | Get Friendly Payment Method Name
    |--------------------------------------------------------------------------
    */

        if ($paymentMethodModule !== '') {
            try {
                $paymentMethodsResponse =
                    $api->request(
                        'GetPaymentMethods'
                    );

                $paymentMethods = data_get(
                    $paymentMethodsResponse,
                    'paymentmethods.paymentmethod',
                    []
                );

                /*
            |--------------------------------------------------------------------------
            | Normalize Single Payment Method
            |--------------------------------------------------------------------------
            */

                if (
                    is_array($paymentMethods)
                    && isset($paymentMethods['module'])
                ) {
                    $paymentMethods = [
                        $paymentMethods,
                    ];
                }

                if (is_array($paymentMethods)) {
                    foreach ($paymentMethods as $method) {
                        if (! is_array($method)) {
                            continue;
                        }

                        $module = trim(
                            (string) (
                                $method['module']
                                ?? ''
                            )
                        );

                        if ($module !== $paymentMethodModule) {
                            continue;
                        }

                        $paymentMethodName = trim(
                            (string) (
                                $method['displayname']
                                ?? ''
                            )
                        );

                        break;
                    }
                }
            } catch (WhmcsApiException $e) {
                logger()->warning(
                    'WHMCS payment methods could not be retrieved.',
                    [
                        'invoice_id' => $invoiceId,
                        'payment_method' => $paymentMethodModule,
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Friendly Payment Method Fallback
    |--------------------------------------------------------------------------
    */

        if (
            $paymentMethodName === ''
            && $paymentMethodModule !== ''
        ) {
            $paymentMethodName = ucwords(
                preg_replace(
                    '/(?<!^)([A-Z])/',
                    ' $1',
                    str_replace(
                        ['_', '-'],
                        ' ',
                        $paymentMethodModule
                    )
                )
            );
        }

        $invoice['paymentmethodname'] =
            $paymentMethodName;

        /*
    |--------------------------------------------------------------------------
    | Techwave Site Settings
    |--------------------------------------------------------------------------
    */

        $setting =
            SiteSetting::query()->first();

        /*
    |--------------------------------------------------------------------------
    | Generate PDF
    |--------------------------------------------------------------------------
    */

        $pdf = Pdf::loadView(
            'pdf.whmcs-invoice',
            [
                'invoice' => $invoice,
                'setting' => $setting,
                'payTo' => $payTo,
            ]
        )
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        /*
    |--------------------------------------------------------------------------
    | Download PDF
    |--------------------------------------------------------------------------
    */

        return $pdf->download(
            'invoice-'.$invoiceId.'.pdf'
        );
    }
}
