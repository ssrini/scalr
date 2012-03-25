<?php

require_once(LOG4PHP_DIR . '/helpers/LoggerPatternParser.php');

define('LOG4PHP_LOGGER_PATTERN_PARSER_SUBTHREAD_CONVERTER', 9999);
define('LOG4PHP_LOGGER_PATTERN_PARSER_BACKTRACE_CONVERTER', 9998);
define('LOG4PHP_LOGGER_PATTERN_PARSER_FARMID_CONVERTER', 9997);


class LoggerPatternParserScalr extends LoggerPatternParser {

    function parse()
    {
        LoggerLog::debug("LoggerPatternParser::parse()");
    
        $c = '';
        $this->i = 0;
        $this->currentLiteral = '';
        while ($this->i < $this->patternLength) {
            $c = $this->pattern{$this->i++};
//            LoggerLog::debug("LoggerPatternParser::parse() char is now '$c' and currentLiteral is '{$this->currentLiteral}'");            
            switch($this->state) {
                case LOG4PHP_LOGGER_PATTERN_PARSER_LITERAL_STATE:
                    // LoggerLog::debug("LoggerPatternParser::parse() state is 'LOG4PHP_LOGGER_PATTERN_PARSER_LITERAL_STATE'");
                    // In literal state, the last char is always a literal.
                    if($this->i == $this->patternLength) {
                        $this->currentLiteral .= $c;
                        continue;
                    }
                    if($c == LOG4PHP_LOGGER_PATTERN_PARSER_ESCAPE_CHAR) {
                        // LoggerLog::debug("LoggerPatternParser::parse() char is an escape char");                    
                        // peek at the next char.
                        switch($this->pattern{$this->i}) {
                            case LOG4PHP_LOGGER_PATTERN_PARSER_ESCAPE_CHAR:
                                // LoggerLog::debug("LoggerPatternParser::parse() next char is an escape char");                    
                                $this->currentLiteral .= $c;
                                $this->i++; // move pointer
                                break;
                            case 'n':
                                // LoggerLog::debug("LoggerPatternParser::parse() next char is 'n'");                            
                                $this->currentLiteral .= LOG4PHP_LINE_SEP;
                                $this->i++; // move pointer
                                break;
                            default:
                                if(strlen($this->currentLiteral) != 0) {
                                    $this->addToList(new LoggerLiteralPatternConverter($this->currentLiteral));
                                    LoggerLog::debug("LoggerPatternParser::parse() Parsed LITERAL converter: \"{$this->currentLiteral}\".");
                                }
                                $this->currentLiteral = $c;
                                $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_CONVERTER_STATE;
                                $this->formattingInfo->reset();
                        }
                    } else {
                        $this->currentLiteral .= $c;
                    }
                    break;
              case LOG4PHP_LOGGER_PATTERN_PARSER_CONVERTER_STATE:
                    // LoggerLog::debug("LoggerPatternParser::parse() state is 'LOG4PHP_LOGGER_PATTERN_PARSER_CONVERTER_STATE'");              
                        $this->currentLiteral .= $c;
                        switch($c) {
                        case '-':
                            $this->formattingInfo->leftAlign = true;
                            break;
                        case '.':
                            $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_DOT_STATE;
                                break;
                        default:
                            if(ord($c) >= ord('0') and ord($c) <= ord('9')) {
                                    $this->formattingInfo->min = ord($c) - ord('0');
                                    $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_MIN_STATE;
                            } else {
                                $this->finalizeConverter($c);
                            }
                        } // switch
                    break;
              case LOG4PHP_LOGGER_PATTERN_PARSER_MIN_STATE:
                    // LoggerLog::debug("LoggerPatternParser::parse() state is 'LOG4PHP_LOGGER_PATTERN_PARSER_MIN_STATE'");              
                        $this->currentLiteral .= $c;
                    if(ord($c) >= ord('0') and ord($c) <= ord('9')) {
                        $this->formattingInfo->min = ($this->formattingInfo->min * 10) + (ord($c) - ord('0'));
                        } elseif ($c == '.') {
                        $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_DOT_STATE;
                    } else {
                        $this->finalizeConverter($c);
                        }
                        break;
              case LOG4PHP_LOGGER_PATTERN_PARSER_DOT_STATE:
                    // LoggerLog::debug("LoggerPatternParser::parse() state is 'LOG4PHP_LOGGER_PATTERN_PARSER_DOT_STATE'");              
                        $this->currentLiteral .= $c;
                    if(ord($c) >= ord('0') and ord($c) <= ord('9')) {
                        $this->formattingInfo->max = ord($c) - ord('0');
                            $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_MAX_STATE;
                    } else {
                          LoggerLog::warn("LoggerPatternParser::parse() Error occured in position {$this->i}. Was expecting digit, instead got char \"{$c}\".");
                          $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_LITERAL_STATE;
                    }
                        break;
              case LOG4PHP_LOGGER_PATTERN_PARSER_MAX_STATE:
                    // LoggerLog::debug("LoggerPatternParser::parse() state is 'LOG4PHP_LOGGER_PATTERN_PARSER_MAX_STATE'");              
                        $this->currentLiteral .= $c;
                    if(ord($c) >= ord('0') and ord($c) <= ord('9')) {
                        $this->formattingInfo->max = ($this->formattingInfo->max * 10) + (ord($c) - ord('0'));
                        } else {
                          $this->finalizeConverter($c);
                      $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_LITERAL_STATE;
                        }
                        break;
            } // switch
        } // while
        if(strlen($this->currentLiteral) != 0) {
            $this->addToList(new LoggerLiteralPatternConverter($this->currentLiteral));
            // LoggerLog::debug("LoggerPatternParser::parse() Parsed LITERAL converter: \"{$this->currentLiteral}\".");
        }
        return $this->head;
    }

    function finalizeConverter($c)
    {
        LoggerLog::debug("LoggerPatternParser::finalizeConverter() with char '$c'");    

        $pc = null;
        switch($c) {
        	
        	case 'b':
                $pc = new LoggerBasicPatternConverterScalr($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_BACKTRACE_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() BACKTRACE converter.");
                $this->currentLiteral = '';
                break;
        	
            case 'c':
                $pc = new LoggerCategoryPatternConverter($this->formattingInfo, $this->extractPrecisionOption());
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() CATEGORY converter.");
                $this->currentLiteral = '';
                break;
            case 'C':
                $pc = new LoggerClassNamePatternConverter($this->formattingInfo, $this->extractPrecisionOption());
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() CLASSNAME converter.");
                $this->currentLiteral = '';
                break;
            case 'd':
                $dateFormatStr = LOG4PHP_LOGGER_PATTERN_PARSER_DATE_FORMAT_ISO8601; // ISO8601_DATE_FORMAT;
                $dOpt = $this->extractOption();

                if($dOpt !== null)
                        $dateFormatStr = $dOpt;
                    
                if ($dateFormatStr == 'ISO8601') {
                    $df = LOG4PHP_LOGGER_PATTERN_PARSER_DATE_FORMAT_ISO8601;
                } elseif($dateFormatStr == 'ABSOLUTE') {
                    $df = LOG4PHP_LOGGER_PATTERN_PARSER_DATE_FORMAT_ABSOLUTE;
                } elseif($dateFormatStr == 'DATE') {
                    $df = LOG4PHP_LOGGER_PATTERN_PARSER_DATE_FORMAT_DATE;
                } else {
                    $df = $dateFormatStr;
                    if ($df == null) {
                        $df = LOG4PHP_LOGGER_PATTERN_PARSER_DATE_FORMAT_ISO8601;
                    }
                    }
                $pc = new LoggerDatePatternConverter($this->formattingInfo, $df);
                $this->currentLiteral = '';
                break;
            case 'F':
                $pc = new LoggerLocationPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_FILE_LOCATION_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() File name converter.");
                $this->currentLiteral = '';
                break;
            case 'f':
                $pc = new LoggerBasicPatternConverterScalr($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_FARMID_CONVERTER);                
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() FARMID converter.");
                $this->currentLiteral = '';
                break;
                
            case 'l':
                $pc = new LoggerLocationPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_FULL_LOCATION_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() Location converter.");
                $this->currentLiteral = '';
                break;
            case 'L':
                $pc = new LoggerLocationPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_LINE_LOCATION_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() LINE NUMBER converter.");
                $this->currentLiteral = '';
                break;
            case 'm':
                $pc = new LoggerBasicPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_MESSAGE_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() MESSAGE converter.");
                $this->currentLiteral = '';
                break;
            case 'M':
                $pc = new LoggerLocationPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_METHOD_LOCATION_CONVERTER);
                $this->currentLiteral = '';
                break;
            case 'p':
                $pc = new LoggerBasicPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_LEVEL_CONVERTER);
                $this->currentLiteral = '';
                break;
            case 'r':
                $pc = new LoggerBasicPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_RELATIVE_TIME_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() RELATIVE TIME converter.");
                $this->currentLiteral = '';
                break;
            case 't':
                $pc = new LoggerBasicPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_THREAD_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() THREAD converter.");
                $this->currentLiteral = '';
                break;
            case 's':
                $pc = new LoggerBasicPatternConverterScalr($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_SUBTHREAD_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() SUBTHREAD converter.");
                $this->currentLiteral = '';
                break;
                
            case 'u':
                if($this->i < $this->patternLength) {
                        $cNext = $this->pattern{$this->i};
                    if(ord($cNext) >= ord('0') and ord($cNext) <= ord('9')) {
                            $pc = new LoggerUserFieldPatternConverter($this->formattingInfo, (string)(ord($cNext) - ord('0')));
                        LoggerLog::debug("LoggerPatternParser::finalizeConverter() USER converter [{$cNext}].");
                        $this->currentLiteral = '';
                            $this->i++;
                        } else {
                        LoggerLog::warn("LoggerPatternParser::finalizeConverter() Unexpected char '{$cNext}' at position {$this->i}.");
                    }
                }
                break;
            case 'x':
                $pc = new LoggerBasicPatternConverter($this->formattingInfo, LOG4PHP_LOGGER_PATTERN_PARSER_NDC_CONVERTER);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() NDC converter.");
                $this->currentLiteral = '';
                break;

            case 'X':
                $xOpt = $this->extractOption();
                $pc = new LoggerMDCPatternConverter($this->formattingInfo, $xOpt);
                LoggerLog::debug("LoggerPatternParser::finalizeConverter() MDC converter.");
                $this->currentLiteral = '';
                break;
            default:
                LoggerLog::warn("LoggerPatternParser::finalizeConverter() Unexpected char [$c] at position {$this->i} in conversion pattern.");
                $pc = new LoggerLiteralPatternConverter($this->currentLiteral);
                $this->currentLiteral = '';
        }
        $this->addConverter($pc);
    }

    function addConverter($pc)
    {
        $this->currentLiteral = '';
        // Add the pattern converter to the list.
        $this->addToList($pc);
        // Next pattern is assumed to be a literal.
        $this->state = LOG4PHP_LOGGER_PATTERN_PARSER_LITERAL_STATE;
        // Reset formatting info
        $this->formattingInfo->reset();
    }
}

