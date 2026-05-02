<?php
/**
 * @var string $label
 * @var string $url
 * @var string $class
 */

if (!defined('ABSPATH')) {
    exit;
}

if ($url === '') : ?>
<span class="<?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
<?php else : ?>
<a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
<?php endif; ?>
