<?php

require_once(LOG4PHP_DIR . '/layouts/LoggerLayoutPattern.php');


class LoggerPatternLayoutScalr extends LoggerLayoutPattern {

      
    /**
     * Returns LoggerPatternParser used to parse the conversion string. Subclasses
     * may override this to return a subclass of PatternParser which recognize
     * custom conversion characters.
     *
     * @param string $pattern
     * @return LoggerPatternParser
     */
    function createPatternParser($pattern)
    {
        return new LoggerPatternParserScalr($pattern);
    }    
}
