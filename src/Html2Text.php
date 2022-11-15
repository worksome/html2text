<?php

declare(strict_types=1);

namespace Worksome\Html2Text;

use DOMDocument;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;

class Html2Text
{
    public static function convert(string $html, Config $config = new Config()): string
    {
        $isOfficeDocument = static::isOfficeDocument($html);

        if ($isOfficeDocument) {
            // remove office namespace
            $html = str_replace(["<o:p>", "</o:p>"], '', $html);
        }

        $html = static::fixNewlines($html);
        if (mb_detect_encoding($html, "UTF-8", true)) {
            $html = mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8");
        }

        $doc = static::getDocument($html);

        $output = static::iterateOverNode($doc, null, false, $isOfficeDocument, $config);

        // process output for whitespace/newlines
        return static::processWhitespaceNewlines($output);
    }

    /**
     * Unify newlines; in particular, \r\n becomes \n, and
     * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
     * all become \ns.
     */
    public static function fixNewlines(string $text): string
    {
        // Replace \r\n to \n
        $text = str_replace("\r\n", "\n", $text);

        // Remove \rs
        return str_replace("\r", "\n", $text);
    }

    /** @return array<string> */
    public static function nbspCodes(): array
    {
        return [
            "\xc2\xa0",
            "\u00a0",
        ];
    }

    /** @return array<string> */
    public static function zwnjCodes(): array
    {
        return [
            "\xe2\x80\x8c",
            "\u200c",
        ];
    }

    public static function processWhitespaceNewlines(string $text): string
    {
        // remove excess spaces around tabs
        $text = (string) preg_replace("/ *\t */im", "\t", $text);

        // remove leading whitespace
        $text = ltrim($text);

        // remove leading spaces on each line
        $text = (string) preg_replace("/\n[ \t]*/im", "\n", $text);

        // Convert non-breaking spaces to regular spaces to prevent output issues,
        // do it here, so they do NOT get removed with other leading spaces, as they
        // are sometimes used for indentation
        $text = static::renderText($text);

        // Remove trailing whitespace
        $text = rtrim($text);

        // Remove trailing spaces on each line
        $text = (string) preg_replace("/[ \t]*\n/im", "\n", $text);

        // Unarmor pre blocks
        $text = static::fixNewLines($text);

        // Remove unnecessary empty lines
        return (string) preg_replace("/\n\n\n*/im", "\n\n", $text);
    }

    public static function getDocument(string $html): DOMDocument
    {
        $doc = new DOMDocument();

        $html = trim($html);

        if (! $html) {
            // DOMDocument doesn't support empty value and throws an error
            // Return empty document instead
            return $doc;
        }

        if ($html[0] !== '<') {
            // If HTML does not begin with a tag, we put a body tag around it.
            // If we do not do this, PHP will insert a paragraph tag around
            // the first block of text for some reason which can mess up
            // the newlines. See pre.html test for an example.
            $html = '<body>' . $html . '</body>';
        }

        $html = (string) preg_replace('/&(?![a-z]+?;)/mi', '&amp;', $html);

        $load_result = $doc->loadHTML($html);

        if (! $load_result) {
            throw new Html2TextException('Could not load HTML - badly formed?', $html);
        }

        return $doc;
    }

    public static function isOfficeDocument(string $html): bool
    {
        return str_contains($html, "urn:schemas-microsoft-com:office");
    }

    /**
     * Replace any special characters with simple text versions, to prevent output issues:
     * - Convert non-breaking spaces to regular spaces; and
     * - Convert zero-width non-joiners to '' (nothing).
     *
     * This is to match our goal of rendering documents as they would be rendered
     * by a browser.
     */
    public static function renderText(string $text): string
    {
        $text = str_replace(static::nbspCodes(), ' ', $text);
        return str_replace(static::zwnjCodes(), '', $text);
    }

    public static function isWhitespace(string $text): bool
    {
        return strlen(trim(static::renderText($text), "\n\r\t ")) === 0;
    }

    public static function nextChildName(DOMNode $node): ?string
    {
        // get the next child
        $nextNode = $node->nextSibling;
        while ($nextNode != null) {
            if ($nextNode instanceof DOMText) {
                if (! static::isWhitespace($nextNode->wholeText)) {
                    break;
                }
            }

            if ($nextNode instanceof DOMElement) {
                break;
            }

            $nextNode = $nextNode->nextSibling;
        }

        $nextName = null;
        if ($nextNode instanceof DOMElement || $nextNode instanceof DOMText) {
            $nextName = strtolower($nextNode->nodeName);
        }

        return $nextName;
    }

    public static function iterateOverNode(
        DOMNode $node,
        string|null $prevName,
        bool $inPre,
        bool $isOfficeDocument,
        Config $config,
    ): string {
        if ($node instanceof DOMText) {
            // Replace whitespace characters with a space (equivalent to \s)
            if ($inPre) {
                $text = "\n" . trim(static::renderText($node->wholeText), "\n\r\t ") . "\n";

                // Remove trailing whitespace only
                $text = (string) preg_replace("/[ \t]*\n/im", "\n", $text);

                // armor newlines with \r.
                return str_replace("\n", "\r", $text);
            } else {
                $text = static::renderText($node->wholeText);
                $text = (string) preg_replace("/[\\t\\n\\f\\r ]+/im", " ", $text);

                if (! static::isWhitespace($text) && ($prevName == 'p' || $prevName == 'div')) {
                    return "\n" . $text;
                }

                return $text;
            }
        }

        if ($node instanceof DOMDocumentType || $node instanceof DOMProcessingInstruction) {
            return '';
        }

        /** @var DOMElement $node */
        if ($node->attributes?->getNamedItem('data-hidden-plaintext') !== null) {
            return '';
        }

        $name = strtolower($node->nodeName);
        $nextName = static::nextChildName($node);

        // start whitespace
        switch ($name) {
            case "hr":
                $prefix = '';
                if ($prevName != null) {
                    $prefix = "\n";
                }
                return $prefix . "---------------------------------------------------------------\n";

            case "style":
            case "head":
            case "title":
            case "meta":
            case "script":
                // ignore these tags
                return "";

            case "h1":
            case "h2":
            case "h3":
            case "h4":
            case "h5":
            case "h6":
            case "ol":
            case "ul":
            case "pre":
                // add two newlines
                $output = "\n\n";
                break;

            case "td":
            case "th":
                // add tab char to separate table fields
                $output = "\t";
                break;

            case "p":
                // Microsoft exchange emails often include HTML which, when passed through
                // html2text, results in lots of double line returns everywhere.
                //
                // To fix this, for any p element with a className of `MsoNormal` (the standard
                // classname in any Microsoft export or outlook for a paragraph that behaves
                // like a line return) we skip the first line returns and set the name to br.
                if ($isOfficeDocument && $node->getAttribute('class') == 'MsoNormal') {
                    $output = "";
                    $name = 'br';
                    break;
                }

                // add two lines
                $output = "\n\n";
                break;

            case "tr":
                // add one line
                $output = "\n";
                break;

            case "div":
                $output = "";
                if ($prevName !== null) {
                    // add one line
                    $output .= "\n";
                }
                break;

            case "li":
                $output = "- ";
                break;

            default:
                // print out contents of unknown tags
                $output = "";
                break;
        }

        if (isset($node->childNodes)) {
            $n = $node->childNodes->item(0);
            $previousSiblingNames = [];
            $previousSiblingName = null;

            $parts = [];
            $trailing_whitespace = 0;

            while ($n != null) {
                $text = static::iterateOverNode(
                    $n,
                    $previousSiblingName,
                    $inPre || $name == 'pre',
                    $isOfficeDocument,
                    $config
                );

                // Pass current node name to next child, as previousSibling does not appear to get populated
                if ($n instanceof DOMDocumentType
                    || $n instanceof DOMProcessingInstruction
                    || ($n instanceof DOMText && static::isWhitespace($text))) {
                    // Keep current previousSiblingName, these are invisible
                    $trailing_whitespace++;
                } else {
                    $previousSiblingName = strtolower($n->nodeName);
                    $previousSiblingNames[] = $previousSiblingName;
                    $trailing_whitespace = 0;
                }

                $node->removeChild($n);
                $n = $node->childNodes->item(0);

                $parts[] = $text;
            }

            // Remove trailing whitespace, important for the br check below
            while ($trailing_whitespace-- > 0) {
                array_pop($parts);
            }

            // suppress last br tag inside a node list if follows text
            $last_name = array_pop($previousSiblingNames);
            if ($last_name === 'br') {
                $last_name = array_pop($previousSiblingNames);
                if ($last_name === '#text') {
                    array_pop($parts);
                }
            }

            $output .= implode('', $parts);
        }

        // end whitespace
        switch ($name) {
            case "h1":
            case "h2":
            case "h3":
            case "h4":
            case "h5":
            case "h6":
            case "pre":
            case "p":
                // add two lines
                $output .= "\n\n";
                break;

            case "br":
                // add one line
                $output .= "\n";
                break;

            case "div":
                break;

            case "a":
                // links are returned in [text](link) format
                $href = $node->getAttribute("href");

                $output = trim($output);

                // remove double [[ ]] s from linking images
                if (str_starts_with($output, "[") && str_ends_with($output, "]")) {
                    $output = substr($output, 1, strlen($output) - 2);

                    // for linking images, the title of the <a> overrides the title of the <img>
                    if ($node->getAttribute("title")) {
                        $output = $node->getAttribute("title");
                    }
                }

                // if there is no link text, but a title attr
                if (! $output && $node->getAttribute("title")) {
                    $output = $node->getAttribute("title");
                }

                if ($href == null) {
                    // it doesn't link anywhere
                    if ($node->getAttribute('name') != null) {
                        $output = $config->dropLinks ? "$output" : "[$output]";
                    }
                } else {
                    if (
                        $href == $output
                        || $href == "mailto:{$output}"
                        || $href == "http://{$output}"
                        || $href == "https://{$output}"
                    ) {
                        // link to the same address: just use link
                        $output = "$output";
                    } else {
                        // replace it
                        if ($output) {
                            $output = $config->dropLinks ? "{$output}" : "[$output]($href)";
                        } else {
                            // empty string
                            $output = "$href";
                        }
                    }
                }

                // does the next node require additional whitespace?
                switch ($nextName) {
                    case "h1":
                    case "h2":
                    case "h3":
                    case "h4":
                    case "h5":
                    case "h6":
                        $output .= "\n";
                        break;
                }
                break;

            case "img":
                if ($node->getAttribute("title")) {
                    $output = "[" . $node->getAttribute("title") . "]";
                } elseif ($node->getAttribute("alt")) {
                    $output = "[" . $node->getAttribute("alt") . "]";
                } else {
                    $output = "";
                }
                break;

            case "li":
                $output .= "\n";
                break;

            case "blockquote":
                // process quoted text for whitespace/newlines
                $output = static::processWhitespaceNewlines($output);

                // add leading newline
                $output = "\n" . $output;

                // prepend '> ' at the beginning of all lines
                $output = (string) preg_replace("/\n/im", "\n> ", $output);

                // replace leading '> >' with '>>'
                $output = (string) preg_replace("/\n> >/im", "\n>>", $output);

                // add another leading newline and trailing newlines
                $output = "\n" . $output . "\n\n";
                break;
            default:
                // do nothing
        }

        return $output;
    }
}
