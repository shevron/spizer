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

/**
 * Base (abstract) handler class. All handler classes must inherit from this
 * class and implement the handle() method
 * 
 * Handler objects implement the core functionality of Spizer. Depending on
 * setup, each entity crawled by Spizer will trigger a set of registered 
 * handler objects, processing the Spizer_Document object representing the
 * entity. 
 * 
 * Handler objects can implement any functionality - some examples might be W3C
 * validation of HTML, form XSS testing and simple logging of broken links. 
 * Handler objects have access to the Spizer_Engine and it's queue and logger 
 * objects, allowing handlers to log messages and add additional requeusts to 
 * the queue 
 * 
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
abstract class Spizer_Handler_Abstract
{
	/**
	 * Spizer Engine Object
	 *
	 * @var Spizer_Engine
	 */
	protected $_engine = null;
	
	/**
	 * Handler name - can be set using setHandlerName()
	 *
	 * @var string
	 */
	protected $_name   = 'DefaultHandler';
	
	/**
	 * Handler configuration array
	 *
	 * @var array
	 */
	protected $_config = array(
	    'status'       => null,
	    'content-type' => null
	);
	
	/**
	 * Create the new handler object, loading configuration array
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array())
	{
	    $this->_config = array_merge($this->_config, $config);
	}
	
	/**
	 * Set the crawling engine calling the handler
	 *
	 * @param Spizer_Engine $engine
	 */
	public function setEngine(Spizer_Engine $engine)
	{
		$this->_engine = $engine;
	}
	
	/**
	 * Check if the handler actually needs to be called (according to it's
	 * content type and status code), and if so call ::handle()
	 *
	 * @param Spizer_Document $document
	 */
	public function call(Spizer_Document $document)
	{
	    $status = $document->getStatus();
	    $type   = $document->getHeader('content-type');
	    
	    $call = true;
	    if ($this->_config['status']) {
	        if (is_array($this->_config['status'])) {
	            if (! in_array($status, $this->_config['status'])) $call = false;
	        } elseif ($this->_config['status'] != $status) {
	            $call = false;
	        }
	    }
	    
	    if ($this->_config['content-type']) {
	        if (is_array($this->_config['content-type'])) {
	            if (! in_array($type, $this->_config['content-type'])) $call = false;
	        } elseif ($this->_config['content-type'] != $type) {
	            $call = false;
	        }
	    }
	    
	    if ($call) $this->handle($document);
	}

	/**
	 * Set the handler name - the handler name is used to identify the particular
	 * handler object (not only the handler type) and can be any arbitrary string
	 *
	 * @param string $name
	 */
	public function setHandlerName($name)
	{
	    $this->_name = $name;
	}
	
	/**
	 * Pass some data to the logger
	 * 
	 * @param array $data
	 */
	protected function _log($data)
	{
	    $this->_engine->log($this->_name, $data);
	}
	
	/**
	 * Handle document
	 *
	 * @param Spizer_Document $document
	 */
	abstract public function handle(Spizer_Document $document);
}
