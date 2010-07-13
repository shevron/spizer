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

/**
 * The XPath handler class allows running XML Xpath queries on XML-based 
 * documents (including HTML documents). 
 * 
 * If the XPath query provides results, information will be logged to the
 * logger object. 
 * 
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_XPath extends Spizer_Handler_Abstract
{
	/**
	 * Create the new handler object, loading configuration array
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array())
    {
        if (! isset($config['query'])) {
            require_once 'Spizer/Handler/Exception.php';
            throw new Spizer_Handler_Exception('The XPath query was not passed in the configuration array');
        }

        parent::__construct($config);
    }
    
	/**
     * Handle incoming documents
     * 
     * @param Spizer_Document_Xml $document 
     * @see   Spizer_Handler_Abstract::handle()
     */
    public function handle(Spizer_Document $document)
    {
        // Silently ignore non-XML documents
        if (! $document instanceof Spizer_Document_Xml) return;
        
        $query = $this->_config['query'];
        $tags = $document->getXpath()->query($query);
        if ($tags instanceof DOMNodeList) {
            foreach ($tags as $tag) {
                $data = array(
                    'query' => $query,
                );
            
                if (isset($this->_config['message'])) 
                    $data['message'] = $this->_config['message'];
                    
                if (isset($this->_config['captureValue'])) {
                    $value = $document->getXpath()->evaluate($this->_config['captureValue'], $tag);
                    if ($value) {
                        $data['captureValue'] = (string) $value;
                    }
                }
            
                $this->_log($data);
            }
        }
    }
}
