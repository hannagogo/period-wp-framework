<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Infrastructure\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Infrastructure\WordPress\MetaBox;

final class MetaBoxTest extends TestCase
{
    public function testRegisterDoesNotFailWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testSaveDoesNotFailWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A'],
            ],
        ]);

        $this->assertNull($metaBox->save(123));
    }

    public function testFieldsReturnsConfiguredFields(): void
    {
        $fields = [
            ['name' => 'field_a', 'label' => 'Field A'],
            ['name' => 'field_b', 'label' => 'Field B'],
        ];

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => $fields,
        ]);

        $this->assertSame($fields, $metaBox->fields());
    }

    public function testIdReturnsConfiguredId(): void
    {
        $metaBox = new MetaBox([
            'id' => 'custom_box',
            'title' => 'Custom Box',
            'post_type' => 'post',
        ]);

        $this->assertSame('custom_box', $metaBox->id());
    }

    public function testMissingConfigIsSafe(): void
    {
        $metaBox = new MetaBox([]);

        $this->assertSame('', $metaBox->id());
        $this->assertSame([], $metaBox->fields());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A', 'type' => 'text'],
                ['name' => 'field_b', 'label' => 'Field B', 'type' => 'checkbox'],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('name="field_a"', $output);
        $this->assertStringContainsString('name="field_b"', $output);
    }

    public function testRegisterDoesNotFailWithoutWordPressForImageField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'image_box',
            'title' => 'Image Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRegisterDoesNotFailWithoutWordPressForMediaField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRegisterDoesNotFailWithoutWordPressForGalleryField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPressForGalleryField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('data-period-wp-gallery', $output);
        $this->assertStringContainsString('name="gallery_ids"', $output);
    }

    public function testGallerySanitizeJsonArrayReturnsNumericArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '[1,2,3]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([1, 2, 3], $method->invoke($metaBox, $galleryField));
    }

    public function testGallerySanitizeInvalidJsonReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '[1,2,';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([], $method->invoke($metaBox, $galleryField));
    }

    public function testGallerySanitizeEmptyStringReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([], $method->invoke($metaBox, $galleryField));
    }

    public function testRegisterDoesNotFailWithoutWordPressForRepeaterField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPressForRepeaterField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('data-period-wp-repeater', $output);
        $this->assertStringContainsString('name="repeater_entries"', $output);
    }

    public function testRepeaterSanitizeJsonArrayReturnsArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A"},{"title":"B"}]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([['title' => 'A'], ['title' => 'B']], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterSanitizeInvalidJsonReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A"}';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterSanitizeEmptyStringReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterChildFieldSanitizeValues(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                    ['name' => 'enabled', 'type' => 'checkbox'],
                    ['name' => 'image_id', 'type' => 'image'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A","enabled":"1","image_id":"123"}]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
            ['name' => 'enabled', 'type' => 'checkbox'],
            ['name' => 'image_id', 'type' => 'image'],
        ]];

        $this->assertSame([['title' => 'A', 'enabled' => '1', 'image_id' => '123']], $method->invoke($metaBox, $repeaterField));
    }

    public function testImageAndMediaSanitizeNumericValues(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        $_POST['image_id'] = '123';
        $_POST['media_id'] = '456';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $imageField = ['name' => 'image_id', 'type' => 'image'];
        $mediaField = ['name' => 'media_id', 'type' => 'media'];

        $this->assertSame('123', $method->invoke($metaBox, $imageField));
        $this->assertSame('456', $method->invoke($metaBox, $mediaField));
    }

    public function testImageAndMediaSanitizeEmptyValueReturnsEmptyString(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        unset($_POST['image_id'], $_POST['media_id']);

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $imageField = ['name' => 'image_id', 'type' => 'image'];
        $mediaField = ['name' => 'media_id', 'type' => 'media'];

        $this->assertSame('', $method->invoke($metaBox, $imageField));
        $this->assertSame('', $method->invoke($metaBox, $mediaField));
    }
}
