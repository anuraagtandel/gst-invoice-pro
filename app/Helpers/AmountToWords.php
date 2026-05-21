<?php

namespace App\Helpers;

class AmountToWords
{
    public static function convert(float $amount): string
    {
        $number = floor($amount);
        $decimal = round($amount - $number, 2) * 100;

        $hundred = null;
        $digits_length = strlen((string) $number);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
        ];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $current_number = floor($number % $divider);
            $number = floor($number / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($current_number) {
                $plural = (($counter = count($str)) && $current_number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($current_number < 21) ? $words[$current_number].' '.$digits[$counter].$plural.' '.$hundred
                    : $words[floor($current_number / 10) * 10].' '.$words[$current_number % 10].' '.$digits[$counter].$plural.' '.$hundred;
            } else {
                $str[] = null;
            }
        }

        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? ' and Paise '.($words[$decimal / 10].' '.$words[$decimal % 10]) : '';

        $result = ($Rupees ? $Rupees.'Rupees ' : '').$paise.' Only';

        // Clean up string
        $result = str_replace('  ', ' ', $result);
        $result = trim($result);

        return strtoupper($result);
    }
}
