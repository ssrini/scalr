<?php

class LoggerBasicPatternConverterScalr extends LoggerBasicPatternConverter {

    /**
     * @param LoggerLoggingEvent $event
     * @return string
     */
    function convert($event)
    {
        switch($this->type) {
                
            case LOG4PHP_LOGGER_PATTERN_PARSER_SUBTHREAD_CONVERTER:
                    return $event->subThreadName;
            case LOG4PHP_LOGGER_PATTERN_PARSER_BACKTRACE_CONVERTER:
                    return $event->backtrace;
            case LOG4PHP_LOGGER_PATTERN_PARSER_FARMID_CONVERTER:
                    return $event->farmID;
            default: 
                return '';
        }
    }
}