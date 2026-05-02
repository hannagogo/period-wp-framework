<?php

declare(strict_types=1);

namespace Period\WpFramework;

use Period\WpFramework\Infrastructure\ShortcodeRegistrar;
use Period\WpFramework\Support\ArgsResolver;

final class Application
{
    private string $basePath;
    private ArgsResolver $argsResolver;
    private bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->argsResolver = new ArgsResolver();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        (new ShortcodeRegistrar($this))->register();

        $this->booted = true;
    }

    public function button(array $args = []): string
    {
        $args = $this->argsResolver->resolve($args, [
            'label' => 'Button',
            'url' => '',
            'class' => '',
        ]);

        $label = esc_html((string) $args['label']);
        $url = esc_url((string) $args['url']);
        $class = esc_attr(trim('period-wp-button ' . (string) $args['class']));

        if ($url === '') {
            return sprintf('<span class="%s">%s</span>', $class, $label);
        }

        return sprintf('<a class="%s" href="%s">%s</a>', $class, $url, $label);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }
}
