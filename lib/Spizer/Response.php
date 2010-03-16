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

require_once 'Zend/Http/Response.php';

/**
 * Spizer_Response objects represent the HTTP response message received from 
 * the server, providing easy access to properties like the response status
 * code, headers and body. 
 * 
 * Currently Spizer_Response is just an encapsulation of Zend_Http_Response, 
 * which works quite well for now.
 * 
 * @see        Zend_Http_Response
 * 
 * @package    Spizer
 * @subpackage Core
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Response
{
    protected $_response = null;
    
    protected $_headers  = null;
    
    public function __construct(Zend_Http_Response $response)
    {
        $this->_response = $response;
    }
    
    public function getAllHeaders()
    {
        if ($this->_headers == null) {
            foreach ($this->_response->getHeaders() as $k => $v) {
                $this->_headers[strtolower($k)] = $v;
            }
        }
        
        return $this->_headers;
    }
    
    public function __call($method, $args)
    {
        return call_user_func(array($this->_response, $method), $args);
    }
}
