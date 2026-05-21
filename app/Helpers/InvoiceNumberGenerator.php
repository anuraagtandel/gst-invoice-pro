<?php

namespace App\Helpers;

use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;

class InvoiceNumberGenerator
{
    /**
     * Get the next formatted invoice number
     * e.g., SDP/26-27/0001
     */
    public static function next($invoiceDate = null): string
    {
        $prefix = Setting::get('invoice_prefix', 'SDP');
        $year = self::yearRange($invoiceDate);
        $seq = self::nextSeqFor($prefix, $year);

        $paddedSeq = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        return "{$prefix}/{$year}/{$paddedSeq}";
    }

    /**
     * Get the next sequence integer
     */
    public static function nextSeq(): int
    {
        return (int) Setting::get('next_invoice_seq', 1);
    }

    private static function yearRange($invoiceDate = null): string
    {
        $dt = $invoiceDate
            ? ($invoiceDate instanceof Carbon ? $invoiceDate : Carbon::parse($invoiceDate))
            : now();

        $y1 = (int) $dt->format('y');
        $y2 = ($y1 + 1) % 100;

        return sprintf('%02d-%02d', $y1, $y2);
    }

    private static function nextSeqFor(string $prefix, string $year): int
    {
        $like = "{$prefix}/{$year}/%";

        $lastInvoiceNo = Invoice::query()
            ->where('invoice_no', 'like', $like)
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        if (! $lastInvoiceNo) {
            return 1;
        }

        $parts = explode('/', $lastInvoiceNo);
        $lastPart = end($parts);
        $lastSeq = (int) ltrim((string) $lastPart, '0');

        return $lastSeq + 1;
    }

    /**
     * Increment the invoice sequence in the database
     */
    public static function increment($invoiceDate = null): void
    {
        $prefix = Setting::get('invoice_prefix', 'SDP');
        $year = self::yearRange($invoiceDate);
        $nextSeq = self::nextSeqFor($prefix, $year);

        Setting::set('invoice_prefix', $prefix);
        Setting::set('financial_year', $year);
        Setting::set('next_invoice_seq', $nextSeq + 1);
    }
}
