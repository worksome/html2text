<?php

declare(strict_types=1);

use Worksome\Html2Text\Config;
use Worksome\Html2Text\Html2Text;

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
