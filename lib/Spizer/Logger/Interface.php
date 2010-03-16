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
 * Spizer logger object interface
 * 
 * Logger objects are used by spizer to collect and store crawling information
 * such request and response attributes and messages coming in from handler
 * objects
 *  
 * All Spizer logger objects (XML logger, DB logger etc.) are required to 
 * implement this interface.
 * 
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @package    Spizer
 * @subpackage Logger
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
interface Spizer_Logger_Interface
{
    public function __construct($config = array());
    
    public function startPage();
    
    public function logRequest(Spizer_Request $request);
    
    public function logResponse(Spizer_Response $response);
    
    public function logHandlerInfo($handler, $info = array());
    
    public function endPage();
}
