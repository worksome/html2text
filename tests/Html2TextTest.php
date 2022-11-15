<?php

declare(strict_types=1);

use Worksome\Html2Text\Config;
use Worksome\Html2Text\Html2Text;

it('parses', function (string $testFile, Config $config = new Config()) {
    expect(file_exists(__DIR__ . "/fixtures/{$testFile}.html"))->toBeTrue()
        ->and(file_exists(__DIR__ . "/fixtures/{$testFile}.txt"))->toBeTrue();

    $input = file_get_contents(__DIR__ . "/fixtures/{$testFile}.html");
    $expected = Html2Text::fixNewlines(file_get_contents(__DIR__ . "/fixtures/{$testFile}.txt"));

    $output = Html2Text::convert($input, $config);

    expect(trim($output))->toBe(trim($expected));
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
    'Windows-1252' => 'windows-1252-example',
    'DOM Processing' => 'dom-processing',
    'Empty HTML' => 'empty',
    'MS Office' => 'msoffice',
    'MS Office (Huge)' => 'huge-msoffice',
    'Zero-width non-joiners (ZWNJ tag)' => 'zero-width-non-joiners',
]);

it('parses with drop links', function (string $testFile, Config $config = new Config(dropLinks: true)) {
    expect(file_exists(__DIR__ . "/fixtures/{$testFile}.html"))->toBeTrue()
        ->and(file_exists(__DIR__ . "/fixtures/{$testFile}.no-links.txt"))->toBeTrue();

    $input = file_get_contents(__DIR__ . "/fixtures/{$testFile}.html");
    $expected = Html2Text::fixNewlines(file_get_contents(__DIR__ . "/fixtures/{$testFile}.no-links.txt"));

    $output = Html2Text::convert($input, $config);

    expect($output)->toBe($expected);
})->with([
    'Basic' => 'basic',
    'Anchor tags' => 'anchors',
]);
