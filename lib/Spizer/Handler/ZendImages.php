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

require_once 'Spizer/Handler/Abstract.php';

/**
 * Zend specific handler - find all images that contain underscores in their 
 * file name. Should not be a part of the final Spizer distribution.
 * 
 * @todo       Find a good way to seperate core Spizer handler objects from 
 *             user-defined ones. 
 * 
 * @package    Spizer
 * @subpackage Handler
 * @category   ZendSpecific
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_ZendImages extends Spizer_Handler_Abstract
{
    /**
     * Handle incoming documents
     * 
     * @param Spizer_Document_Html $document 
     * @see   Spizer_Handler_Abstract::handle()
     */
    public function handle(Spizer_Document $document)
    {
        // Silently ignore non-HTML documents
        if (! $document instanceof Spizer_Document_Html) return;
        
        $images = $document->getImages();
        foreach ($images as $img) {
            $img = basename($img);
            if (strpos($img, '_') !== false) {
                // Image file name contains underscore
                $this->engine->log('ZendImages', array(
                    'message' => 'Image contains underscore in it\'s file name',
                    'src'     => $img
                ));
            }
        }
    }
}
