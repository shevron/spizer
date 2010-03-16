<?php

/**
 * Spizer - the flexible PHP web spider
 * Copyright 2010 Shahar Evron
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License. 
 * 
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */

require_once 'Spizer/Handler/Abstract.php';
require_once 'Zend/Log.php';

/**
 * The logger object is a simple handler that writes trivial request 
 * information such as URL, method, response code and response content length
 * to a provided Zend_Log object.
 * 
 * 
 * @deprecated Probably not required since the introduction of logger objects
 * @see        Zend_Log
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_Logger extends Spizer_Handler_Abstract  
{
	/**
	 * Logger object
	 *
	 * @var Zend_Log
	 */
	protected $_logger = null;
	
	/**
	 * Create a new Logger Handler
	 *
	 * @param Zend_Log $logger
	 */
	public function __construct(Zend_Log $logger)
	{
		$this->_logger = $logger;
	}
	
	/**
	 * Handle document
	 *
	 * @param Spizer_Document $document
	 */
	public function handle(Spizer_Document $document)
	{
		
	    $string = "{$document->getUrl()} {$document->getStatus()} " . strlen($document->getBody());
		
	    // Decide log level according to status code
	    switch(round($document->getStatus() / 100)) {
	        case 1:
	        case 2:
	            $level = Zend_Log::INFO;
	            break;
	         
	        case 3:
	            $level = Zend_Log::NOTICE;
	            break;
	            
	        case 4:
	            $level = Zend_Log::WARN;
	            break;
	            
	        case 5:
	            $level = Zend_Log::ERR;
	            break;
	    }
		
	    $this->_logger->log($string, $level);
	}
}
