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

require_once 'Spizer/Logger/Interface.php';

/**
 * Spizer XML logger class 
 * 
 * Will log all information to a file (or to any other PHP stream) in XML 
 * format. Requires the XMLWriter PHP extension to be loaded. 
 * 
 * 
 * @package    Spizer
 * @subpackage Logger
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Logger_Xml implements Spizer_Logger_Interface
{
    /**
     * XMLWriter object
     * 
     * @var XMLWriter
     */
    protected $_writer;
    
    /**
     * Configuration options
     * 
     * @var array
     */
    protected $_config = array(
        'target'     => 'php://stdout',
        'logheaders' => true,
        'indent'     => true,
        'indentstr'  => '  '
    );
    
    /**
     * 
     */
    function __construct($config = array()) 
    {
        if (! class_exists('XMLWriter')) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception('The XMLWriter PHP extension is not loaded');
        }
        
        $this->_config = array_merge($this->_config, $config);
        
        $this->_writer = new XMLWriter();
        if (! $this->_writer->openUri($this->_config['target'])) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception('Cannot open log file "' . $this->_config['target'] . '" for writing');
        }
        
        $this->_writer->setIndent($this->_config['indent']);
        $this->_writer->setIndentString($this->_config['indentstr']);
        
        $this->_writer->startDocument('1.0', 'UTF-8');
        $this->_writer->startElement('spizerlog');
        $this->_writer->writeAttribute('xmlns', 'http://arr.gr/spizer/xmllog/1.0');
        $this->_writer->writeAttribute('microtime', microtime(true));
    }

    /**
     *  
     * @see Spizer_Logger_Interface::startPage()
     */
    public  function startPage() 
    {
        $this->_writer->startElement('page');
    }
  
	/**
	 *  
     * @see Spizer_Logger_Interface::endPage()
	 */
    public  function endPage() 
    {
        $this->_writer->endElement(); // page
    }
  
    /**
     * 
     * @see Spizer_Logger_Interface::logHandlerInfo()
     */
    public  function logHandlerInfo($handler, $info = array()) 
    {
        $this->_writer->startElement('handlerInfo');
        $this->_writer->writeAttribute('handler', $handler);

        foreach($info as $field => $value) {
            $this->_writer->writeElement($field, $value);
        }
        
        $this->_writer->endElement(); // handlerInfo
    }
  
    /**
     * Log the request object
     * 
     * @param Spizer_Request $request
     * @see   Spizer_Logger_Interface::logRequest()
     */
    public  function logRequest(Spizer_Request $request) 
    {
        $this->_writer->startElement('request');
        $this->_writer->writeAttribute('microtime', microtime(true));
        
        $this->_writer->writeElement('uri', $request->getUri());
        $this->_writer->writeElement('method', $request->getMethod());
        
        $ref = $request->getReferrer();
        if ($ref) $this->_writer->writeElement('referrer', $ref);
        
        if ($this->_config['logheaders']) {
            foreach($request->getAllHeaders() as $header => $value) {
                $this->_logHeader($header, $value);
            }
        }

        $this->_writer->endElement(); // request
    }
  
    /**
     * Log the response received from server
     * 
     * @param  Spizer_Response $response
     * @see    Spizer_Logger_Interface::logResponse()
     * @return void
     */
    public  function logResponse(Spizer_Response $response) 
    {
        $this->_writer->startElement('response');
        $this->_writer->writeAttribute('microtime', microtime(true));

        $this->_writer->writeElement('status', $response->getStatus());
        $this->_writer->writeElement('message', $response->getMessage());
        
        // Log response headers
        if ($this->_config['logheaders']) {
            foreach($response->getHeaders() as $header => $value) {
                $this->_logHeader($header, $value);
            }
        }

        $this->_writer->endElement(); // response
    }
    
    /**
     * An internal method to log headers (can recurse into itself if it receives 
     * an array of headers)
     *
     * @param  string $key
     * @param  string|array $value
     * @return void
     */
    protected function _logHeader($header, $value)
    {
        if (is_array($value)) {
            foreach ($value as $v) $this->_logHeader($header, $v);
        } else {
            $this->_writer->startElement('header');
            $this->_writer->writeAttribute('name', $header);
            $this->_writer->text($value);
            $this->_writer->endElement(); // header
        }
    }
    
    /**
     * Destructor - makes sure to properly close the XML if the logger object 
     * is destroyed 
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_writer->endDocument();
        $this->_writer->flush();
    }
}
