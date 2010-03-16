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

require_once 'Spizer/Document.php';

/**
 * Spizer XML document object - provides additional XML-specific accessors in
 * addition to the default Document object, such as running XPath queries and
 * accessing the document through the DOM interface provided by PHP
 *
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @package    Spizer
 * @subpackage Document
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Document_Xml extends Spizer_Document
{
    /**
	 * DOMDocument object of this document
	 *
	 * @var DOMDocument
	 */
	protected $_domDocument = null;

	/**
	 * DOM XPath object - generated on demand using self::getXpath()
	 *
	 * @var DOMXPath
	 */
	protected $_domXpath    = null;
	
	protected function __construct($url, $status, array $headers, $body)
	{
		parent::__construct($url, $status, $headers, $body);
		
		$this->_domDocument = new DOMDocument();
		$this->_domDocument->preserveWhiteSpace = true;
		
		// We have to silence this out because invalid documents
		// tend to throw allot of warnings
		@$this->_domDocument->loadHtml($body);	
	}

	/**
	 * Get the DOMDocument object for this XML page
	 *
	 * @return DOMDocument
	 */
	public function getDomDocument()
	{
		return $this->_domDocument;
	}
	
	/**
	 * Get the DOM XPath object for the DOMDocument
	 *
	 * @return DOMXPath
	 */
	public function getXpath()
	{
	    if (! $this->_domXpath) {
	        $this->_domXpath = new DOMXPath($this->_domDocument);
	    }
	    
	    return $this->_domXpath;
	}
}
