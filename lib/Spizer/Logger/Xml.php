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
    protected $target    = null;
    
    protected $inPage    = false;
    
    protected $indent    = 0;
    
    protected $indentStr = "  ";
    
    /**
     * 
     */
    function __construct($config = array()) 
    {
        if (! isset($config['target'])) $config['target'] = 'php://stdout';
        $mode = ((isset($config['append']) && $config['append']) ? 'a' : 'w');  
        
        if (isset($config['indentstr'])) $this->indentStr = (string) $config['indentstr'];
        
        // Open log file for writing / appending
        $this->target = @fopen($config['target'], $mode);
        if (! $this->target) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception('Cannot open log file "' . $config['target'] . '" for writing');
        }
        
        // Start the XML document
        $this->writeXml('<?xml version="1.0" encoding="UTF-8"?>');
        $this->writeXml('<spizerlog microtime="' . microtime(true) . '">');
        
        ++$this->indent;
    }

    /**
     * 
     * @see Spizer_Logger_Interface::startPage()
     */
    public  function startPage() 
    {
        if ($this->inPage) {
            $this->endPage();
        }
        
        $this->writeXml('<page>');
        $this->inPage = true;
        
        ++$this->indent;
    }
  
	/**
	 *  
     * @see Spizer_Logger_Interface::endPage()
	 */
    public  function endPage() 
    {
        if (! $this->inPage) return;
        
        --$this->indent;
        $this->writeXml('</page>');
        $this->inPage = false;
    }
  
    /**
     * 
     * @see Spizer_Logger_Interface::logHandlerInfo()
     */
    public  function logHandlerInfo($handler, $info = array()) 
    {
          $this->writeXml('<handlerInfo handler="' . htmlentities($handler) . '">');
          ++$this->indent;
          
          foreach($info as $field => $value) {
              $this->writeXml("<$field>" . htmlentities($value) . "</$field>");
          }
          
          --$this->indent;
          $this->writeXml('</handlerInfo>');
    }
  
    /**
     * Log the request object
     * 
     * @param Spizer_Request $request
     * @see   Spizer_Logger_Interface::logRequest()
     */
    public  function logRequest(Spizer_Request $request) 
    {
        $this->writeXml('<request microtime="' . microtime(true) . '">');
        ++$this->indent;
        
        $this->writeXml('<uri>' . htmlentities($request->getUri()) . '</uri>');
        $this->writeXml('<method>' . htmlentities($request->getMethod()) . '</method>');
        
        $ref = $request->getRefererrer();
        if ($ref) $this->writeXml('<referrer>' . htmlentities($ref) . '</referrer>');
        
        foreach($request->getAllHeaders() as $header => $value) {
            $this->writeXml('<header name="' . $header . '">' . htmlentities($value) . "</header>");
        }
        
        --$this->indent;
        $this->writeXml('</request>');
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
        $this->writeXml('<response microtime="' . microtime(true) . '">');
        ++$this->indent;
        
        $this->writeXml('<status>' . $response->getStatus() . '</status>');
        $this->writeXml('<message>' . htmlentities($response->getMessage()) . '</response>');
        
        // Log response headers
        foreach($response->getHeaders() as $header => $value) {
            $this->logHeader($header, $value);
        }
        
        --$this->indent;
        $this->writeXml('</response>');
    }
    
    /**
     * An internal method to log headers (can recurse into itself if it receives 
     * an array of headers)
     *
     * @param  string $key
     * @param  string|array $value
     * @return void
     */
    protected function logHeader($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $v) $this->logHeader($key, $v);
        } else {
            $key = htmlentities($key);
            $this->writeXml('<header name="' . $key . '">' . htmlentities($value) . "</header>");
        }
    }
    
    /**
     * Internal helper to write a line to the XML output
     *
     * @param  string $line
     * @return void
     */
    protected function writeXml($line)
    {
        $line = str_repeat("  ", $this->indent) . $line . "\n";
        fwrite($this->target, $line);
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
        if ($this->inPage) $this->endPage();
        --$this->indent;
        $this->writeXml('</spizerlog>');
        
        // Close file
        fclose($this->target);
    }
}
