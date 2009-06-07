<?php

/**
 * Spizer - the flexible PHP web spider
 * Copyright 2009 Shahar Evron
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
 * Basic Spizer document object -  represent a generic crawled entity and 
 * includes information about the HTTP request and response that generated the
 * entity.
 * 
 * Document objects do not nescesarily represent a read "document" - for 
 * example, an HTTP response resulting in a 404 or 302 response code will also
 * be represented in a document. 
 * 
 * Documents are generated using the factory pattern - which will first look
 * at the headers and try to match a more specific subclass of Spizer_Document
 * for the received content type (eg. text/html pages will be instantiated as
 * Spizer_Document_Html objects). If no better matching class is found, a 
 * generic Spizer_Document object will be instantiated. 
 *
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @package    Spizer
 * @subpackage Document
 */
class Spizer_Document
{
	protected $url     = null;
	
	protected $body    = null;
	
	protected $headers = array();
	
	protected $status  = null;
	
	protected function __construct($url, $status, array $headers, $body)
	{
		$this->url     = $url;
		$this->status  = $status;
		$this->headers = $headers;
		$this->body    = $body;
	}
	
	public function getUrl()
	{
		return $this->url;
	}
	
	public function getBody()
	{
		return $this->body;
	}
	
	public function getStatus()
	{
		return $this->status;
	}
	
	public function getHeader($header)
	{
		$header = strtolower($header);
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		} else {
			return null;
		}
	}
	
	public function getAllHeaders()
	{
		return $this->headers();
	}
	
	/**
	 * Instantiate a new Document object depending on content type
	 *
	 * @param  Spizer_Request  $request
	 * @param  Spizer_Response $response
	 * @return Spizer_Document
	 */
	static public function factory(Spizer_Request $request, Spizer_Response $response)
	{
	    $url     = $request->getUri();
	    $code    = $response->getStatus();
	    $headers = $response->getAllHeaders();
	    $body    = $response->getBody();

	    // Find out the content type of the document
	    if (isset($headers['content-type'])) {
	        preg_match('/^[^;\s]+/', $headers['content-type'], $m);
	        $type = $m[0];
	        unset($m);
	    } else {
	        $type = ''; 
	    }
	     
		switch ($type) {
			case 'text/html':
			case 'text/xhtml':
				$class = 'Spizer_Document_Html';
				break;
				
			case 'text/xml':
			case 'application/xml':
			    $class = 'Spizer_Document_Xml';
			    break;
			    
			default:
				$class = 'Spizer_Document';
				break;
		}
		
		Zend_Loader::loadClass($class);
		return new $class($url, $code, $headers, $body);
	}
}
