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

require_once 'Spizer/Document/Xml.php';

/**
 * Spizer HTML document object - Inherits from the XML document but also adds
 * (X)HTML-specific accessors such as fetching all forms or image references 
 * out of the document 
 *
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @package    Spizer
 * @subpackage Document
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Document_Html extends Spizer_Document_Xml
{
	protected $_links       = null;
	
	protected $_images      = null;
	
	protected $_headerlinks = null;
	
	protected $_scripts     = null;
	
	protected $_forms       = null;
	
	protected $_frames      = null;
	
	/**
	 * Get all <a href=""> links out of this document
	 *
	 * @todo   Support JavaScript links (window.location=...)
	 * @return array Array of link URLs
	 */
	public function getLinks()
	{
		if ($this->_links === null) {
			$this->_links = array();
			$links = $this->getXpath()->query("//a[@href]");
		
			foreach ($links as $link) {
				$this->_links[] = $link->getAttribute('href');
			}
		}
		
		return $this->_links; 
	}
	
	/**
	 * Get all <link href=""> URLs usually found in the <head> section of the
	 * HTML (stylesheets, feeds, etc.)
	 *
	 * @todo   Add support for CSS @import references
	 * @return array
	 */
	public function getHeaderLinks()
	{
	    if ($this->_headerlinks === null) {
	        $this->_headerlinks = array();
	        $links = $this->getXpath()->query("//link[@href]");
		
			foreach ($links as $link) {
				$this->_headerlinks[] = $link->getAttribute('href');
			}
	    }
	    
	    return $this->_headerlinks;
	}
	
	/**
	 * Get all images <img src=""> URLs referenced out of this document
	 *
	 * @return array Array of image URLs
	 */
	public function getImages()
	{
	    if ($this->_images === null) {
	        $this->_images = array();
	        $images = $this->getXpath()->query("//img[@src]");
	        
	        foreach ($images as $img) {
	            $this->_images[] = $img->getAttribute('src');
	        }
	    }
	    
	    return $this->_images;
	}
	
	/**
	 * Get all <script src=""> external URL references 
	 *
	 * @return array Array of script URLs
	 */
	public function getScriptLinks()
	{
	    if ($this->_scripts === null) {
	        $this->_scripts = array();
	        $scripts = $this->getXpath()->query("//script[@src]");
	        
	        foreach ($scripts as $script) {
	            $this->_scripts[] = $script->getAttribute('src');
	        }
	    }
	    
	    return $this->_scripts;
	}
	
	/**
	 * Get all <frame src=""> external URL references
	 * 
	 * @return array Array of frame URLs
	 */
	public function getFrameLinks()
	{
	    if ($this->_frames === null) {
	        $this->_frames = array();
	        $frames = $this->getXpath()->query('//frameset/frame[@src]');

	        foreach($frames as $frame) {
	            $this->_frames[] = $frame->getAttribute('src');
	        }
	    }
	    
	    return $this->_frames;
	}
}
