<?php

if (class_exists('ParsedownExtra')) {
    class DynamicParent extends \ParsedownExtra
    {
        public function __construct()
        {
            parent::__construct();
        }
    }
} else {
    class DynamicParent extends \Parsedown
    {
        public function __construct()
        {
        }
    }
}

class ParsedownExtended extends DynamicParent
{
    public const ID_ATTRIBUTE_DEFAULT = 'toc';
    protected $tagToc = '[toc]';

    protected $contentsListArray = [];
    protected $contentsListString = '';
    protected $firstHeadLevel = 0;

    protected $isBlacklistInitialized = false;
    protected $anchorDuplicates = [];

    protected $specialCharacters = [
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '?', '"', "'", '<',
    ];

    /**
     * Version requirement check.
     */
    public function __construct(array $params = null)
    {
        parent::__construct();

        if (!empty($params)) {
            $this->options = $params;
        }

        /*
         * Inline
         * ------------------------------------------------------------------------.
         */

        $this->options['toc'] = $this->options['toc'] ?? false;

        // Marks
        $this->InlineTypes['='][] = 'mark';
        $this->inlineMarkerList .= '=';

        // Keystrokes
        $this->InlineTypes['['][] = 'Keystrokes';
        $this->inlineMarkerList .= '[';

        // Inline Math
        $this->InlineTypes['\\'][] = 'Math';
        $this->inlineMarkerList .= '\\';
        $this->InlineTypes['$'][] = 'Math';
        $this->inlineMarkerList .= '$';

        // Superscript
        $this->InlineTypes['^'][] = 'Superscript';
        $this->inlineMarkerList .= '^';

        // Subscript
        $this->InlineTypes['~'][] = 'Subscript';

        // Emojis
        $this->InlineTypes[':'][] = 'Emojis';
        $this->inlineMarkerList .= ':';

        // Typographer
        $state = $this->options['typographer'] ?? false;
        if ($state !== false) {
            $this->InlineTypes['('][] = 'Typographer';
            $this->inlineMarkerList .= '(';
            $this->InlineTypes['.'][] = 'Typographer';
            $this->inlineMarkerList .= '.';
            $this->InlineTypes['+'][] = 'Typographer';
            $this->inlineMarkerList .= '+';
            $this->InlineTypes['!'][] = 'Typographer';
            $this->inlineMarkerList .= '!';
            $this->InlineTypes['?'][] = 'Typographer';
            $this->inlineMarkerList .= '?';
        }

        // Smartypants
        $state = $this->options['smarty'] ?? false;
        if ($state !== false) {
            $this->InlineTypes['<'][] = 'Smartypants';
            $this->inlineMarkerList .= '<';
            $this->InlineTypes['>'][] = 'Smartypants';
            $this->inlineMarkerList .= '>';
            $this->InlineTypes['-'][] = 'Smartypants';
            $this->inlineMarkerList .= '-';
            $this->InlineTypes['.'][] = 'Smartypants';
            $this->inlineMarkerList .= '.';
            $this->InlineTypes["'"][] = 'Smartypants';
            $this->inlineMarkerList .= "'";
            $this->InlineTypes['"'][] = 'Smartypants';
            $this->inlineMarkerList .= '"';
            $this->InlineTypes['`'][] = 'Smartypants';
            $this->inlineMarkerList .= '`';
        }

        /*
         * Blocks
         * ------------------------------------------------------------------------
         */

        // Block Math
        $this->BlockTypes['\\'][] = 'Math';
        $this->BlockTypes['$'][] = 'Math';

        // Task
        $this->BlockTypes['['][] = 'Checkbox';

        // Custom YUSSEL

        // Alert
        array_unshift($this->BlockTypes[':'], 'Alert');
    }

    /**
     * Parses the given markdown string to an HTML string but it leaves the ToC
     * tag as is. It's an alias of the parent method "\DynamicParent::text()".
     */
    public function body($text): string
    {
        $text = $this->encodeTagToHash($text);  // Escapes ToC tag temporary
        $html = DynamicParent::text($text);     // Parses the markdown text

        return $this->decodeTagFromHash($html); // Unescape the ToC tag
    }

    /**
     * Parses markdown string to HTML and also the "[toc]" tag as well.
     * It overrides the parent method: \Parsedown::text().
     */
    public function text($text)
    {
        // Parses the markdown text except the ToC tag. This also searches
        // the list of contents and available to get from "contentsList()"
        // method.
        $html = $this->body($text);

        if (isset($this->options['toc']) && false == $this->options['toc']) {
            return $html;
        }

        $tagOrigin = $this->getTagToC();

        if (strpos($text, $tagOrigin) === false) {
            return $html;
        }

        $tocData = $this->contentsList();
        $tocId = $this->getIdAttributeToC();
        $needle = '<p>'.$tagOrigin.'</p>';
        $replace = "<div id=\"{$tocId}\">{$tocData}</div>";

        return str_replace($needle, $replace, $html);
    }

    /**
     * Returns the parsed ToC.
     *
     * @param string $typeReturn Type of the return format. "html" or "json".
     *
     * @return string HTML/JSON string of ToC
     */
    public function contentsList($typeReturn = 'html')
    {
        if ('html' === strtolower($typeReturn)) {
            $result = '';
            if (!empty($this->contentsListString)) {
                // Parses the ToC list in markdown to HTML
                $result = $this->body($this->contentsListString);
            }

            return $result;
        }

        if ('json' === strtolower($typeReturn)) {
            return json_encode($this->contentsListArray);
        }

        // Forces to return ToC as "html"
        error_log(
            'Unknown return type given while parsing ToC.'
            .' At: '.__FUNCTION__.'() '
            .' in Line:'.__LINE__.' (Using default type)'
        );

        return $this->contentsList('html');
    }

    protected function inlineText($text)
    {
        $Inline = [
            'extent' => strlen($text),
            'element' => [],
        ];

        $Inline['element']['elements'] = self::pregReplaceElements(
            $this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
            [
                ['name' => 'br'],
                ['text' => "\n"],
            ],
            $text
        );

        return DynamicParent::inlineText($text);
    }

    /**
     * ------------------------------------------------------------------------
     * Inline
     * ------------------------------------------------------------------------.
     */

    // inlineCode

    protected function inlineEmojis($excerpt)
    {
        include 'emojiMap.php';
        
        if (preg_match('/^(:)([^: ]*?)(:)/', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'text' => str_replace(array_keys($emojiMap), $emojiMap, $matches[0]),
                ],
            ];
        }
    }

    // Inline Marks

    protected function inlineMark($excerpt)
    {
        if (preg_match('/^(=){2,}([^=]*?)(=){2,}/', $excerpt['text'], $matches)) {
            $color = 1;
            while (isset($excerpt['text'][$color]) and $excerpt['text'][$color] === '=') {
                $color++;
            }
            if ($color > 6) {
                return;
            }
            --$color; // Hay 5 colores
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'mark',
                    'text' => $matches[2],
                    'attributes' => [
                        'class' => 'color-'. min(5, $color),
                    ],
                ],
            ];
        }
    }

    // Inline Keystrokes

    protected function inlineKeystrokes($excerpt)
    {
        if (preg_match('/^(?<!\[)(?:\[\[([^\[\]]*|[\[\]])\]\])(?!\])/s', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'kbd',
                    'text' => $matches[1],
                ],
            ];
        }
    }

    // Inline Superscript

    protected function inlineSuperscript($excerpt)
    {
        if (preg_match('/(?:\^(?!\^)([^\^ ]*)\^(?!\^))/', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'sup',
                    'text' => $matches[1],
                    'function' => 'lineElements',
                ],
            ];
        }
    }

    // Inline Subscript

    protected function inlineSubscript($excerpt)
    {
        if (preg_match('/(?:~(?!~)([^~ ]*)~(?!~))/', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'sub',
                    'text' => $matches[1],
                    'function' => 'lineElements',
                ],
            ];
        }
    }

    // Inline typographer

    protected function inlineTypographer($excerpt)
    {
        $substitutions = [
            '/\(c\)/i' => '&copy;',
            '/\(r\)/i' => '&reg;',
            '/\(tm\)/i' => '&trade;',
            '/\(p\)/i' => '&para;',
            '/\+-/i' => '&plusmn;',
            '/\.{4,}|\.{2}/i' => '...',
            '/\!\.{3,}/i' => '!..',
            '/\?\.{3,}/i' => '?..',
        ];

        if (preg_match('/\+-|\(p\)|\(tm\)|\(r\)|\(c\)|\.{2,}|\!\.{3,}|\?\.{3,}/i', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'rawHtml' => preg_replace(array_keys($substitutions), array_values($substitutions), $matches[0]),
                ],
            ];
        }
    }

    // Inline Smartypants

    protected function inlineSmartypants($excerpt)
    {
        // Substitutions
        $backtickDoublequoteOpen = $this->options['smarty']['substitutions']['left-double-quote'] ?? '&ldquo;';
        $backtickDoublequoteClose = $this->options['smarty']['substitutions']['right-double-quote'] ?? '&rdquo;';

        $smartDoublequoteOpen = $this->options['smarty']['substitutions']['left-double-quote'] ?? '&ldquo;';
        $smartDoublequoteClose = $this->options['smarty']['substitutions']['right-double-quote'] ?? '&rdquo;';
        $smartSinglequoteOpen = $this->options['smarty']['substitutions']['left-single-quote'] ?? '&lsquo;';
        $smartSinglequoteClose = $this->options['smarty']['substitutions']['right-single-quote'] ?? '&rsquo;';

        $leftAngleQuote = $this->options['smarty']['substitutions']['left-angle-quote'] ?? '&laquo;';
        $rightAngleQuote = $this->options['smarty']['substitutions']['right-angle-quote'] ?? '&raquo;';

        if (preg_match('/(``)(?!\s)([^"\'`]{1,})(\'\')|(\")(?!\s)([^\"]{1,})(\")|(\')(?!\s)([^\']{1,})(\')|(<{2})(?!\s)([^<>]{1,})(>{2})|(\.{3})|(-{3})|(-{2})/i', $excerpt['text'], $matches)) {
            $matches = array_values(array_filter($matches));

            // Smart backticks
            $smartBackticks = $this->options['smarty']['smart_backticks'] ?? false;

            if ($smartBackticks) {
                if ('``' === $matches[1]) {
                    $length = strlen(trim($excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($backtickDoublequoteOpen).$matches[2].html_entity_decode($backtickDoublequoteClose),
                        ],
                    ];
                }
            }

            // Smart quotes
            $smartQuotes = $this->options['smarty']['smart_quotes'] ?? true;

            if ($smartQuotes) {
                if ("'" === $matches[1]) {
                    $length = strlen(trim($excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($smartSinglequoteOpen).$matches[2].html_entity_decode($smartSinglequoteClose),
                        ],
                    ];
                }

                if ('"' === $matches[1]) {
                    $length = strlen(trim($excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($smartDoublequoteOpen).$matches[2].html_entity_decode($smartDoublequoteClose),
                        ],
                    ];
                }
            }

            // Smart angled quotes
            $smartAngledQuotes = $this->options['smarty']['smart_angled_quotes'] ?? true;

            if ($smartAngledQuotes) {
                if ('<<' === $matches[1]) {
                    $length = strlen(trim($excerpt['before']));
                    if ($length > 0) {
                        return;
                    }

                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'text' => html_entity_decode($leftAngleQuote).$matches[2].html_entity_decode($rightAngleQuote),
                        ],
                    ];
                }
            }

            // Smart dashes
            $smartDashes = $this->options['smarty']['smart_dashes'] ?? true;

            if ($smartDashes) {
                if ('---' === $matches[1]) {
                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'rawHtml' => $this->options['smarty']['substitutions']['mdash'] ?? '&mdash;',
                        ],
                    ];
                }

                if ('--' === $matches[1]) {
                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'rawHtml' => $this->options['smarty']['substitutions']['ndash'] ?? '&ndash;',
                        ],
                    ];
                }
            }

            // Smart ellipses
            $smartEllipses = $this->options['smarty']['smart_ellipses'] ?? true;

            if ($smartEllipses) {
                if ('...' === $matches[1]) {
                    return [
                        'extent' => strlen($matches[0]),
                        'element' => [
                            'rawHtml' => $this->options['smarty']['substitutions']['ellipses'] ?? '&hellip;',
                        ],
                    ];
                }
            }
        }
    }

    // Inline Math

    protected function inlineMath($excerpt)
    {
        $matchSingleDollar = $this->options['math']['single_dollar'] ?? false;
        // Inline Matches
        if ($matchSingleDollar) {
            // Match single dollar - experimental
            if (preg_match('/^(?<!\\\\)((?<!\$)\$(?!\$)(.*?)(?<!\$)\$(?!\$)|(?<!\\\\\()\\\\\((.*?)(?<!\\\\\()\\\\\)(?!\\\\\)))/s', $excerpt['text'], $matches)) {
                $mathMatch = $matches[0];
            }
        } else {
            if (preg_match('/^(?<!\\\\\()\\\\\((.*?)(?<!\\\\\()\\\\\)(?!\\\\\))/s', $excerpt['text'], $matches)) {
                $mathMatch = $matches[0];
            }
        }

        if (isset($mathMatch)) {
            return [
                'extent' => strlen($mathMatch),
                'element' => [
                    'text' => $mathMatch,
                ],
            ];
        }
    }

    protected function inlineEscapeSequence($excerpt)
    {
        $element = [
            'element' => [
                'rawHtml' => $excerpt['text'][1],
            ],
            'extent' => 2,
        ];

        $state = $this->options['math'] ?? false;

        if ($state) {
            if (isset($excerpt['text'][1]) && in_array($excerpt['text'][1], $this->specialCharacters) && !preg_match('/^(?<!\\\\)(?<!\\\\\()\\\\\((.{2,}?)(?<!\\\\\()\\\\\)(?!\\\\\))/s', $excerpt['text'])) {
                return $element;
            }
        } else {
            if (isset($excerpt['text'][1]) && in_array($excerpt['text'][1], $this->specialCharacters)) {
                return $element;
            }
        }
    }

    /**
     * ------------------------------------------------------------------------
     *  Blocks.
     * ------------------------------------------------------------------------
     */

    protected function blockHeader($line)
    {
        $state = $this->options['headings'] ?? true;
        if (!$state) {
            return;
        }

        $block = DynamicParent::blockHeader($line);
        if (!empty($block)) {
            // Get the text of the heading
            if (isset($block['element']['handler']['argument'])) {
                $text = $block['element']['handler']['argument'];
            }

            // Get the heading level. Levels are h1, h2, ..., h6
            $level = $block['element']['name'];

            $headersAllowed = $this->options['headings']['allowed'] ?? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            if (!in_array($level, $headersAllowed)) {
                return;
            }

            // Checks if auto generated anchors is allowed
            $autoAnchors = $this->options['headings']['auto_anchors'] ?? true;

            if ($autoAnchors) {
                // Get the anchor of the heading to link from the ToC list
                $id = $block['element']['attributes']['id'] ?? $this->createAnchorID($text);
            } else {
                // Get the anchor of the heading to link from the ToC list
                $id = $block['element']['attributes']['id'] ?? null;
            }

            // Set attributes to head tags
            $block['element']['attributes']['id'] = $id;

            $tocHeaders = $this->options['toc']['headings'] ?? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            // Check if level are defined as a heading
            if (in_array($level, $tocHeaders)) {
                // Add/stores the heading element info to the ToC list
                $this->setContentsList([
                    'text' => $text,
                    'id' => $id,
                    'level' => $level,
                ]);
            }

            return $block;
        }
    }

    protected function blockSetextHeader($line, $block = null)
    {
        $state = $this->options['headings'] ?? true;
        if (!$state) {
            return;
        }
        $block = DynamicParent::blockSetextHeader($line, $block);
        if (!empty($block)) {
            // Get the text of the heading
            if (isset($block['element']['handler']['argument'])) {
                $text = $block['element']['handler']['argument'];
            }

            // Get the heading level. Levels are h1, h2, ..., h6
            $level = $block['element']['name'];

            $headersAllowed = $this->options['headings']['allowed'] ?? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            if (!in_array($level, $headersAllowed)) {
                return;
            }

            // Checks if auto generated anchors is allowed
            $autoAnchors = $this->options['headings']['auto_anchors'] ?? true;

            if ($autoAnchors) {
                // Get the anchor of the heading to link from the ToC list
                $id = $block['element']['attributes']['id'] ?? $this->createAnchorID($text);
            } else {
                // Get the anchor of the heading to link from the ToC list
                $id = $block['element']['attributes']['id'] ?? null;
            }

            // Set attributes to head tags
            $block['element']['attributes']['id'] = $id;

            $headersAllowed = $this->options['headings']['allowed'] ?? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

            // Check if level are defined as a heading
            if (in_array($level, $headersAllowed)) {
                // Add/stores the heading element info to the ToC list
                $this->setContentsList([
                    'text' => $text,
                    'id' => $id,
                    'level' => $level,
                ]);
            }

            return $block;
        }
    }

    protected function blockAbbreviation($line)
    {
        $allowCustomAbbr = $this->options['abbreviations']['allow_custom_abbr'] ?? true;

        $state = $this->options['abbreviations'] ?? true;
        if ($state) {
            if (isset($this->options['abbreviations']['predefine'])) {
                foreach ($this->options['abbreviations']['predefine'] as $abbreviations => $description) {
                    $this->DefinitionData['Abbreviation'][$abbreviations] = $description;
                }
            }

            if ($allowCustomAbbr == true) {
                return DynamicParent::blockAbbreviation($line);
            }

            return;
        }
    }

    // Block Math

    protected function blockMath($line)
    {
        $block = [
            'element' => [
                'text' => '',
            ],
        ];

        if (preg_match('/^(?<!\\\\)(\\\\\[)(?!.)$/', $line['text'])) {
            $block['end'] = '\]';

            return $block;
        }
        if (preg_match('/^(?<!\\\\)(\$\$)(?!.)$/', $line['text'])) {
            $block['end'] = '$$';

            return $block;
        }
    }

    // ~

    protected function blockMathContinue($line, $block)
    {
        if (isset($block['complete'])) {
            return;
        }

        if (isset($block['interrupted'])) {
            $block['element']['text'] .= str_repeat(
                "\n",
                $block['interrupted']
            );

            unset($block['interrupted']);
        }

        if (preg_match('/^(?<!\\\\)(\\\\\])$/', $line['text']) && '\]' === $block['end']) {
            $block['complete'] = true;
            $block['math'] = true;
            $block['element']['text'] =
             '\\['.$block['element']['text'].'\\]';

            return $block;
        }
        if (preg_match('/^(?<!\\\\)(\$\$)$/', $line['text']) && '$$' === $block['end']) {
            $block['complete'] = true;
            $block['math'] = true;
            $block['element']['text'] = '$$'.$block['element']['text'].'$$';

            return $block;
        }

        $block['element']['text'] .= "\n".$line['body'];

        // ~

        return $block;
    }

    // ~

    protected function blockMathComplete($block)
    {
        return $block;
    }

    // Block Fenced Code

    protected function blockFencedCode($line)
    {
        $codeBlock = $this->options['code']['blocks'] ?? true;
        $codeMain = $this->options['code'] ?? true;
        if ($codeBlock === false or $codeMain === false) {
            return;
        }

        # Enable custom attribute syntax on code block
        $attributes = array();
        if (strpos($line['text'], '{') !== false && substr($line['text'], -1) === '}') {
            $parts = explode('{', $line['text'], 2);
            $attributes = $this->parseAttributeData(strtr(substr($parts[1], 0, -1), "\x1A", ' '));
            $line['text'] = trim($parts[0]);
        }

        if (!$block = DynamicParent::blockFencedCode($line)) {
            return;
        }

        # ¿Esto sirve?
        $marker = $line['text'][0];
        $openerLength = strspn($line['text'], $marker);
        $language = trim(
            preg_replace('/^`{3}([^\s]+)(.+)?/s', '$1', $line['text'])
        );

        if ($attributes) {
            $block['element']['attributes'] = $attributes;
        }

        return $block;
    }

    protected function blockTableComplete(array $Block)
    {
        if (!isset($Block))
        {
            return null;
        }

        $HeaderElements = &$Block['element']['text'][0]['text'][0]['text'];

        for ($index = count($HeaderElements) - 1; $index >= 0; --$index)
        {
            $colspan = 1;
            $HeaderElement = &$HeaderElements[$index];

            while ($index && $HeaderElements[$index - 1]['text'] === '>')
            {
                $colspan++;
                $PreviousHeaderElement = &$HeaderElements[--$index];
                $PreviousHeaderElement['merged'] = true;
                if (isset($PreviousHeaderElement['attributes']))
                {
                    $HeaderElement['attributes'] = $PreviousHeaderElement['attributes'];
                }
            }

            if ($colspan > 1)
            {
                if (!isset($HeaderElement['attributes']))
                {
                    $HeaderElement['attributes'] = array();
                }
                $HeaderElement['attributes']['colspan'] = $colspan;
            }
        }

        for ($index = count($HeaderElements) - 1; $index >= 0; --$index)
        {
            if (isset($HeaderElements[$index]['merged']))
            {
                array_splice($HeaderElements, $index, 1);
            }
        }

        $Rows = &$Block['element']['text'][1]['text'];

        foreach ($Rows as $RowNo => &$Row)
        {
            $Elements = &$Row['text'];

            for ($index = count($Elements) - 1; $index >= 0; --$index)
            {
                $colspan = 1;
                $Element = &$Elements[$index];

                while ($index && $Elements[$index - 1]['text'] === '>')
                {
                    $colspan++;
                    $PreviousElement = &$Elements[--$index];
                    $PreviousElement['merged'] = true;
                    if (isset($PreviousElement['attributes']))
                    {
                        $Element['attributes'] = $PreviousElement['attributes'];
                    }
                }

                if ($colspan > 1)
                {
                    if (!isset($Element['attributes']))
                    {
                        $Element['attributes'] = array();
                    }
                    $Element['attributes']['colspan'] = $colspan;
                }
            }
        }

        foreach ($Rows as $RowNo => &$Row)
        {
            $Elements = &$Row['text'];

            foreach ($Elements as $index => &$Element)
            {
                $rowspan = 1;

                if (isset($Element['merged']))
                {
                    continue;
                }

                while ($RowNo + $rowspan < count($Rows) && $index < count($Rows[$RowNo + $rowspan]['text']) && $Rows[$RowNo + $rowspan]['text'][$index]['text'] === '^' && (@$Element['attributes']['colspan'] ?: null) === (@$Rows[$RowNo + $rowspan]['text'][$index]['attributes']['colspan'] ?: null))
                {
                    $Rows[$RowNo + $rowspan]['text'][$index]['merged'] = true;
                    $rowspan++;
                }

                if ($rowspan > 1)
                {
                    if (!isset($Element['attributes']))
                    {
                        $Element['attributes'] = array();
                    }
                    $Element['attributes']['rowspan'] = $rowspan;
                }
            }
        }

        foreach ($Rows as $RowNo => &$Row)
        {
            $Elements = &$Row['text'];

            for ($index = count($Elements) - 1; $index >= 0; --$index)
            {
                if (isset($Elements[$index]['merged']))
                {
                    array_splice($Elements, $index, 1);
                }
            }
        }

        return $Block;
    }

    /*
    * Checkbox
    * -------------------------------------------------------------------------
    */
    protected function blockCheckbox($line)
    {
        $text = trim($line['text']);
        $beginLine = substr($text, 0, 4);
        $firstChars = substr($text, 0, 3);
        if ('[ ] ' === $beginLine OR '[] ' === $firstChars) {
            return [
                'handler' => 'checkboxUnchecked',
                'text' => substr(trim($text), 4),
            ];
        }

        if ('[x] ' === $beginLine OR '[X] ' === $beginLine) {
            return [
                'handler' => 'checkboxChecked',
                'text' => substr(trim($text), 4),
            ];
        }
    }

    protected function blockCheckboxContinue(array $block)
    {
        // This is here because Parsedown require it.
    }

    protected function blockCheckboxComplete(array $block)
    {
        $block['element'] = [
            'rawHtml' => $this->{$block['handler']}($block['text']),
            'allowRawHtmlInSafeMode' => true,
        ];

        return $block;
    }

    protected function checkboxUnchecked($text)
    {
        if ($this->markupEscaped || $this->safeMode) {
            $text = self::escape($text);
        }

        return '<input type="checkbox" disabled /> '.$this->format($text);
    }

    protected function checkboxChecked($text)
    {
        if ($this->markupEscaped || $this->safeMode) {
            $text = self::escape($text);
        }

        return '<input type="checkbox" checked disabled /> '.$this->format($text);
    }

    /**
     * ------------------------------------------------------------------------
     *  Helpers.
     * ------------------------------------------------------------------------.
     */

    /**
     * Formats the checkbox label without double escaping.
     */
    protected function format($text)
    {
        // backup settings
        $markupEscaped = $this->markupEscaped;
        $safeMode = $this->safeMode;

        // disable rules to prevent double escaping.
        $this->setMarkupEscaped(false);
        $this->setSafeMode(false);

        // format line
        $text = $this->line($text);

        // reset old values
        $this->setMarkupEscaped($markupEscaped);
        $this->setSafeMode($safeMode);

        return $text;
    }

    protected function parseAttributeData($attributeString)
    {
        $state = $this->options['special_attributes'] ?? true;
        if ($state) {
            return DynamicParent::parseAttributeData($attributeString);
        }

        return [];
    }

    /**
     * Encodes the ToC tag to a hashed tag and replace.
     *
     * This is used to avoid parsing user defined ToC tag which includes "_" in
     * their tag such as "[[_toc_]]". Unless it will be parsed as:
     *   "<p>[[<em>TOC</em>]]</p>"
     */
    protected function encodeTagToHash($text)
    {
        $salt = $this->getSalt();
        $tagOrigin = $this->getTagToC();

        if (strpos($text, $tagOrigin) === false) {
            return $text;
        }

        $tagHashed = hash('sha256', $salt.$tagOrigin);

        return str_replace($tagOrigin, $tagHashed, $text);
    }

    /**
     * Decodes the hashed ToC tag to an original tag and replaces.
     *
     * This is used to avoid parsing user defined ToC tag which includes "_" in
     * their tag such as "[[_toc_]]". Unless it will be parsed as:
     *   "<p>[[<em>TOC</em>]]</p>"
     */
    protected function decodeTagFromHash($text)
    {
        $salt = $this->getSalt();
        $tagOrigin = $this->getTagToC();
        $tagHashed = hash('sha256', $salt.$tagOrigin);

        if (strpos($text, $tagHashed) === false) {
            return $text;
        }

        return str_replace($tagHashed, $tagOrigin, $text);
    }

    /**
     * Unique string to use as a salt value.
     */
    protected function getSalt()
    {
        static $salt;
        if (isset($salt)) {
            return $salt;
        }

        $salt = hash('md5', time());

        return $salt;
    }

    /**
     * Gets the markdown tag for ToC.
     */
    protected function getTagToC()
    {
        return $this->options['toc']['set_toc_tag'] ?? '[toc]';
    }

    /**
     * Gets the ID attribute of the ToC for HTML tags.
     */
    protected function getIdAttributeToC()
    {
        if (isset($this->idToc) && !empty($this->idToc)) {
            return $this->idToc;
        }

        return self::ID_ATTRIBUTE_DEFAULT;
    }

    /**
     * Generates an anchor text that are link-able even if the heading is not in
     * ASCII.
     */
    protected function createAnchorID($str): string
    {

        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());

        $optionUrlEncode = $this->options['toc']['urlencode'] ?? false;
        if ($optionUrlEncode) {
            // Check AnchorID is unique
            $str = $this->incrementAnchorId($str);

            return urlencode($str);
        }

        $charMap = [
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'AA', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'OE', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'aa', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',

            // Latin symbols
            '©' => '(c)', '®' => '(r)', '™' => '(tm)',

            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ğ' => 'g',

            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',

            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',

            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',

            // Latvian
            'Ā' => 'A', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 'Ū' => 'u',
            'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n', 'ū' => 'u',
        ];

        // Transliterate characters to ASCII
        $optionTransliterate = $this->options['toc']['transliterate'] ?? false;
        if ($optionTransliterate) {
            $str = str_replace(array_keys($charMap), $charMap, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $optionDelimiter = $this->options['toc']['delimiter'] ?? '-';
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $optionDelimiter, $str);

        // Remove duplicate delimiters
        $str = preg_replace('/('.preg_quote($optionDelimiter, '/').'){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $optionLimit = $this->options['toc']['limit'] ?? mb_strlen($str, 'UTF-8');
        $str = mb_substr($str, 0, $optionLimit, 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $optionDelimiter);

        $urlLowercase = $this->options['toc']['lowercase'] ?? true;
        $str = $urlLowercase ? mb_strtolower($str, 'UTF-8') : $str;

        // Check if your ID is empty
        if (empty($str)) {
            $str = 'title';
        }

        return $this->incrementAnchorId($str);
    }

    /**
     * Get only the text from a markdown string.
     * It parses to HTML once then trims the tags to get the text.
     */
    protected function fetchText($text)
    {
        return trim(strip_tags($this->line($text)));
    }

    /**
     * Set/stores the heading block to ToC list in a string and array format.
     */
    protected function setContentsList(array $Content)
    {
        // Stores as an array
        $this->setContentsListAsArray($Content);
        // Stores as string in markdown list format.
        $this->setContentsListAsString($Content);
    }

    /**
     * Sets/stores the heading block info as an array.
     */
    protected function setContentsListAsArray(array $Content)
    {
        $this->contentsListArray[] = $Content;
    }

    /**
     * Sets/stores the heading block info as a list in markdown format.
     */
    protected function setContentsListAsString(array $Content)
    {
        $text = $this->fetchText($Content['text']);
        $id = $Content['id'];
        $level = (int) trim($Content['level'], 'h');
        $link = "[{$text}](#{$id})";

        if (0 === $this->firstHeadLevel) {
            $this->firstHeadLevel = $level;
        }
        $cutIndent = $this->firstHeadLevel - 1;
        if ($cutIndent > $level) {
            $level = 1;
        } else {
            $level = $level - $cutIndent;
        }

        $indent = str_repeat('  ', $level);

        // Stores in markdown list format as below:
        // - [Header1](#Header1)
        //   - [Header2-1](#Header2-1)
        //     - [Header3](#Header3)
        //   - [Header2-2](#Header2-2)
        // ...
        $this->contentsListString .= "{$indent}- {$link}".PHP_EOL;
    }

    /**
     * Collect and count anchors in use to prevent duplicated ids. Return string
     * with incremental, numeric suffix. Also init optional blacklist of ids.
     */
    protected function incrementAnchorId($str)
    {
        // add blacklist to list of used anchors
        if (!$this->isBlacklistInitialized) {
            $this->initBlacklist();
        }

        $this->anchorDuplicates[$str] = !isset($this->anchorDuplicates[$str]) ? 0 : ++$this->anchorDuplicates[$str];

        $newStr = $str;

        if ($count = $this->anchorDuplicates[$str]) {
            $newStr .= "-{$count}";

            // increment until conversion doesn't produce new duplicates anymore
            if (isset($this->anchorDuplicates[$newStr])) {
                $newStr = $this->incrementAnchorId($str);
            } else {
                $this->anchorDuplicates[$newStr] = 0;
            }
        }

        return $newStr;
    }

    /**
     * Add blacklisted ids to anchor list.
     */
    protected function initBlacklist()
    {
        if ($this->isBlacklistInitialized) {
            return;
        }

        if (!empty($this->options['headings']['blacklist']) && is_array($this->options['headings']['blacklist'])) {
            foreach ($this->options['headings']['blacklist'] as $v) {
                if (is_string($v)) {
                    $this->anchorDuplicates[$v] = 0;
                }
            }
        }

        $this->isBlacklistInitialized = true;
    }

    protected function lineElements($text, $nonNestables = [])
    {
        $Elements = [];

        $nonNestables = (
            empty($nonNestables)
            ? []
            : array_combine($nonNestables, $nonNestables)
        );

        // $excerpt is based on the first occurrence of a marker

        while ($excerpt = strpbrk($text, $this->inlineMarkerList)) {
            $marker = $excerpt[0];

            $markerPosition = strlen($text) - strlen($excerpt);

            // Get the first char before the marker
            $beforeMarkerPosition = $markerPosition - 1;
            if ($beforeMarkerPosition >= 0) {
                $charBeforeMarker = $text[$markerPosition - 1];
            } else {
                $charBeforeMarker = '';
            }

            $Excerpt = ['text' => $excerpt, 'context' => $text, 'before' => $charBeforeMarker];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
                // check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType])) {
                    continue;
                }

                $Inline = $this->{"inline{$inlineType}"}($Excerpt);

                if (!isset($Inline)) {
                    continue;
                }

                // makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
                    continue;
                }

                // sets a default inline position

                if (!isset($Inline['position'])) {
                    $Inline['position'] = $markerPosition;
                }

                // cause the new element to 'inherit' our non nestables

                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables
                ;

                // the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                // compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                // compile the inline
                $Elements[] = $this->extractElement($Inline);

                // remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            // the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element) {
            if (!isset($Element['autobreak'])) {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }

    private function pregReplaceAssoc(array $replace, $subject)
    {
        return preg_replace(array_keys($replace), array_values($replace), $subject);
    }

    // Custom YUSSEL

    protected function blockAlert($block)
    {

        $contextualClasses = array(
            "primary",
            "secondary",
            "success",
            "danger",
            "warning",
            "info",
            "light",
            "dark"
        );
        if (preg_match('/::: (.*)/', $block['text'], $matches)) {
            if (in_array($matches[1], $contextualClasses)) {
                return [
                    'widget' => 'alert',
                    'element' => [
                        'name' => 'div',
                        'text' => '',
                        'attributes' => [
                            'class' => "alert alert-{$matches[1]}",
                        ],
                    ],
                ];
            } else {
                return [
                    'widget' => 'callout',
                    'element' => [
                        'name' => 'div',
                        'text' => '',
                        'attributes' => [
                            'class' => "callout shadow-sm callout-{$matches[1]}",
                        ],
                    ],
                ];
            }
        }
    }

    protected function blockAlertContinue($line, $block)
    {
        if (isset($block['complete'])) {
            return;
        }

        if (preg_match('/:::/', $line['text'], $matches)) {
            $block['complete'] = true;

            return $block;
        }

        $block['element']['text'] .= $line['text']."\n";

        return $block;
    }

    protected function blockAlertComplete($block)
    {
        $block['element']['rawHtml'] = $this->text($block['element']['text']);
        unset($block['element']['text']);
        return $block;
    }
}