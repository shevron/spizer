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
 * Spizer XML logger class - will log all information to a file (or to any 
 * other PHP stream) in XML format. 
 * 
 * For simplicity and memory usage reasons, this logger does not use any PHP
 * XML processing interface - but manually prints out the XML as a string,
 * saving memory and processing overhead 
 * 
 * @todo       Rewrite to use XMLWriter
 * 
 * @package    Spizer
 * @subpackage Logger
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Logger_Xml implements Spizer_Logger_Interface
{
    protected $_target    = null;
    
    protected $_inPage    = false;
    
    protected $_indent    = 0;
    
    protected $_indentStr = "  ";
    
    /**
     * 
     */
    function __construct($config = array()) 
    {
        if (! isset($config['target'])) $config['target'] = 'php://stdout';
        $mode = ((isset($config['append']) && $config['append']) ? 'a' : 'w');  
        
        if (isset($config['indentstr'])) $this->_indentStr = (string) $config['indentstr'];
        
        // Open log file for writing / appending
        $this->_target = @fopen($config['target'], $mode);
        if (! $this->_target) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception('Cannot open log file "' . $config['target'] . '" for writing');
        }
        
        // Start the XML document
        $this->_writeXml('<?xml version="1.0" encoding="UTF-8"?>');
        $this->_writeXml('<spizerlog microtime="' . microtime(true) . '">');
        
        ++$this->_indent;
    }

    /**
     * 
     * @see Spizer_Logger_Interface::startPage()
     */
    public  function startPage() 
    {
        if ($this->_inPage) {
            $this->endPage();
        }
        
        $this->_writeXml('<page>');
        $this->_inPage = true;
        
        ++$this->_indent;
    }
  
	/**
	 *  
     * @see Spizer_Logger_Interface::endPage()
	 */
    public  function endPage() 
    {
        if (! $this->_inPage) return;
        
        --$this->_indent;
        $this->_writeXml('</page>');
        $this->_inPage = false;
    }
  
    /**
     * 
     * @see Spizer_Logger_Interface::logHandlerInfo()
     */
    public  function logHandlerInfo($handler, $info = array()) 
    {
          $this->_writeXml('<handlerInfo handler="' . htmlentities($handler) . '">');
          ++$this->_indent;
          
          foreach($info as $field => $value) {
              $this->_writeXml("<$field>" . htmlentities($value) . "</$field>");
          }
          
          --$this->_indent;
          $this->_writeXml('</handlerInfo>');
    }
  
    /**
     * Log the request object
     * 
     * @param Spizer_Request $request
     * @see   Spizer_Logger_Interface::logRequest()
     */
    public  function logRequest(Spizer_Request $request) 
    {
        $this->_writeXml('<request microtime="' . microtime(true) . '">');
        ++$this->_indent;
        
        $this->_writeXml('<uri>' . htmlentities($request->getUri()) . '</uri>');
        $this->_writeXml('<method>' . htmlentities($request->getMethod()) . '</method>');
        
        $ref = $request->getReferrer();
        if ($ref) $this->_writeXml('<referrer>' . htmlentities($ref) . '</referrer>');
        
        foreach($request->getAllHeaders() as $header => $value) {
            $this->_writeXml('<header name="' . $header . '">' . htmlentities($value) . "</header>");
        }
        
        --$this->_indent;
        $this->_writeXml('</request>');
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
        $this->_writeXml('<response microtime="' . microtime(true) . '">');
        ++$this->_indent;
        
        $this->_writeXml('<status>' . $response->getStatus() . '</status>');
        $this->_writeXml('<message>' . htmlentities($response->getMessage()) . '</response>');
        
        // Log response headers
        foreach($response->getHeaders() as $header => $value) {
            $this->_logHeader($header, $value);
        }
        
        --$this->_indent;
        $this->_writeXml('</response>');
    }
    
    /**
     * An internal method to log headers (can recurse into itself if it receives 
     * an array of headers)
     *
     * @param  string $key
     * @param  string|array $value
     * @return void
     */
    protected function _logHeader($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $v) $this->_logHeader($key, $v);
        } else {
            $key = htmlentities($key);
            $this->_writeXml('<header name="' . $key . '">' . htmlentities($value) . "</header>");
        }
    }
    
    /**
     * Internal helper to write a line to the XML output
     *
     * @param  string $line
     * @return void
     */
    protected function _writeXml($line)
    {
        $line = str_repeat("  ", $this->_indent) . $line . "\n";
        fwrite($this->_target, $line);
    }
    
    /**
     * Destructor - makes sure to properly close the XML if the logger object 
     * is destroyed 
     *
     * @return void
     */
    public function __destruct()
    {
        // Close XML
        if ($this->_inPage) $this->endPage();
        --$this->_indent;
        $this->_writeXml('</spizerlog>');
        
        // Close file
        fclose($this->_target);
    }
}
