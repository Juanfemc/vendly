<?php

namespace App\Support;

class ProductText
{
    public static function normalize(?string $value): string
    {
        $text = (string) $value;

        if (trim($text) === '') {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/&(?:amp;)?t?nbsp;?/i', ' ', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n[ \t]+/u', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function plain(?string $value): string
    {
        $text = self::htmlToReadableText($value);

        return self::normalize(strip_tags($text));
    }

    public static function rich(?string $value): string
    {
        return self::plain($value);
    }

    public static function featureLines(?string $value): string
    {
        return self::plain($value);
    }

    private static function htmlToReadableText(?string $value): string
    {
        $text = self::removeDangerousBlocks(self::normalize($value));
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li\b[^>]*>/i', "\n- ", $text) ?? $text;
        $text = preg_replace('/<\/\s*li\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*\/?\s*(p|div|section|article|blockquote|h[1-6]|tr|table|tbody|thead|tfoot)\b[^>]*>/i', "\n", $text) ?? $text;

        return $text;
    }

    private static function removeDangerousBlocks(string $text): string
    {
        return preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $text) ?? $text;
    }
}
