<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class manages the access to the database. It basically is a wrapper for
 * MDB2 with some adaptions.
 */
class OC_DB {
	const BACKEND_PDO=0;
	const BACKEND_MDB2=1;

	/**
	 * @var MDB2_Driver_Common
	 */
	static private $connection; //the prefered connection to use, either PDO or MDB2
	static private $backend=null;
	/**
	 * @var MDB2_Driver_Common
	 */
	static private $MDB2=null;
	/**
	 * @var PDO
	 */
	static private $PDO=null;
	/**
	 * @var MDB2_Schema
	 */
	static private $schema=null;
	static private $inTransaction=false;
	static private $prefix=null;
	static private $type=null;

	/**
	 * check which backend we should use
	 * @return int BACKEND_MDB2 or BACKEND_PDO
	 */
	private static function getDBBackend() {
		//check if we can use PDO, else use MDB2 (installation always needs to be done my mdb2)
		if(class_exists('PDO') && OC_Config::getValue('installed', false)) {
			$type = OC_Config::getValue( "dbtype", "sqlite" );
			if($type=='oci') { //oracle also always needs mdb2
				return self::BACKEND_MDB2;
			}
			if($type=='sqlite3') $type='sqlite';
			$drivers=PDO::getAvailableDrivers();
			if(array_search($type, $drivers)!==false) {
				return self::BACKEND_PDO;
			}
		}
		return self::BACKEND_MDB2;
	}

	/**
	 * @brief connects to the database
	 * @param int $backend
	 * @return bool true if connection can be established or false on error
	 *
	 * Connects to the database as specified in config.php
	 */
	public static function connect($backend=null) {
		if(self::$connection) {
			return true;
		}
		if(is_null($backend)) {
			$backend=self::getDBBackend();
		}
		if($backend==self::BACKEND_PDO) {
			$success = self::connectPDO();
			self::$connection=self::$PDO;
			self::$backend=self::BACKEND_PDO;
		}else{
			$success = self::connectMDB2();
			self::$connection=self::$MDB2;
			self::$backend=self::BACKEND_MDB2;
		}
		return $success;
	}

	/**
	 * connect to the database using pdo
	 *
	 * @return bool
	 */
	public static function connectPDO() {
		if(self::$connection) {
			if(self::$backend==self::BACKEND_MDB2) {
				self::disconnect();
			}else{
				return true;
			}
		}
		// The global data we need
		$name = OC_Config::getValue( "dbname", "owncloud" );
		$host = OC_Config::getValue( "dbhost", "" );
		$user = OC_Config::getValue( "dbuser", "" );
		$pass = OC_Config::getValue( "dbpassword", "" );
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		if(strpos($host, ':')) {
			list($host, $port)=explode(':', $host,2);
		}else{
			$port=false;
		}
		$opts = array();
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );

		// do nothing if the connection already has been established
		if(!self::$PDO) {
			// Add the dsn according to the database type
			switch($type) {
				case 'sqlite':
					$dsn='sqlite2:'.$datadir.'/'.$name.'.db';
					break;
				case 'sqlite3':
					$dsn='sqlite:'.$datadir.'/'.$name.'.db';
					break;
				case 'mysql':
					if($port) {
						$dsn='mysql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='mysql:dbname='.$name.';host='.$host;
					}
					$opts[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
					break;
				case 'pgsql':
					if($port) {
						$dsn='pgsql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='pgsql:dbname='.$name.';host='.$host;
					}
					/**
					* Ugly fix for pg connections pbm when password use spaces
					*/
					$e_user = addslashes($user);
					$e_password = addslashes($pass);
					$pass = $user = null;
					$dsn .= ";user='$e_user';password='$e_password'";
					/** END OF FIX***/
					break;
				case 'oci': // Oracle with PDO is unsupported
					if ($port) {
							$dsn = 'oci:dbname=//' . $host . ':' . $port . '/' . $name;
					} else {
							$dsn = 'oci:dbname=//' . $host . '/' . $name;
					}
					break;
				default:
					return false;
			}
			try{
				self::$PDO=new PDO($dsn, $user, $pass, $opts);
			}catch(PDOException $e) {
				echo( '<b>can not connect to database, using '.$type.'. ('.$e->getMessage().')</center>');
				die();
			}
			// We always, really always want associative arrays
			self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		return true;
	}

	/**
	 * connect to the database using mdb2
	 */
	public static function connectMDB2() {
		if(self::$connection) {
			if(self::$backend==self::BACKEND_PDO) {
				self::disconnect();
			}else{
				return true;
			}
		}
		// The global data we need
		$name = OC_Config::getValue( "dbname", "owncloud" );
		$host = OC_Config::getValue( "dbhost", "" );
		$user = OC_Config::getValue( "dbuser", "" );
		$pass = OC_Config::getValue( "dbpassword", "" );
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		$SERVERROOT=OC::$SERVERROOT;
		$datadir=OC_Config::getValue( "datadirectory", "$SERVERROOT/data" );

		// do nothing if the connection already has been established
		if(!self::$MDB2) {
			// Require MDB2.php (not required in the head of the file so we only load it when needed)
			require_once 'MDB2.php';

			// Prepare options array
			$options = array(
			  'portability' => MDB2_PORTABILITY_ALL - MDB2_PORTABILITY_FIX_CASE,
			  'log_line_break' => '<br>',
			  'idxname_format' => '%s',
			  'debug' => true,
			  'quote_identifier' => true  );

			// Add the dsn according to the database type
			switch($type) {
				case 'sqlite':
				case 'sqlite3':
					$dsn = array(
						'phptype'  => $type,
						'database' => "$datadir/$name.db",
						'mode' => '0644'
					);
					break;
				case 'mysql':
					$dsn = array(
						'phptype'  => 'mysql',
						'username' => $user,
						'password' => $pass,
						'hostspec' => $host,
						'database' => $name
					);
					break;
				case 'pgsql':
					$dsn = array(
						'phptype'  => 'pgsql',
						'username' => $user,
						'password' => $pass,
						'hostspec' => $host,
						'database' => $name
					);
					break;
				case 'oci':
					$dsn = array(
							'phptype'  => 'oci8',
							'username' => $user,
							'password' => $pass,
							'charset' => 'AL32UTF8',
					);
					if ($host != '') {
						$dsn['hostspec'] = $host;
						$dsn['database'] = $name;
					} else { // use dbname for hostspec
						$dsn['hostspec'] = $name;
						$dsn['database'] = $user;
					}
					break;
				default:
					return false;
			}

			// Try to establish connection
			self::$MDB2 = MDB2::factory( $dsn, $options );

			// Die if we could not connect
			if( PEAR::isError( self::$MDB2 )) {
				echo( '<b>can not connect to database, using '.$type.'. ('.self::$MDB2->getUserInfo().')</center>');
				OC_Log::write('core', self::$MDB2->getUserInfo(), OC_Log::FATAL);
				OC_Log::write('core', self::$MDB2->getMessage(), OC_Log::FATAL);
				die();
			}

			// We always, really always want associative arrays
			self::$MDB2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		}

		// we are done. great!
		return true;
	}

	/**
	 * @brief Prepare a SQL query
	 * @param string $query Query string
	 * @param int $limit
	 * @param int $offset
	 * @return MDB2_Statement_Common prepared SQL query
	 *
	 * SQL query via MDB2 prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query , $limit=null, $offset=null ) {

		if (!is_null($limit) && $limit != -1) {
			if (self::$backend == self::BACKEND_MDB2) {
				//MDB2 uses or emulates limits & offset internally
				self::$MDB2->setLimit($limit, $offset);
			} else {
				//PDO does not handle limit and offset.
				//FIXME: check limit notation for other dbs
				//the following sql thus might needs to take into account db ways of representing it
				//(oracle has no LIMIT / OFFSET)
				$limit = (int)$limit;
				$limitsql = ' LIMIT ' . $limit;
				if (!is_null($offset)) {
					$offset = (int)$offset;
					$limitsql .= ' OFFSET ' . $offset;
				}
				//insert limitsql
				if (substr($query, -1) == ';') { //if query ends with ;
					$query = substr($query, 0, -1) . $limitsql . ';';
				} else {
					$query.=$limitsql;
				}
			}
		}

		// Optimize the query
		$query = self::processQuery( $query );

		self::connect();
		// return the result
		if(self::$backend==self::BACKEND_MDB2) {
			$result = self::$connection->prepare( $query );

			// Die if we have an error (error means: bad query, not 0 results!)
			if( PEAR::isError($result)) {
				$entry = 'DB Error: "'.$result->getMessage().'"<br />';
				$entry .= 'Offending command was: '.htmlentities($query).'<br />';
				OC_Log::write('core', $entry,OC_Log::FATAL);
				error_log('DB error: '.$entry);
				die( $entry );
			}
		}else{
			try{
				$result=self::$connection->prepare($query);
			}catch(PDOException $e) {
				$entry = 'DB Error: "'.$e->getMessage().'"<br />';
				$entry .= 'Offending command was: '.htmlentities($query).'<br />';
				OC_Log::write('core', $entry,OC_Log::FATAL);
				error_log('DB error: '.$entry);
				die( $entry );
			}
			$result=new PDOStatementWrapper($result);
		}
		return $result;
	}

	/**
	 * @brief gets last value of autoincrement
	 * @param string $table The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @return int id
	 *
	 * MDB2 lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null) {
		self::connect();
		if($table !== null) {
			$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
			$suffix = OC_Config::getValue( "dbsequencesuffix", "_id_seq" );
			$table = str_replace( '*PREFIX*', $prefix, $table ).$suffix;
		}
		return self::$connection->lastInsertId($table);
	}

	/**
	 * @brief Disconnect
	 * @return bool
	 *
	 * This is good bye, good bye, yeah!
	 */
	public static function disconnect() {
		// Cut connection if required
		if(self::$connection) {
			if(self::$backend==self::BACKEND_MDB2) {
				self::$connection->disconnect();
			}
			self::$connection=false;
			self::$MDB2=false;
			self::$PDO=false;
		}

		return true;
	}

	/**
	 * @brief saves database scheme to xml file
	 * @param string $file name of file
	 * @param int $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file ,$mode=MDB2_SCHEMA_DUMP_STRUCTURE) {
		self::connectScheme();

		// write the scheme
		$definition = self::$schema->getDefinitionFromDatabase();
		$dump_options = array(
			'output_mode' => 'file',
			'output' => $file,
			'end_of_line' => "\n"
		);
		self::$schema->dumpDatabase( $definition, $dump_options, $mode );

		return true;
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $file ) {
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		/* FIXME: use CURRENT_TIMESTAMP for all databases. mysql supports it as a default for DATETIME since 5.6.5 [1]
		 * as a fallback we could use <default>0000-01-01 00:00:00</default> everywhere
		 * [1] http://bugs.mysql.com/bug.php?id=27645
		 * http://dev.mysql.com/doc/refman/5.0/en/timestamp-initialization.html
		 * http://www.postgresql.org/docs/8.1/static/functions-datetime.html
		 * http://www.sqlite.org/lang_createtable.html
		 * http://docs.oracle.com/cd/B19306_01/server.102/b14200/functions037.htm
		 */
		 if( $CONFIG_DBTYPE == 'pgsql' ) { //mysql support it too but sqlite doesn't
				 $content = str_replace( '<default>0000-00-00 00:00:00</default>', '<default>CURRENT_TIMESTAMP</default>', $content );
		 }

		file_put_contents( $file2, $content );

		// Try to create tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );

		//clean up memory
		unlink( $file2 );

		// Die in case something went wrong
		if( $definition instanceof MDB2_Schema_Error ) {
			die( $definition->getMessage().': '.$definition->getUserInfo());
		}
		if(OC_Config::getValue('dbtype', 'sqlite')==='oci') {
			unset($definition['charset']); //or MDB2 tries SHUTDOWN IMMEDIATE
			$oldname = $definition['name'];
			$definition['name']=OC_Config::getValue( "dbuser", $oldname );
		}

		$ret=self::$schema->createDatabase( $definition );

		// Die in case something went wrong
		if( $ret instanceof MDB2_Error ) {
			echo (self::$MDB2->getDebugOutput());
			die ($ret->getMessage() . ': ' . $ret->getUserInfo());
		}

		return true;
	}

	/**
	 * @brief update the database scheme
	 * @param string $file file to read structure from
	 * @return bool
	 */
	public static function updateDbFromStructure($file) {
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
		
		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		$previousSchema = self::$schema->getDefinitionFromDatabase();
		if (PEAR::isError($previousSchema)) {
			$error = $previousSchema->getMessage();
			$detail = $previousSchema->getDebugInfo();
			OC_Log::write('core', 'Failed to get existing database structure for upgrading ('.$error.', '.$detail.')', OC_Log::FATAL);
			return false;
		}

		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $previousSchema['name'], $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		/* FIXME: use CURRENT_TIMESTAMP for all databases. mysql supports it as a default for DATETIME since 5.6.5 [1]
		 * as a fallback we could use <default>0000-01-01 00:00:00</default> everywhere
		 * [1] http://bugs.mysql.com/bug.php?id=27645
		 * http://dev.mysql.com/doc/refman/5.0/en/timestamp-initialization.html
		 * http://www.postgresql.org/docs/8.1/static/functions-datetime.html
		 * http://www.sqlite.org/lang_createtable.html
		 * http://docs.oracle.com/cd/B19306_01/server.102/b14200/functions037.htm
		 */
		if( $CONFIG_DBTYPE == 'pgsql' ) { //mysql support it too but sqlite doesn't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>', '<default>CURRENT_TIMESTAMP</default>', $content );
		}
		file_put_contents( $file2, $content );
		$op = self::$schema->updateDatabase($file2, $previousSchema, array(), false);

		//clean up memory
		unlink( $file2 );

		if (PEAR::isError($op)) {
			$error = $op->getMessage();
			$detail = $op->getDebugInfo();
			OC_Log::write('core', 'Failed to update database structure ('.$error.', '.$detail.')', OC_Log::FATAL);
			return false;
		}
		return true;
	}

	/**
	 * @brief connects to a MDB2 database scheme
	 * @returns bool
	 *
	 * Connects to a MDB2 database scheme
	 */
	private static function connectScheme() {
		// We need a mdb2 database connection
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->loadModule('Reverse');

		// Connect if this did not happen before
		if(!self::$schema) {
			require_once 'MDB2/Schema.php';
			self::$schema=MDB2_Schema::factory(self::$MDB2);
		}

		return true;
	}

	/**
	 * @brief does minor changes to query
	 * @param string $query Query string
	 * @return string corrected query string
	 *
	 * This function replaces *PREFIX* with the value of $CONFIG_DBTABLEPREFIX
	 * and replaces the ` with ' or " according to the database driver.
	 */
	private static function processQuery( $query ) {
		self::connect();
		// We need Database type and table prefix
		if(is_null(self::$type)) {
			self::$type=OC_Config::getValue( "dbtype", "sqlite" );
		}
		$type = self::$type;
		if(is_null(self::$prefix)) {
			self::$prefix=OC_Config::getValue( "dbtableprefix", "oc_" );
		}
		$prefix = self::$prefix;

		// differences in escaping of table names ('`' for mysql) and getting the current timestamp
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'NOW()', 'datetime(\'now\')', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'strftime(\'%s\',\'now\')', $query );
		}elseif( $type == 'pgsql' ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'cast(extract(epoch from current_timestamp) as integer)', $query );
		}elseif( $type == 'oci'  ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
		}

		// replace table name prefix
		$query = str_replace( '*PREFIX*', $prefix, $query );

		return $query;
	}

	/**
	 * @brief drop a table
	 * @param string $tableName the table to drop
	 */
	public static function dropTable($tableName) {
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->dropTable($tableName);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($file) {
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to a temporary file
		$file2 = tempnam( get_temp_dir(), 'oc_db_scheme_' );
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		file_put_contents( $file2, $content );

		// get the tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );

		// Delete our temporary file
		unlink( $file2 );
		$tables=array_keys($definition['tables']);
		foreach($tables as $table) {
			self::dropTable($table);
		}
	}

	/**
	 * @brief replaces the owncloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public static function replaceDB( $file ) {
		$apps = OC_App::getAllApps();
		self::beginTransaction();
		// Delete the old tables
		self::removeDBStructure( OC::$SERVERROOT . '/db_structure.xml' );

		foreach($apps as $app) {
			$path = OC_App::getAppPath($app).'/appinfo/database.xml';
			if(file_exists($path)) {
				self::removeDBStructure( $path );
			}
		}

		// Create new tables
		self::createDBFromStructure( $file );
		self::commit();
	}

	/**
	 * Start a transaction
	 * @return bool
	 */
	public static function beginTransaction() {
		self::connect();
		if (self::$backend==self::BACKEND_MDB2 && !self::$connection->supports('transactions')) {
			return false;
		}
		self::$connection->beginTransaction();
		self::$inTransaction=true;
		return true;
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 * @return bool
	 */
	public static function commit() {
		self::connect();
		if(!self::$inTransaction) {
			return false;
		}
		self::$connection->commit();
		self::$inTransaction=false;
		return true;
	}

	/**
	 * check if a result is an error, works with MDB2 and PDOException
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result) {
		if(!$result) {
			return true;
		}elseif(self::$backend==self::BACKEND_MDB2 and PEAR::isError($result)) {
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * returns the error code and message as a string for logging
	 * works with MDB2 and PDOException
	 * @param mixed $error
	 * @return string
	 */
	public static function getErrorMessage($error) {
		if ( self::$backend==self::BACKEND_MDB2 and PEAR::isError($error) ) {
			$msg = $error->getCode() . ': ' . $error->getMessage();
			if (defined('DEBUG') && DEBUG) {
				$msg .= '(' . $error->getDebugInfo() . ')';
			}
		} elseif (self::$backend==self::BACKEND_PDO and self::$PDO) {
			$msg = self::$PDO->errorCode() . ': ';
			$errorInfo = self::$PDO->errorInfo();
			if (is_array($errorInfo)) {
				$msg .= 'SQLSTATE = '.$errorInfo[0] . ', ';
				$msg .= 'Driver Code = '.$errorInfo[1] . ', ';
				$msg .= 'Driver Message = '.$errorInfo[2];
			}else{
				$msg = '';
			}
		}else{
			$msg = '';
		}
		return $msg;
	}
}

/**
 * small wrapper around PDOStatement to make it behave ,more like an MDB2 Statement
 */
class PDOStatementWrapper{
	/**
	 * @var PDOStatement
	 */
	private $statement=null;
	private $lastArguments=array();

	public function __construct($statement) {
		$this->statement=$statement;
	}

	/**
	 * make execute return the result instead of a bool
	 */
	public function execute($input=array()) {
		$this->lastArguments=$input;
		if(count($input)>0) {
			$result=$this->statement->execute($input);
		}else{
			$result=$this->statement->execute();
		}
		if($result) {
			return $this;
		}else{
			return false;
		}
	}

	/**
	 * provide numRows
	 */
	public function numRows() {
		$regex = '/^SELECT\s+(?:ALL\s+|DISTINCT\s+)?(?:.*?)\s+FROM\s+(.*)$/i';
		if (preg_match($regex, $this->statement->queryString, $output) > 0) {
			$query = OC_DB::prepare("SELECT COUNT(*) FROM {$output[1]}", PDO::FETCH_NUM);
			return $query->execute($this->lastArguments)->fetchColumn();
		}else{
			return $this->statement->rowCount();
		}
	}

	/**
	 * provide an alias for fetch
	 */
	public function fetchRow() {
		return $this->statement->fetch();
	}

	/**
	 * pass all other function directly to the PDOStatement
	 */
	public function __call($name,$arguments) {
		return call_user_func_array(array($this->statement,$name), $arguments);
	}

	/**
	 * Provide a simple fetchOne.
	 * fetch single column from the next row
	 * @param int $colnum the column number to fetch
	 */
	public function fetchOne($colnum = 0) {
		return $this->statement->fetchColumn($colnum);
	}
}
