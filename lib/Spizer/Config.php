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

class Spizer_Config
{
    /**
     * Configuration file types
     */
    const YAML = 'yaml';
    const INI  = 'ini';
    const XML  = 'xml';
    
    /**
     * Load configuration file by it's type and put it into a Zend_Config object
     *
     * @param  string $configFile Configuration file path
     * @param  string $fileType   Configuration file type
     * @param  string $section    Configuration section to load
     * @return Zend_Config
     */
    static public function load($configFile, $fileType = self::YAML, $section = 'default')
    {
        switch ($fileType) {
            case self::YAML:
                $yaml = file_get_contents($configFile);
                if (extension_loaded('syck')) {
                    $data = syck_load($yaml);
                } else {
                    require_once 'Spyc.php';
                    $data = Spyc::YAMLLoad($yaml);
                }
                
                require_once 'Zend/Config.php';
                return new Zend_Config($data[$section]);
                break;
                
            case self::INI:
                require_once 'Zend/Config/Ini.php';
                return new Zend_Config_Ini($configFile, $section);
                break;
                
            case self::XML:
                require_once 'Zend/Config/Xml.php';
                return new Zend_Config_Xml($configFile, $section);
                break;
                
            default:
                require_once 'Spizer/Exception.php';
                throw new Spizer_Exception("Configuration files of type '$fileType' are not (yet?) supported");
                break;
        }
    }
}
