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
 * The link appender handler provides the basic Spizer ability to extract
 * new targets out of received HTML documents and add them to the queue. The
 * handler also keeps track of already queued and visited URLs, and avoids
 * adding them to the queue again.   
 * 
 * Most Spizer-based applications will want to either use this handler, or a 
 * different handler extending it.
 * 
 * @todo       Implement additional filtering rules (exclude URLs, etc.)
 * 
 * @package    Spizer
 * @subpackage Handler
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Handler_LinkAppender extends Spizer_Handler_Abstract 
{
    private $targets = array();
    
    protected $config = array(
        'status'        => null,
        'content-type'  => null,
        'follow_href'   => true,
        'follow_img'    => false,
        'follow_link'   => false,
        'follow_script' => false,
        'same-domain'   => false
    );
    
    /**
     * Handle document - fetch links out of the document and add them to the 
     * queue
     *
     * @param Spizer_Document_Html $doc
     */
    public function handle(Spizer_Document $doc)
    {
        // If need, set the match domain according to the first URL
        if (! isset($this->config['domain']) && $this->config['same-domain']) {
            $this->config['domain'] = $this->engine->getBaseUri()->getHost();
        }
        
        // Add document URL to the list of visited pages
        $baseUrl = (string) $doc->getUrl();
        if (! in_array($baseUrl, $this->targets)) $this->targets[] = $baseUrl;
        
        // Silently skip all non-HTML documents
        if (! $doc instanceof Spizer_Document_Html) return;
        
        // Fetch links out of the document
        $links = array();
        if ($this->config['follow_href'])   $links = array_merge($links, $doc->getLinks()); 
        if ($this->config['follow_img'])    $links = array_merge($links, $doc->getImages());
        if ($this->config['follow_link'])   $links = array_merge($links, $doc->getHeaderLinks());
        if ($this->config['follow_script']) $links = array_merge($links, $doc->getScriptLinks());
        
        // Iterate over all document links
		foreach ($links as $link) {
		    // Try to parse URL - if we fail, skip this link (should not happen normally)
			if (! ($parts = @parse_url($link))) continue;
			
			// Skip non-http schemes
			if (isset($parts['scheme']) &&  
		       ($parts['scheme'] != 'http' && $parts['scheme'] != 'https')) continue;
			
			// Full URI
			if (isset($parts['host'])) { 
				if (preg_match('/' . preg_quote($this->config['domain']) . '$/', $parts['host'])) {
				    $this->addToQueue($link, $baseUrl);
				}

		    // Partial URI
			} elseif (isset($parts['path'])) {
			    try {
				    $linkUri = clone $doc->getUrl(); 
				    $linkUri->setQuery(isset($parts['query']) ? $parts['query'] : null);
				    $linkUri->getFragment(isset($parts['fragment']) ? $parts['fragment'] : null);
					
				    // Full absolute path
				    if (substr_compare($parts['path'], '/', 0, 1) == 0) {
					    $linkUri->setPath($parts['path']);
					    
					// Relative path
				    } else {
					    $basePath = $doc->getUrl()->getPath();
					    $pos = strrpos($basePath, '/');
					    if ($pos === false) {
						    $linkUri->setPath('/' . $parts['path']);
					    } else {
						    $linkUri->setPath(substr($basePath, 0, $pos + 1) . $parts['path']);
					    }
				    }

				    $this->addToQueue($linkUri, $baseUrl);
				    
				// If any of the URL parts is invalid, an exception will be caught here
			    } catch (Zend_Uri_Exception $e) {
			        $this->engine->log('LinkAppender', array(
			            'link'    => $link,  
			            'message' => 'Unable to parse link URL: ' . $e->getMessage()
			        ));
			    }
			}
		}
    }
    
    /**
     * Add a URL to the engine queue
     *
     * @param Zend_Uri_Http|string $url
     */
    private function addToQueue($url, $referrer)
    {
        $url = (string) $url;
        
        if (! in_array($url, $this->targets)) {
            $request = new Spizer_Request($url);
            $request->setReferrer($referrer);
            $this->engine->getQueue()->append($request);
            
            $this->targets[] = $url;
        }
    }
}
