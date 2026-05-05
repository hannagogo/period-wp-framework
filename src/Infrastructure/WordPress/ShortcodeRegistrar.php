<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

final class ShortcodeRegistrar
{
    public function register(): void
    {
        if (!function_exists('add_shortcode')) {
            return;
        }

        add_shortcode('document', [$this, 'renderDocument']);
        add_shortcode('title', [$this, 'renderTitle']);
        add_shortcode('site_name', [$this, 'renderSiteName']);
    }

    public function renderDocument(array|string $atts = [], ?string $content = null): string
    {
        $renderer = new DocumentRenderer();
        return $renderer->render($content ?? '');
    }

    public function renderTitle(array|string $atts = []): string
    {
        $resolver = new TitleResolver(new SiteInfo());
        return $resolver->siteTitle();
    }

    public function renderSiteName(array|string $atts = []): string
    {
        return (new SiteInfo())->name();
    }
}
