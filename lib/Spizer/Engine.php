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

require_once 'Zend/Http/Client.php';
require_once 'Zend/Uri/Http.php';
require_once 'Spizer/Document.php';
require_once 'Spizer/Response.php';
require_once 'Spizer/Queue.php';

/**
 * Main Spizer engine class 
 * 
 * The Engine object (it usually takes only one) is the "heart" of Spizer and 
 * is in charge of the actual crawling process - that is sending the requests,
 * instantiating document objects and passing them along to handler objects as
 * needed. 
 *
 * @todo       Logging facility (not data logger - error logging etc.)
 * 
 * @package    Spizer
 * @subpackage Core
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Engine
{
	/**
	 * The spider's HTTP client
	 *
	 * @var Zend_Http_Client
	 */
	protected $_httpClient   = null;
	
	/**
	 * Base URL to use for relative links 
	 *
	 * @var Zend_Uri_Http
	 */
	protected $_baseUri      = null;
	
	/**
	 * Handlers to run on all visited pages
	 * 
	 * @var array
	 */
	protected $_handlers     = array();
	
	/**
	 * Array of URLS to be scanned
	 *
	 * @var Spizer_Queue
	 */
	protected $_queue        = null;
	
	/**
	 * Logger object
	 * 
	 * @var Spizer_Logger_Interface 
	 */
	protected $logger       = null;
	
	/**
	 * Configuration array
	 *
	 * @var array
	 */
	protected $_config       = array(
	    'savecookies' => true,
	    'lifo'        => false,
	    'httpOpts'    => array()
	);
	
	/**
	 * Request counter
	 *
	 * @var integer
	 */
	protected $_requestCounter = 0;
	
	/**
	 * Create a new Spizer Engine object
	 *
	 * @param array $config Configuration array
	 */
	public function __construct(array $config = array())
	{
   		// Load configuration
		foreach ($config as $k => $v) {
			$this->_config[$k] = $v;
		}
		
		// Set up the HTTP client
		$this->_httpClient = new Zend_Http_Client();
		if ($this->_config['savecookies']) $this->_httpClient->setCookieJar();
		
		if (isset($this->_config['httpOpts']) && is_array($this->_config['httpOpts'])) {
		    $httpOpts = $this->_config['httpOpts'];
		} else {
		    $httpOpts = array();
		}
		
		$httpOpts['maxredirects'] = 0;
		
		$this->_httpClient->setConfig($httpOpts);
		
		// Set up the queue
		$this->_queue = new Spizer_Queue($this->_config['lifo']);
	}
	
	/**
	 * Return the HTTP client object used by this crawler
	 *
	 * @return Zend_Http_Client
	 */
	public function getHttpClient()
	{
		return $this->_httpClient;
	}
	
	/**
	 * Set the logger object for the spizer engine
	 *
	 * @param  Spizer_Logger_Interface $logger
	 * @return Spizer_Engine
	 */
	public function setLogger(Spizer_Logger_Interface $logger)
	{
	    if ($this->logger) {
	        throw new Spizer_Exception('Logger was already set, can\'t set logger now');
	    }
	    
	    $this->logger = $logger;
	    
	    return $this;
	}
	
	/**
	 * Get the logger object of this spizer engine
	 *
	 * @return Spizer_Logger_Interface
	 */
	public function getLogger()
	{
	    return $this->logger;
	}
	
	/**
	 * Log a message from one of the handler objects
	 *
	 * @param string $handler Handler name
	 * @param array  $info    Information to log (associative array)
	 */
	public function log($handler, $info = array())
	{
	    if (! $this->logger) {
	        throw new Spizer_Exception('Logger was not set yet, can\'t log any handler information');
	    }
	    
	    $this->logger->logHandlerInfo($handler, $info);
	}
	
	/**
	 * Run the crawler until we hit the last URL
	 *
	 * @param string $url URL to start crawling from
	 */
	public function run($url)
	{
		if (! Zend_Uri_Http::check($url)) {
			require_once 'Spizer/Exception.php';
			throw new Spizer_Exception("'$url' is not a valid HTTP URI");
		}
		$this->_baseUri = Zend_Uri::factory($url);
		$this->_queue->append($url);
		
		// Set the default logger if not already set
		if (! $this->logger) {
		    require_once 'Spizer/Logger/Xml.php';
		    $this->logger = new Spizer_Logger_Xml();
		}
		
		// Go!
		while ($request = $this->_queue->next()) {
		    $this->logger->startPage();
	        $this->logger->logRequest($request);
		    
    	    // Prepare HTTP client for next request
	        $this->_httpClient->resetParameters();
	        $this->_httpClient->setUri($request->getUri());
    	    $this->_httpClient->setMethod($request->getMethod());
	        $this->_httpClient->setHeaders($request->getAllHeaders());
	        $this->_httpClient->setRawData($request->getBody());
		    
       	    // Send request, catching any HTTP related issues that might happen
       	    try {
	            $response = new Spizer_Response($this->_httpClient->request());
       	    } catch (Zend_Exception $e) {
       	        fwrite(STDERR, "Error executing request: {$e->getMessage()}.\n");
       	        fwrite(STDERR, "Request information:\n");
       	        fwrite(STDERR, "  {$request->getMethod()} {$request->getUri()}\n");
       	        fwrite(STDERR, "  Referred by: {$request->getReferrer()}\n");
       	    }
       	    
    	    $this->logger->logResponse($response);
	    
	        // Call handlers
	        $this->_callHandlers($request, $response);
	    
    	    // End page
	        $this->logger->endPage();
	        ++$this->_requestCounter;
	    
    	    // Wait if a delay was set
	    	if (isset($this->_config['delay'])) sleep($this->_config['delay']);
		}
	}
	
	/**
	 * Authenticate the HTTP client by sending $data to $url before starting
	 * to crawl
	 *
	 * @todo   review this 
	 * 
	 * @param  string $url
	 * @param  array  $data
	 * @param  string $method
	 * @return Spizer_Engine
	 */
	public function authenticateUrl($url, array $data, $method = Zend_Http_Client::POST)
	{
		if ($method == 'POST') {
			$this->_httpClient->setParameterPost($data);
		} else {
			$this->_httpClient->setParameterGet($data);
		}
		
		$this->_httpClient->setUri($url);
		$this->_httpClient->request($method);
		
		return $this;
	}
	
	/**
	 * Get the URL queue of this crawler
	 *
	 * @return Spizer_Queue
	 */
	public function getQueue()
	{
		return $this->_queue;
	}
	
	/**
	 * Add another handler object to the generic handlers stack
	 *
	 * @param  Spizer_Handler_Abstract $handler
	 * @return Spizer_Engine
	 */
	public function addHandler(Spizer_Handler_Abstract $handler)
	{
		$handler->setEngine($this);
		$this->_handlers[] = $handler;
		return $this;
	}
	
	/**
	 * Get the current value of the request counter
	 *
	 * @return integer
	 */
	public function getRequestCounter()
	{
	    return $this->_requestCounter;
	}
	
	/**
	 * Get the first URI crawling started from
	 *
	 * @return Zend_Uri_Http
	 */
	public function getBaseUri()
	{
	    return $this->_baseUri;
	}
	
	/**
	 * Call all handlers on document 
	 *
	 * @param Spizer_Request  $request
	 * @param Spizer_Response $response
	 */
	private function _callHandlers(Spizer_Request $request, Spizer_Response $response)
	{
	    $document = Spizer_Document::factory($request, $response);
	    
	    // Run all common handlers
	    foreach ($this->_handlers as $handler) {
			$handler->call($document);
		}
	}
}
