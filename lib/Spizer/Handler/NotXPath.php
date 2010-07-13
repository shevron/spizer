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

require_once 'Spizer/Handler/XPath.php';

/**
 * The NotXPath handler is very similar to the Spizer_Handler_XPath handler, 
 * with one notable difference: It will log documents that *do not* match the
 * provided XPath queries. 
 * 
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_NotXPath extends Spizer_Handler_XPath
{

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
        if ($tags->length == 0) {
            $data = array('query' => $query);
            if (isset($this->_config['message'])) $data['message'] = $this->_config['message'];
            $this->_log($data);
        }
    }
}

