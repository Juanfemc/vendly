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
        $text = preg_replace('/\n[ \t]+/u', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function plain(?string $value): string
    {
        $text = self::normalize($value);
        $text = self::removeDangerousBlocks($text);
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li\b[^>]*>/i', '- ', $text) ?? $text;
        $text = preg_replace('/<\/\s*(li|p|div|section|article|blockquote|h[1-6]|tr)\s*>/i', "\n", $text) ?? $text;

        return self::normalize(strip_tags($text));
    }

    public static function rich(?string $value): string
    {
        $text = self::removeDangerousBlocks(self::normalize($value));

        if ($text === '') {
            return '';
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4>';

        return preg_replace('/<([a-z0-9]+)(?:\s[^>]*)?>/i', '<$1>', strip_tags($text, $allowedTags)) ?? '';
    }

    public static function featureLines(?string $value): string
    {
        return self::plain($value);
    }

    private static function removeDangerousBlocks(string $text): string
    {
        return preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $text) ?? $text;
    }
}
