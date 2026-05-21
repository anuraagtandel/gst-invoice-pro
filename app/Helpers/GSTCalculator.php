<?php

namespace App\Helpers;

class GSTCalculator
{
    /**
     * Calculate taxes and totals for a single invoice item
     */
    public static function calcItem(array $item, string $tax_type, array $schemes = []): array
    {
        $base_price = (float) ($item['base_price'] ?? 0);
        $qty_ct = (float) ($item['qty_ct'] ?? 0);
        $qty_un = (float) ($item['qty_un'] ?? 0);
        $pack_size = (int) ($item['pack_size'] ?? 1);
        $discount_pct = (float) ($item['discount_pct'] ?? 0);
        $gst_rate = (float) ($item['gst_rate'] ?? 0);
        $cess_rate = 0;
        $is_free = (bool) ($item['is_free'] ?? false);
        $mrp = (float) ($item['mrp'] ?? 0);

        $purchased_units = ($qty_ct * $pack_size) + $qty_un;
        $free_units = 0;

        // Apply schemes
        if (! $is_free && ! empty($schemes)) {
            foreach ($schemes as $scheme) {
                foreach ($scheme['scheme_slabs'] as $slab) {
                    if ($qty_ct >= $slab['min_qty'] && (is_null($slab['max_qty']) || $qty_ct <= $slab['max_qty'])) {
                        $free_units = $qty_ct * $slab['free_qty'];
                        break 2; // Exit both loops once a slab is applied
                    }
                }
            }
        }

        $total_units = $purchased_units + $free_units;

        if ($is_free) {
            return [
                'total_units' => $total_units,
                'purchased_units' => $purchased_units,
                'free_units' => $free_units,
                'taxable' => 0,
                'cgst_rate' => 0,
                'cgst' => 0,
                'sgst_rate' => 0,
                'sgst' => 0,
                'cess_rate' => 0,
                'cess' => 0,
                'line_total' => 0,
                'mrp_value' => $mrp * $total_units,
            ];
        }

        $taxable = $base_price * $purchased_units * (1 - ($discount_pct / 100));

        $cgst_rate = 0;
        $sgst_rate = 0;
        $cgst = 0;
        $sgst = 0;

        if ($tax_type === 'IGST') {
            $cgst_rate = 0;
            $sgst_rate = $gst_rate; // Store IGST in SGST field as per requirement
            $sgst = $taxable * ($sgst_rate / 100);
        } else {
            // CGST_SGST or CGST_UTGST
            $cgst_rate = $gst_rate / 2;
            $sgst_rate = $gst_rate / 2;
            $cgst = $taxable * ($cgst_rate / 100);
            $sgst = $taxable * ($sgst_rate / 100);
        }

        $cess = 0;
        $line_total = $taxable + $cgst + $sgst;

        return [
            'total_units' => $total_units,
            'purchased_units' => $purchased_units,
            'free_units' => $free_units,
            'taxable' => $taxable,
            'cgst_rate' => $cgst_rate,
            'cgst' => $cgst,
            'sgst_rate' => $sgst_rate,
            'sgst' => $sgst,
            'cess_rate' => $cess_rate,
            'cess' => $cess,
            'line_total' => $line_total,
            'mrp_value' => $mrp * $total_units,
        ];
    }

    /**
     * Calculate totals for the entire invoice based on processed items
     */
    public static function calcInvoiceTotals(array $items): array
    {
        $subtotal_mrp = 0;
        $taxable_amount = 0;
        $total_cgst = 0;
        $total_sgst = 0;
        $total_cess = 0;
        $free_goods_value = 0;
        $total_line_amount = 0;

        foreach ($items as $item) {
            $mrp_val = $item['mrp_value'] ?? 0;
            $subtotal_mrp += $mrp_val;

            if ($item['taxable'] == 0 && ($item['total_units'] ?? 0) > 0) {
                // Free item
                $free_goods_value += $mrp_val;
            } else {
                $taxable_amount += $item['taxable'];
                $total_cgst += $item['cgst'];
                $total_sgst += $item['sgst'];
                $total_line_amount += $item['line_total'];
            }
        }

        $total_discount = $subtotal_mrp - $total_line_amount - $free_goods_value;

        $raw_total = $taxable_amount + $total_cgst + $total_sgst;
        $grand_total = round($raw_total);
        $round_off = $grand_total - $raw_total;

        return [
            'subtotal_mrp' => $subtotal_mrp,
            'total_discount' => $total_discount,
            'taxable_amount' => $taxable_amount,
            'total_cgst' => $total_cgst,
            'total_sgst' => $total_sgst,
            'total_cess' => 0,
            'free_goods_value' => $free_goods_value,
            'round_off' => $round_off,
            'grand_total' => $grand_total,
        ];
    }
}
