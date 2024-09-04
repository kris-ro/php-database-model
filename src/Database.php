<?php

namespace KrisRo\PhpDatabaseModel;

class Database {

  /**
   * Database connection
   * 
   * @var object 
   *   PDO object
   */
  protected $databaseConnection = null;

  /**
   * Transaction ID
   * 
   * @var string
   */
  protected $transactionId = null;

  /**
   * Query object
   * 
   * @var object
   */
  private $query = null;

  /**
   * Default PDO method
   * 
   * @var string
   */
  protected $method = 'fetchAll';

  /**
   * Accepted PDO operations
   * 
   * @var array
   */
  private $allowedMethods = ['fetchAll', 'returnId', 'rowCount'];

  /**
   * No error value return on executing params
   */
  private $_MYSQL_CLEAR_ERR = '00000';
  
  protected $fetchModes = [
    \PDO::FETCH_ASSOC,
    \PDO::FETCH_NUM,
    \PDO::FETCH_BOTH,
    \PDO::FETCH_OBJ,
    
    \PDO::FETCH_COLUMN,
    \PDO::FETCH_KEY_PAIR,
    \PDO::FETCH_UNIQUE,
    \PDO::FETCH_GROUP,
  ];
  
  /**
   * Constructor
   * 
   * @param array $credentials
   */
  public function __construct(?array $credentials = NULL) {
    if ($credentials) {
      $this->createConnection([
        'host' => $credentials['host'],
        'database' => $credentials['database'],
        'username' => $credentials['username'],
        'password' => $credentials['password'],
      ]);
    }
  }

  /**
   * Creates database connection
   * 
   * @param array $credentials
   * 
   * @return $this
   */
  public function createConnection(array $credentials): self {
    try {
      $this->databaseConnection = new \PDO(
        'mysql: --default-character-set=utf8;charset=utf8;' . 'host=' . ($credentials['host'] ?? null) . ';dbname=' . ($credentials['database'] ?? null),
        ($credentials['username'] ?? null),
        ($credentials['password'] ?? null),
        [\PDO::MYSQL_ATTR_FOUND_ROWS => true]
      );

    } catch (\PDOException $e) {
      trigger_error($e->getMessage(), E_USER_ERROR);
      return $this;
    }

    return $this;
  }
  
  /**
   * Set SQL query
   * 
   * @param string $query
   * 
   * @return self
   */
  public function query(string $query): self {
    $this->query = $this->databaseConnection->prepare($query);
    
    return $this;
  }
  
  /**
   * Execute query params
   * 
   * @param array|null $params
   * 
   * @return self
   */
  public function execute(?array $params = []): self {
    if (!$this->query) {
      trigger_error('Query is missing', E_USER_ERROR);
    }

    $this->query->execute($params);

    $error = $this->query->errorInfo();
    if ($error[0] !== $this->_MYSQL_CLEAR_ERR) {
      trigger_error('[' . $error[0] . ']' . $error[2], E_USER_ERROR);
    }

    return $this;
  }
  
  /**
   * rowCount wrapper
   * 
   * @return int
   */
  public function rowCount(): int {
    if (!$this->query) {
      trigger_error('Query is missing', E_USER_ERROR);
    }

    $count = $this->query->rowCount();
    if ($count === FALSE) {
      trigger_error('Query error', E_USER_ERROR);
    }

    return $count;
  }
  
  /**
   * Get last ID returned by MySQL's LAST_INSERT_ID()
   * 
   * @return int
   */
  public function returnId(): int {
    $this->query = $this->databaseConnection->prepare('SELECT LAST_INSERT_ID() as returnID');
    $this->query->execute();
    
    $lastInsertedId = $this->query->fetch(\PDO::FETCH_COLUMN);
    
    if ($lastInsertedId === FALSE) {
      trigger_error('Last insert ID query error', E_USER_ERROR);
    }

    return $lastInsertedId;
  }
  
  
  public function insertRow() {
    
  }
  
  public function fetch($fetchMode): bool|array|\stdClass {
    if (!$this->query) {
      trigger_error('Query is missing', E_USER_ERROR);
    }

    if (in_array($fetchMode, [\PDO::FETCH_UNIQUE, \PDO::FETCH_GROUP])) {
      return $this->query->fetch($fetchMode|\PDO::FETCH_ASSOC) ?: [];
    }

    return $this->query->fetch($fetchMode);
  }
  
  /**
   * Fetch results
   * 
   * @param int|null $fetchMode
   * 
   * @return array
   */
  public function fetchAll(?int $fetchMode = NULL): array {
    if (!$this->query) {
      trigger_error('Query is missing', E_USER_ERROR);
    }
    
    $allowedFetchModes = [
      \PDO::FETCH_OBJ,
      \PDO::FETCH_ASSOC,
      \PDO::FETCH_NUM,
      \PDO::FETCH_BOTH,
      \PDO::FETCH_COLUMN,
      \PDO::FETCH_KEY_PAIR,
      \PDO::FETCH_UNIQUE,
      \PDO::FETCH_GROUP,
    ];
    
    if (!$fetchMode) {
      $fetchMode = \PDO::FETCH_ASSOC;

    } elseif (!in_array($fetchMode, $allowedFetchModes)) {
      trigger_error('Invalid fetch mode', E_USER_ERROR);
    }

    if (in_array($fetchMode, [\PDO::FETCH_UNIQUE, \PDO::FETCH_GROUP])) {
      return $this->query->fetchAll($fetchMode|\PDO::FETCH_ASSOC) ?: [];
    }

    return $this->query->fetchAll($fetchMode) ?: [];
  }
  
  /**
   * Fetch as stdClass objects
   * 
   * @return array
   *   Array of objects
   */
  public function fetchAllObject(): array {
    return $this->fetchAll(\PDO::FETCH_OBJ);
  }

  /**
   * Fetch associative array
   * 
   * @return array
   */
  public function fetchAllAssoc(): array {
    return $this->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   * Fetch numerically indexed array
   * 
   * @return array
   */
  public function fetchAllNum(): array {
    return $this->fetchAll(\PDO::FETCH_NUM);
  }
  
  /**
   * Fetch indexed by column and numerically
   * 
   * @return array
   */
  public function fetchAllBoth(): array {
    return $this->fetchAll(\PDO::FETCH_BOTH);
  }
  
  /**
   * Fetch indexed by id or another unique column
   * 
   * <code>
   * 'SELECT id, name, city FROM users';
   * array (
   *  1025 => array (
   *    'name' => 'Jjerry',
   *    'city' => 'London',
   *  ),
   *  725 => array (
   *    'name' => 'Victor',
   *    'city' => 'Paris',
   *  ),
   * )
   * </code>
   * 
   * @return array
   */
  public function fetchAllUnique(): array {
    return $this->fetchAll(\PDO::FETCH_UNIQUE);
  }

  /**
   * Fetch one column indexed numerically
   * 
   * <code>
   * 'SELECT name FROM users'
   * array (
   *  0 => 'Jerry',
   *  1 => 'Mary',
   *  2 => 'Victor',
   *  3 => 'Serena',
   * )
   * </code>
   * 
   * @return array
   */
  public function fetchAllOneColumn(): array {
    return $this->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * Fetch rows of pairs indexed by column
   * 
   * <code>
   * 'SELECT name, car FROM users'
   * array (
   *  'Jerry' => 'Toyota',
   *  'Mary' => 'Ford',
   *  'Victor' => 'Mazda',
   *  'Serena' => 'Mazda',
   * )
   * </code>
   * 
   * @return array
   */
  public function fetchAllIndexedByColumn(): array {
    return $this->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * Fetch rows grouped by column
   * 
   * <code>
   * 'SELECT city, users.* FROM users'
   * array (
   *  'London' => array ( 
   *    0 => array (
   *      'name' => 'Jerry',
   *      'city' => 'London',
   *    ),
   *    1 => array (
   *      'name' => 'Mary',
   *      'city' => 'London',
   *    ),
   *  ),
   *  'Paris' => array (
   *    0 => array (
   *      'name' => 'Victor',
   *      'city' => 'Paris',
   *    ),
   *    1 => array (
   *      'name' => 'Serena',
   *      'city' => 'Paris',
   *    ),
   *  ),
   * )
   * </code>
   * 
   * @return array
   */
  public function fetchAllGrouped(): array {
    return $this->fetchAll(\PDO::FETCH_GROUP);
  }

  /**
   * Fetch grouped data
   * 
   * <code>
   * 'SELECT city, name FROM users'
   * array (
      'London' => array (
          0 => 'Jerry',
          1 => 'Victor',
        ),
      'Paris' => array (
          0 => 'Mary',
          1 => 'Serena',
        ),
    )
   * </code>
   * 
   * @return array
   */
  public function fetchAllGroupedColumn(): array {
    if (!$this->query) {
      trigger_error('Query is missing', E_USER_ERROR);
    }

    return $this->query->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN);
  }

  /**
   * Start transaction
   * 
   * @param array|null $lockTables
   * @param int|null $lockId
   * 
   * @return bool
   */
  public function beginTransaction(?array $lockTables = [], ?int $lockId = NULL): bool {
    if ($this->transactionId) {
      return TRUE;
    }

    $this->databaseConnection->beginTransaction();
    $this->transaction = $lockId;

    $this->lockTables($lockTables);

		return TRUE;
	}

  /**
   * Rollback a transaction
   * 
   * @param int|null $lockId
   * 
   * @return bool
   */
	public function rollBack(?int $lockId = NULL): bool {
    if ($this->transactionId !== $lockId) {
      return TRUE;
    }

    $this->databaseConnection->rollBack();
    $this->databaseConnection->query('UNLOCK TABLES');

    $this->transactionId = FALSE;

		return TRUE;
	}

  /**
   * Commit a transaction 
   * 
   * @param type $lockId
   * 
   * @return bool
   */
	public function commit($lockId = null): bool {
    if ($this->transactionId !== $lockId) {
      return TRUE;
    }

    $this->databaseConnection->commit();
    $this->databaseConnection->query('UNLOCK TABLES');

    $this->transactionId = FALSE;

    return TRUE;
	}

  /**
   * Lock specified tables
   * 
   * @param array|null $tables
   */
  private function lockTables(?array $tables = []): void {
    $lock = [];
    foreach ($tables as $table => $lockType) {
      $lock[] = "`{$table}` $lockType";
    }

    if (!empty($lock)) {
      $this->databaseConnection->query('LOCK TABLES ' . implode(', ', $lock));
    }
  }
}
