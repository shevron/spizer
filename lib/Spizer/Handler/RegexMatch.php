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
 * The Regular Expression Matching handler will try to match the document body
 * to a PCRE provided by the user, and will log the matching documents
 * 
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_RegexMatch extends Spizer_Handler_StringMatch
{
	/**
	 * Create the new handler object, loading configuration array
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array())
    {
        if (! isset($config['match']) || ! is_string($config['match'])) {
            require_once 'Spizer/Handler/Exception.php';
            throw new Spizer_Handler_Exception('The regex to match was not passed in the configuration array');
        }
        
        // Check that the regex is valid
        if ((@preg_match($config['match'], 'test') === false)) {
            require_once 'Spizer/Handler/Exception.php';
            throw new Spizer_Handler_Exception('The provided regex is not valid: "' . $config['match'] . '"');
        }

        parent::__construct($config);
    }
    
	/**
     * Handle incoming documents
     * 
     * @param Spizer_Document $document 
     * @see   Spizer_Handler_Abstract::handle()
     */
    public function handle(Spizer_Document $document)
    {
        if (preg_match($this->_config['match'], $document->getBody(), $m, PREG_OFFSET_CAPTURE)) {
            $this->_log(array(
                'message' => 'Document body matched lookup expression',
            	'regex'   => $this->_config['match'],
                'match'   => $m[0][0],
                'offset'  => $m[0][1]
            ));
        }
    }
}
