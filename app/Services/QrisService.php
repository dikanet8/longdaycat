<?php

namespace App\Services;

class QrisService
{
    /**
     * Generate dynamic QRIS with specific amount from a static QRIS payload
     */
    public static function generateDynamicQris(string $staticQris, int $amount): string
    {
        if (empty($staticQris)) {
            return '';
        }

        // Remove whitespace just in case
        $staticQris = trim($staticQris);

        // Find the CRC tag (6304) which is always at the end
        $pos = strrpos($staticQris, '6304');
        if ($pos !== false) {
            $baseString = substr($staticQris, 0, $pos);
        } else {
            // fallback if someone just passes without CRC
            $baseString = $staticQris;
        }

        // We should check if tag 54 (Amount) already exists. In a standard static QRIS, it doesn't.
        // Assuming no tag 54 exists, we append it.
        $amountStr = (string)$amount;
        $amountLen = str_pad((string)strlen($amountStr), 2, '0', STR_PAD_LEFT);
        $tag54 = "54" . $amountLen . $amountStr;

        // Note: EMVCo spec says tag 58 (Country) or 53 (Currency) might be before it, 
        // but order of tags 00-61 doesn't strictly matter for most payment apps as long as they exist before 62/63.
        
        $dynamicString = $baseString . $tag54 . "6304";
        
        // Calculate new CRC
        $crc = self::generateCrc16Ccitt($dynamicString);
        
        return $dynamicString . $crc;
    }

    /**
     * Calculate CRC16 CCITT for EMVCo
     */
    private static function generateCrc16Ccitt(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ ($x)) & 0xFFFF;
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
