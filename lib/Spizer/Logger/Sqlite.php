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

require_once 'Spizer/Logger/Interface.php';
require_once 'Zend/Db.php';

/**
 * Spizer SQLite logger class - will log all information to an SQLite 3.x 
 * database. Will create the database schema if it does not exist, can can 
 * optionally overwrite or append data to the sqlite DB
 * 
 * Requires the PDO SQLITE extension to be loaded into PHP
 * 
 * @package    Spizer
 * @subpackage Logger
 * @author     Shahar Evron, shahar.evron@gmail.com
 * @license    Licensed under the Apache License 2.0, see COPYING for details
 */
class Spizer_Logger_Sqlite implements Spizer_Logger_Interface
{
    /**
     * Database adapter - using Zend_Db
     *
     * @var Zend_Db_Adapter_Pdo_Sqlite
     */
    private $_db = null;
    
    /**
     * Current request ID
     *
     * @var integer
     */
    private $_currentReqId = null;
    
    /**
     * 
     * @see Spizer_Logger_Interface::__construct()
     */
    public function __construct($config = array())
    {
        if (! extension_loaded('pdo_sqlite')) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception("The PDO_SQLITE extension is required in order to use the Sqlite logger");
        }
        
        if (! isset($config['dbfile'])) {
            require_once 'Spizer/Logger/Exception.php';
            throw new Spizer_Logger_Exception("The Sqlite logger requires a real DB output file to be set");
        }
        
        // Instantiate the adapter
        $this->_db = Zend_Db::factory('PDO_SQLITE', array('dbname' => $config['dbfile']));
        
        // Set up the database and tables
        if (! (isset($config['append']) && $config['append'])) {
            $this->_db->query("DROP TABLE IF EXISTS requests");
            $this->_db->query("DROP TABLE IF EXISTS request_headers");
            $this->_db->query("DROP TABLE IF EXISTS responses");
            $this->_db->query("DROP TABLE IF EXISTS response_headers");
            $this->_db->query("DROP TABLE IF EXISTS messages");
        }
        
        $this->_db->query("CREATE TABLE IF NOT EXISTS requests(
        					id INTEGER NOT NULL PRIMARY KEY, 
        					microtime REAL NOT NULL, 
        					url TEXT NOT NULL,
        					referrer TEXT, 
        					method VARCHAR(10) NOT NULL)");
        
        $this->_db->query("CREATE TABLE IF NOT EXISTS request_headers(
        					id INTEGER NOT NULL PRIMARY KEY, 
        					request_id INTEGER NOT NULL, 
        					header VARCHAR(50) NOT NULL, 
        					value TEXT)");
        
        $this->_db->query("CREATE TABLE IF NOT EXISTS responses(
        					id INTEGER NOT NULL PRIMARY KEY, 
        					request_id INTEGER NOT NULL, 
        					microtime REAL NOT NULL, 
        					statuscode INTEGER NOT NULL, 
        					message VARCHAR(30) NOT NULL)");
        
        $this->_db->query("CREATE TABLE IF NOT EXISTS response_headers(
        					id INTEGER NOT NULL PRIMARY KEY, 
        					request_id INTEGER NOT NULL, 
        					header VARCHAR(50) NOT NULL, 
        					value TEXT)");
        
        $this->_db->query("CREATE TABLE IF NOT EXISTS messages(
        					id INTEGER NOT NULL PRIMARY KEY, 
        					request_id INTEGER NOT NULL, 
        					handler VARCHAR(30) NOT NULL, 
        					key VARCHAR(30) NOT NULL, 
        					value TEXT)");

    }
  
    /**
     * Log information after finishing the handling. In this particular logger
     * we do nothing.
     *
     */
    public  function endPage() 
    {
        // Do nothing
    }
  
    /**
     * Log messages coming in from handlers
     *
     * @param string $handler Handler name
     * @param array  $info    Array of key => value information
     */
    public  function logHandlerInfo($handler,$info = array())
    {
        $handler = $this->_db->quote($handler);
        $stmt = $this->_db->prepare("INSERT INTO messages (request_id, handler, key, value) VALUES ({$this->_currentReqId}, {$handler}, ?, ?)");
        foreach ($info as $k => $v) {
            $stmt->execute(array($k, $v));
        }
    }
  
    /**
     * Log request information
     *
     * @param Spizer_Request $request
     */
    public  function logRequest(Spizer_Request $request) 
    {
        $this->_db->insert('requests', array(
            'microtime' => microtime(true),
            'url'       => $request->getUri(),
            'referrer'  => $request->getReferrer(),
            'method'    => $request->getMethod()
        ));
        
        $this->_currentReqId = $this->_db->lastInsertId('requests', 'id');
        
        $stmt = $this->_db->prepare("INSERT INTO request_headers (request_id, header, value) VALUES ({$this->_currentReqId}, ?, ?)");
        foreach ($request->getAllHeaders() as $k => $v) {
            $stmt->execute(array($k, $v));
        }
    }
  
    /**
     * Log response information
     *
     * @param Spizer_Response $response
     */
    public  function logResponse(Spizer_Response $response) 
    {
        $this->_db->insert('responses', array(
            'microtime'  => microtime(true),
            'request_id' => $this->_currentReqId,
            'statuscode' => $response->getStatus(),
            'message'    => $response->getMessage()
        ));
        
        $stmt = $this->_db->prepare("INSERT INTO response_headers (request_id, header, value) VALUES ({$this->_currentReqId}, ?, ?)");
        foreach ($response->getAllHeaders() as $k => $v) {
            $stmt->execute(array($k, $v));
        }
    }
  
    /**
     * Log information before begging request. In this particular logger we do nothing
     *
     */
    public  function startPage() 
    {
        // Do nothing
    }
}
