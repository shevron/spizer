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

require_once 'Spizer/Request.php';

/**
 * Spizer queue class - a container for the queue of URLs to be crawled by the
 * engine  
 * 
 * The queue class contains the list of pending targets to crawl - and also 
 * serves then in particular order to the engine upon request. URLs can be 
 * appended to the queue transparently, and will be served to the engine in one
 * of two orders: FIFO (default) or LIFO, depending on how the queue object was
 * set up.  
 * 
 * FIFO will serve the longest pending URL first - this will result in a flat
 * crawling patten, going deep in the tree only after the "root" elements are 
 * crawled. 
 * 
 * LIFO crawling will serve the newest pending URL first - the result is a 
 * deep crawling pattern, in which the engine will finish crawling all the 
 * dependencies of one document before moving to the next one.
 * 
 * The queue object implements the SPL Countable interface, allowing it to be 
 * passed to the count() function - this will return the number of pending URLs 
 *    
 * @package    Spizer
 * @subpackage Core
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Queue implements Countable
{
    /**
     * Array of targets 
     *
     * @var array
     */
    private $_targets = array();
    
    /**
     * LIFO flag - if set to true, the queue is in fact a stack, serving 
     * targets in last in first out basis  
     *
     * @var boolean
     */
    private $_lifo    = false;
    
    /**
     * Create a new queue object
     *
     * @param array $targets Array of targets
     */
    public function __construct($lifo = false)
    {
        $this->_lifo = (boolean) $lifo;
    }
        
    /**
     * Get the count of pending targets.
     *
     * @return integer
     */
    public function count() 
    {
        return count($this->_targets);
    }
    
    /**
     * Get the next target in queue, depending on the FIFO flag
     *
     * @return Spizer_Request|null will return null if nothing more to serve
     */
    public function next()
    {
        if ($this->_lifo) {
            return array_pop($this->_targets);
        } else {
            return array_shift($this->_targets);
        }
    }
    
	/**
	 * Append a new Spizer_Request to the queue. Will also accept valid URLs
	 * which will automatically be converted into a Spizer_Request object
	 *
	 * @param Spizer_Request|string $value URL
	 */
	public function append($value) 
	{
	    if (! $value instanceof Spizer_Request) {
		    $value = new Spizer_Request((string) $value);
	    }
		
	    $this->_targets[] = $value;
	}
}
