<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $toNull = function ($value) {
            if ($value === null) {
                return null;
            }
            $v = trim((string) $value);
            return $v === '' ? null : $v;
        };
        $toUpperNull = function ($value) use ($toNull) {
            $v = $toNull($value);
            return $v === null ? null : strtoupper($v);
        };

        $request->merge([
            'gst_legal_name' => $toUpperNull($request->input('gst_legal_name')),
            'gst_trade_name' => $toUpperNull($request->input('gst_trade_name')),
            'gst_gstin' => $toUpperNull($request->input('gst_gstin')),
            'gst_pan' => $toUpperNull($request->input('gst_pan')),
            'gst_address_1' => $toNull($request->input('gst_address_1')),
            'gst_address_2' => $toNull($request->input('gst_address_2')),
            'gst_city' => $toUpperNull($request->input('gst_city')),
            'gst_district' => $toUpperNull($request->input('gst_district')),
            'gst_state' => $toUpperNull($request->input('gst_state')),
            'gst_state_code' => $toNull($request->input('gst_state_code')),
            'gst_pin_code' => $toNull($request->input('gst_pin_code')),
            'gst_country' => $toUpperNull($request->input('gst_country')),
            'gst_phone' => $toNull($request->input('gst_phone')),
            'gst_email' => $toNull($request->input('gst_email')),
            'dispatch_from_name' => $toUpperNull($request->input('dispatch_from_name')),
            'dispatch_from_address' => $toNull($request->input('dispatch_from_address')),
            'dispatch_from_city' => $toUpperNull($request->input('dispatch_from_city')),
            'dispatch_from_state' => $toUpperNull($request->input('dispatch_from_state')),
            'dispatch_from_state_code' => $toNull($request->input('dispatch_from_state_code')),
            'dispatch_from_pin_code' => $toNull($request->input('dispatch_from_pin_code')),
        ]);

        $request->validate([
            'gst_legal_name' => ['nullable', 'string', 'max:255'],
            'gst_trade_name' => ['nullable', 'string', 'max:255'],
            'gst_gstin' => ['nullable', 'string', 'regex:/^\\d{2}[A-Z]{5}\\d{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/'],
            'gst_pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gst_address_1' => ['nullable', 'string', 'max:255'],
            'gst_address_2' => ['nullable', 'string', 'max:255'],
            'gst_city' => ['nullable', 'string', 'max:100'],
            'gst_district' => ['nullable', 'string', 'max:100'],
            'gst_state' => ['nullable', 'string', 'max:100'],
            'gst_state_code' => ['nullable', 'string', 'regex:/^\\d{2}$/'],
            'gst_pin_code' => ['nullable', 'string', 'regex:/^\\d{6}$/'],
            'gst_country' => ['nullable', 'string', 'max:100'],
            'gst_phone' => ['nullable', 'string', 'regex:/^\\d{10,15}$/'],
            'gst_email' => ['nullable', 'email', 'max:150'],
            'dispatch_from_name' => ['nullable', 'string', 'max:255'],
            'dispatch_from_address' => ['nullable', 'string', 'max:255'],
            'dispatch_from_city' => ['nullable', 'string', 'max:100'],
            'dispatch_from_state' => ['nullable', 'string', 'max:100'],
            'dispatch_from_state_code' => ['nullable', 'string', 'regex:/^\\d{2}$/'],
            'dispatch_from_pin_code' => ['nullable', 'string', 'regex:/^\\d{6}$/'],
        ]);

        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('app.settings.index')->with('success', 'Settings updated successfully.');
    }

    public function uploadInvoiceQr(Request $request)
    {
        $validated = $request->validate([
            'invoice_qr_image' => 'required|file|mimes:png,jpg,jpeg|max:5120',
        ]);

        $ext = strtolower($validated['invoice_qr_image']->getClientOriginalExtension() ?: 'png');
        $stored = $validated['invoice_qr_image']->storeAs('invoice', 'qr.' . $ext, 'public');
        Setting::set('invoice_qr_path', $stored);

        return redirect()->route('app.settings.index')->with('success', 'Invoice QR updated successfully.');
    }

    public function invoiceQrPreview()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $qrSettingPath = trim((string) ($settings['invoice_qr_path'] ?? ''));
        $candidate = $qrSettingPath !== '' ? storage_path('app/public/' . $qrSettingPath) : null;
        $fallback = public_path('images/qr.png');
        $file = ($candidate && file_exists($candidate)) ? $candidate : (file_exists($fallback) ? $fallback : null);

        if (!$file) {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
