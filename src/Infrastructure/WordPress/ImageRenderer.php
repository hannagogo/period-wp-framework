<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

use Period\WpFramework\Support\ImageUtil;

final class ImageRenderer
{
    public function render(int $attachmentId, array $args = []): string
    {
        if (!function_exists('wp_get_attachment_image_src')) {
            return '';
        }

        $args = $this->normalizeArgs($args);
        $size = $args['size'];

        $srcData = wp_get_attachment_image_src($attachmentId, $size);
        if ($srcData === false || !is_array($srcData) || count($srcData) < 3) {
            return '';
        }

        [$src, $width, $height] = $srcData;
        $width = (int) $width;
        $height = (int) $height;

        $alt = $this->resolveAlt($attachmentId, $args['alt']);
        $orientation = ImageUtil::orientation($width, $height);
        $wrapperClasses = $this->buildWrapperClasses($args['wrapper_class'], $orientation, $args['class']);

        $imgHtml = $this->buildImageHtml($src, $width, $height, $alt, $args['lazy']);

        if ($args['wrapper']) {
            return sprintf('<div class="%s">%s</div>', htmlspecialchars($wrapperClasses, ENT_QUOTES, 'UTF-8'), $imgHtml);
        }

        return $imgHtml;
    }

    private function normalizeArgs(array $args): array
    {
        $size = $args['size'] ?? 'full';
        $class = $args['class'] ?? '';
        $wrapper = isset($args['wrapper']) ? (bool) $args['wrapper'] : true;
        $wrapperClass = $args['wrapper_class'] ?? 'image';
        $lazy = isset($args['lazy']) ? (bool) $args['lazy'] : true;
        $alt = array_key_exists('alt', $args) ? $args['alt'] : null;

        return [
            'size' => is_string($size) && $size !== '' ? $size : 'full',
            'class' => is_string($class) ? $class : '',
            'wrapper' => $wrapper,
            'wrapper_class' => is_string($wrapperClass) && $wrapperClass !== '' ? $wrapperClass : 'image',
            'lazy' => $lazy,
            'alt' => is_string($alt) ? $alt : null,
        ];
    }

    private function resolveAlt(int $attachmentId, ?string $explicitAlt): string
    {
        if (is_string($explicitAlt) && $explicitAlt !== '') {
            return $explicitAlt;
        }

        if (function_exists('get_post_meta')) {
            $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
            if (is_string($alt) && $alt !== '') {
                return $alt;
            }
        }

        return '';
    }

    private function buildWrapperClasses(string $wrapperClass, string $orientation, string $extraClass): string
    {
        $classes = [$wrapperClass, $wrapperClass . '--' . $orientation];

        if ($extraClass !== '') {
            $classes[] = $extraClass;
        }

        return implode(' ', $classes);
    }

    private function buildImageHtml(string $src, int $width, int $height, string $alt, bool $lazy): string
    {
        $srcAttr = $this->escapeUrl($src);
        $altAttr = $this->escapeAttr($alt);
        $loading = $lazy ? ' loading="lazy"' : '';

        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="%s"%s>',
            $srcAttr,
            $width,
            $height,
            $altAttr,
            $loading
        );
    }

    private function escapeAttr(string $value): string
    {
        return function_exists('esc_attr') ? esc_attr($value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeUrl(string $url): string
    {
        return function_exists('esc_url') ? esc_url($url) : $url;
    }
}
