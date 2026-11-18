<?php

declare(strict_types=1);

use Worksome\Html2Text\Config;
use Worksome\Html2Text\Html2Text;
use Worksome\Html2Text\Html2TextException;

it('parses', function (string $testFile, Config $config = new Config()) {
    expect(file_exists(__DIR__ . "/fixtures/{$testFile}.html"))->toBeTrue();

    $input = file_get_contents(__DIR__ . "/fixtures/{$testFile}.html");

    $output = Html2Text::convert($input, $config);

    expect($output)->toMatchSnapshot();
})->with([
    'Basic' => 'basic',
    'Anchor tags' => 'anchors',
    'More anchor tags' => 'more-anchors',
    'Break tag' => 'br',
    'Ampersand (&)' => 'ampersand',
    'Break tags' => 'brs',
    'Tables' => 'table',
    'Non-breaking spaces (NBSP tag)' => 'nbsp',
    'Lists' => 'lists',
    'Pre tags' => 'pre',
    'New lines' => 'newlines',
    'Nested Divs' => 'nested-divs',
    'Blockquotes' => 'blockquotes',
    'Full email' => 'full-email',
    'Images' => 'images',
    'Non-breaking spaces' => 'non-breaking-spaces',
    'UTF-8' => 'utf8-example',
    'DOM Processing' => 'dom-processing',
    'Empty HTML' => 'empty',
    'MS Office' => 'msoffice',
    'MS Office (Huge)' => 'huge-msoffice',
    'Zero-width non-joiners (ZWNJ tag)' => 'zero-width-non-joiners',
]);

it('parses with drop links', function (string $testFile, Config $config = new Config(dropLinks: true)) {
    expect(file_exists(__DIR__ . "/fixtures/{$testFile}.html"))->toBeTrue();

    $input = file_get_contents(__DIR__ . "/fixtures/{$testFile}.html");

    $output = Html2Text::convert($input, $config);

    expect($output)->toMatchSnapshot();
})->with([
    'Basic' => 'basic',
    'Anchor tags' => 'anchors',
]);

it('parses with "windows-1252" character set', function () {
    expect(file_exists(__DIR__ . '/fixtures/windows-1252-example.html'))->toBeTrue();

    $input = file_get_contents(__DIR__ . '/fixtures/windows-1252-example.html');

    $config = new Config(characterSet: 'windows-1252');

    $output = Html2Text::convert($input, $config);

    expect($output)->toMatchSnapshot();
});

it('parses with a character set declared in a `meta` tag', function (string $meta) {
    $input = "<html><head>{$meta}</head><body><p>caf\xe9</p></body></html>";

    $output = Html2Text::convert($input, new Config(characterSet: 'windows-1252'));

    expect($output)->toBe('café');
})->with([
    'charset' => '<meta charset="windows-1252">',
    'http-equiv' => '<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">',
]);

it('updates the character set declared in `meta` tags when converting to UTF-8', function () {
    $convert = new ReflectionMethod(Html2Text::class, 'convertToUtf8');

    $output = $convert->invoke(
        null,
        "<meta charset='windows-1252'><meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1252\"><p>caf\xe9</p>",
        'windows-1252',
    );

    expect($output)->toBe(
        "<meta charset='UTF-8'><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><p>café</p>",
    );
});

it('parses with a character set that mbstring does not support', function () {
    $output = Html2Text::convert("<p>Za\x9e\xf9</p>", new Config(characterSet: 'windows-1250'));

    expect($output)->toBe('Zažů');
});

it('throws an exception for an unknown character set', function () {
    Html2Text::convert('<p>Hello</p>', new Config(characterSet: 'not-a-character-set'));
})->throws(Html2TextException::class, 'Could not convert HTML from the [not-a-character-set] character set.');
