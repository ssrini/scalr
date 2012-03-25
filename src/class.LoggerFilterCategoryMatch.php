<?php

require_once(LOG4PHP_DIR . '/helpers/LoggerOptionConverter.php');
require_once(LOG4PHP_DIR . '/LoggerFilter.php');

class LoggerFilterCategoryMatch extends LoggerFilter {
		
    /**
     * @var boolean
     */
    var $acceptOnMatch = false;

    /**
     * @var string
     */
    var $stringToMatch = null;
   
    /**
     * @return boolean
     */
    function getAcceptOnMatch() {
        return $this->acceptOnMatch;
    }
    
    /**
     * @param mixed $acceptOnMatch a boolean or a string ('true' or 'false')
     */
    function setAcceptOnMatch($acceptOnMatch) {
        $this->acceptOnMatch = is_bool($acceptOnMatch) ? 
            $acceptOnMatch : 
            (bool)(strtolower($acceptOnMatch) == 'true');
    }
    
    /**
     * @return string
     */
    function getStringToMatch() {
        return $this->stringToMatch;
    }
    
    /**
     * @param string $s the string to match
     */
    function setStringToMatch($s) {
        $this->stringToMatch = $s;
    }

    /**
     * @return integer a {@link LOGGER_FILTER_NEUTRAL} is there is no string match.
     */
    function decide(LoggerLoggingEvent $event) {
        $category = $event->getLoggerName();
        
        if ($category === null or  $this->stringToMatch === null) {
            return LoggerFilter::NEUTRAL;
        }
       
        if (preg_match($this->stringToMatch, $category)) {
            return $this->acceptOnMatch ? LoggerFilter::ACCEPT : LoggerFilter::NEUTRAL; 
        } else {
        	return LoggerFilter::DENY;
        }
        
        return $retval;
    }	
} 