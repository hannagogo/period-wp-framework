<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class HtmlTemplate
{
    private string $template;

    public function __construct(string $template)
    {
        $this->template = $template;
    }

    public function render(array $data = []): string
    {
        return (string) preg_replace_callback(
            '/\{\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}\}|\{\{\s*(?:(attr|url|html):)?\s*([A-Za-z0-9_.-]+)\s*\}\}/',
            function (array $matches) use ($data): string {
                if (!empty($matches[1])) {
                    return $this->renderValue($matches[1], $data, 'raw');
                }

                $filter = $matches[2] ?? '';
                $key = $matches[3] ?? '';

                if ($filter === 'attr') {
                    return $this->renderValue($key, $data, 'attr');
                }

                if ($filter === 'url') {
                    return $this->renderValue($key, $data, 'url');
                }

                if ($filter === 'html') {
                    return $this->renderValue($key, $data, 'html');
                }

                return $this->renderValue($key, $data, 'html_safe');
            },
            $this->template
        ) ?? '';
    }

    private function renderValue(string $key, array $data, string $mode): string
    {
        $value = $this->resolveKey($key, $data);

        if (!is_scalar($value)) {
            return '';
        }

        $value = (string) $value;

        return match ($mode) {
            'attr' => self::escAttr($value),
            'url' => self::escUrl($value),
            'html' => self::wpKsesPost($value),
            'raw' => $value,
            default => self::escHtml($value),
        };
    }

    private function resolveKey(string $key, array $data): mixed
    {
        $parts = explode('.', $key);
        $value = $data;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return '';
            }

            $value = $value[$part];
        }

        return $value;
    }

    private static function escHtml(string $value): string
    {
        if (function_exists('esc_html')) {
            return esc_html($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escAttr(string $value): string
    {
        if (function_exists('esc_attr')) {
            return esc_attr($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escUrl(string $value): string
    {
        if (function_exists('esc_url')) {
            return esc_url($value);
        }

        return filter_var($value, FILTER_SANITIZE_URL) ?? '';
    }

    private static function wpKsesPost(string $value): string
    {
        if (function_exists('wp_kses_post')) {
            return wp_kses_post($value);
        }

        return $value;
    }
}
