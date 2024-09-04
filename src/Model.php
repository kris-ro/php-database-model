<?php

namespace KrisRo\PhpDatabaseModel;

/**
 * Magic calls:
 * <code>get(Assoc|Objects|Unique|Column|Indexed|Grouped){Table_name}ByCondition()->(all|next)()</code>
 * <code>get(Assoc|Objects|Unique|Column|Indexed|Grouped){Table_name}By{Column_name}()->(all|next)()</code>
 * <code>set{Table_name}</code>
 * <code>set{Table_name}AndGetId</code>
 * <code>update{Table_name}ByCondition</code>
 * <code>update{Table_name}</code>
 * <code>delete{Table_name}ByCondition</code>
 * <code>delete{Table_name}</code>
 * <code>count{Table_name}ByCondition</code>
 */
class Model extends Database {

  /**
   * Allowed fetch methods. See <code>KrisRo\PhpDatabaseModel\Database</code> methods for details.
   * 
   * @var array
   */
  private $fetchMethods = [
    'Assoc',
    'Objects',
    'Unique', // Fetch indexed by id or another unique column : [id-35 => [row data], ...]
    'Column', // Fetch one column indexed numerically : [1 => Toyota, 2 => Renault, ...]
    'Indexed',// Fetch rows of pairs indexed by column : [Jerry => Toyota, ...] like Column, but indexed by another column
    'Grouped',// Fetch rows grouped by column : [car-Toyota => [all rows with car Toyota]]
  ];
  
  /**
   * Selected fetch mode on magic get call
   * This would be the <code>KrisRo\PhpDatabaseModel\Database</code> method name
   * 
   * @var string
   */
  protected $fetchMode = NULL;

  /**
   * When set to TRUE the existing statement is used.
   * 
   * @var bool
   */
  protected $nextRow = FALSE;
  
  public function __call($call, $arguments) {
    $callPattern = '/^([a-z]+)([A-Z][^A-Z]+)([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?([A-Z][^A-Z]+)?/u';
    if (!preg_match($callPattern, $call, $callSegments)) {
      trigger_error('Unknown method in Model', E_USER_ERROR);
      return;
    }
    
    $this->nextRow = FALSE;
    
    switch ($callSegments[1]) {
      case 'set':
        return $this->set(array_slice($callSegments, 2), current($arguments));

      case 'get':
        return $this->get(array_slice($callSegments, 2), current($arguments));

      case 'getall':
        return $this->getEverything(array_slice($callSegments, 2));

      case 'update':
        return $this->update(array_slice($callSegments, 2), current($arguments));
      
      case 'delete':
        return $this->delete(array_slice($callSegments, 2), current($arguments));
      
      case 'count':
        return $this->count(array_slice($callSegments, 2), current($arguments));
    }
  }

  /**
   * fetchAll all records in the result set
   * 
   * @return array
   */
  public function all(): array {
    $fetchAllModes = [
      'Assoc' => 'fetchAllAssoc',
      'Objects' => 'fetchAllObject',
      'Unique' => 'fetchAllUnique',
      'Column' => 'fetchAllOneColumn',
      'Indexed' => 'fetchAllIndexedByColumn',
      'Grouped' => 'fetchAllGrouped',
    ];

    if (!($this->fetchMode ?? NULL) || !isset($fetchAllModes[$this->fetchMode])) {
      trigger_error('Invalid model call (no valid fetch mode)', E_USER_ERROR);
    }
    
    $fetchMethod = $fetchAllModes[$this->fetchMode];
    
    return $this->$fetchMethod();
  }
  
  /**
   * fetch next record in the result set
   * 
   * @return array|stdClass
   */
  public function next(): bool|array|\stdClass {
    $this->nextRow = TRUE;

    $fetchAllModes = [
      'Assoc' => \PDO::FETCH_ASSOC,
      'Objects' => \PDO::FETCH_OBJ,
      'Unique' => \PDO::FETCH_UNIQUE,
      'Indexed' => \PDO::FETCH_KEY_PAIR,
      'Grouped' => \PDO::FETCH_GROUP,
    ];

    if (!($this->fetchMode ?? NULL) || !isset($fetchAllModes[$this->fetchMode])) {
      $this->nextRow = FALSE;
      trigger_error('Invalid model call (no valid fetch mode)', E_USER_ERROR);
    }

    $result = $this->fetch($fetchAllModes[$this->fetchMode]);
    
    if (!$result) {
      $this->nextRow = FALSE;
    }

    return $result;
  }

  /**
   * Get table fields
   * 
   * @param string $tableName
   * 
   * @return array
   */
  public function getTableFields(string $tableName): array {
    $sql = "show columns from `$tableName`";

    return $this->query($sql)->execute()->fetchAllAssoc();
  }
  
  /**
   * Prep and execute insert statements
   * 
   * @param array $callSegments
   * @param type $params
   * 
   * @return int|string|bool
   */
  public function set(array $callSegments, $params): int|string|bool {
    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }

    if (is_array(current($params))) {
      return $this->setBatch($tableName, $params);
    }
    
    $returnId = (implode('', array_slice($callSegments, 0, 3)) == 'AndGetId');
    
    $execParams = [];
		foreach ($params as $field => $value) {
			$fields[':' . $field] = $field;
			$execParams[':' . $field] = $value;
		}

		$sql = 'INSERT INTO `' . $tableName . '` (`' . implode('`, `', $fields) . '`) VALUES (' . implode(', ', array_keys($fields)) . ')';
    
    if ($returnId) {
      return $this->query($sql)->execute($execParams)->returnId();
    }
    
    return $this->query($sql)->execute($execParams)->rowCount();
  }
  
  public function setBatch(string $tableName, array $params): int|bool {
    $rows = [];
    $execParams = [];

    foreach ($params as $index => $set) {
      $rows[] = $this->getBatchInsertRow($tableName, $index, $set, $execParams);
    }

    $sql = 'INSERT INTO `' . $tableName . '` (`' . implode('`, `', array_keys(current($params))) . '`) VALUES ';
    
    return $this->query($sql . implode(', ', $rows))->execute($execParams)->rowCount();
  }
  
  protected function getBatchInsertRow(string $tableName, int $index, array $set, array &$execParams) {
    $fields = [];
    
    foreach ($set as $field => $value) {
      $fields[":{$field}_{$index}"] = $field;
      $execParams[":{$field}_{$index}"] = $value;
    }
    
    return '(' . implode(', ', array_keys($fields)) . ')';
  }
  
  /**
   * Prep SELECT query string
   * 
   * @param array $callSegments
   * @param type $params
   * 
   * @return self
   */
  public function get(array $callSegments, $params): self {
    // update/replace model::getall in code
    if ($this->nextRow) {
      return $this;
    }

    $fetchMode = array_shift($callSegments);
    if (!$fetchMode || !in_array($fetchMode, $this->fetchMethods)) {
      trigger_error('Invalid model call (no valid fetch mode)', E_USER_ERROR);
    }

    $this->fetchMode = $fetchMode;

    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }

    $column = array_pop($callSegments);
    $operator = array_pop($callSegments);

    $sql = 'SELECT ' . $this->buildQuerySelect($params['select'] ?? NULL) 
        . " FROM `{$tableName}` "
        . ($params['join'] ?? '')
        . $this->buildCondition($column, $operator, $params['condition'] ?? NULL)
        . $this->buildGroupBy($params['group'] ?? NULL)
        . $this->buildOrderBy($params['order'] ?? NULL)
        . $this->buildRange($params['range'] ?? NULL);

    $this->query($sql)->execute($this->buildParams($column, $operator, $params ?? NULL));

    return $this;
  }

  public function getEverything($callSegments): array {
    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }
    
    $sql = "select * from `$tableName`";
    
    return $this->query($sql)->execute()->fetchAllAssoc();
  }
  
  /**
   * Prep and execute UPDATE query string
   * 
   * @param array $callSegments
   * @param type $params
   * 
   * @return int|bool
   */
  public function update(array $callSegments, $params): int|bool {
    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }

    $column = array_pop($callSegments) ?: "{$tableName}_id";
    $operator = array_pop($callSegments) ?? '';

    $sql = "UPDATE `{$tableName}` SET " 
        . $this->getUpdateFieldsAndValues($params['values'] ?? $params, $column)
        . $this->buildCondition($column, $operator, $params['condition'] ?? NULL);

    return $this->query($sql)->execute($this->buildParams($column, $operator, $params ?? NULL))->rowCount();
  }

  /**
   * Prep and execute DELETE query string
   * 
   * @param array $callSegments
   * @param type $params
   * 
   * @return int|bool
   */
  public function delete(array $callSegments, $params): int|bool {
    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }
    
    $column = array_pop($callSegments) ?: "{$tableName}_id";
    $operator = array_pop($callSegments) ?? '';

    $sql = "DELETE FROM `{$tableName}` " 
        . $this->buildCondition($column, $operator, $params['condition'] ?? NULL);

    return $this->query($sql)->execute($this->buildParams($column, $operator, $params ?? NULL))->rowCount();
  }

  /**
   * Prep and execute SELECT query string for counting result set
   * 
   * @param array $callSegments
   * @param type $params
   * 
   * @return int|bool
   */
  public function count(array $callSegments, $params): int|bool {
    $tableName = strtolower(array_shift($callSegments) ?? '');
    if (!$tableName) {
      trigger_error('Invalid model call (no table)', E_USER_ERROR);
    }

    $column = 'Condition';
    $operator = '=';

    $sql = 'SELECT ' . $this->buildQuerySelect($params['select'] ?? NULL) 
        . " FROM `{$tableName}` "
        . ($params['join'] ?? '')
        . $this->buildCondition($column, $operator, $params['condition'] ?? NULL)
        . $this->buildGroupBy($params['group'] ?? NULL)
        . $this->buildOrderBy($params['order'] ?? NULL)
        . $this->buildRange($params['range'] ?? NULL);

    return $this->query($sql)->execute($this->buildParams($column, $operator, $params ?? NULL))->rowCount();
  }

  /**
   * Builds query's field string
   * 
   * @param string|array|null $select
   * 
   * @return string
   */
  protected function buildQuerySelect(string|array|null $select = NULL): string {
    if (is_null($select)) {
      return ' * ';
    }

    if (is_array($select)) {
      return ' `' . implode('`,`', $select) . '` ';
    }

    return " {$select} ";
  }

  /**
   * Build <code>where</code> clause
   * 
   * @param string|null $condition
   * 
   * @return string
   */
  protected function buildCondition(string $columnOrCondition, string $operator, string|null $condition = NULL): string {
    /**
     * When column is specified in magic call string
     */
    if ($columnOrCondition != 'Condition') {
      $operator = ($operator == 'Like') ? 'like' : '=';
      return ' WHERE `' . strtolower($columnOrCondition) . '` ' . $operator . ' :' . strtolower($columnOrCondition);
    }

    if (is_null($condition)) {
      return '';
    }

    return " WHERE {$condition} ";
  }

  /**
   * Build <code>group by</code> clause
   * 
   * @param string|array|null $groupBy
   * 
   * @return string
   */
  protected function buildGroupBy(string|array|null $groupBy = NULL): string {
    if (is_null($groupBy)) {
      return '';
    }
    
    if (is_array($groupBy)) {
      return ' GROUP BY `' . implode('`,`', $groupBy) . '` ';
    }
    
    return " GROUP BY {$groupBy} ";
  }
  
  /**
   * Build <code>order by</code> clause
   * 
   * @param string|null $orderBy
   * 
   * @return string
   */
  protected function buildOrderBy(string|null $orderBy): string {
    if (is_null($orderBy)) {
      return '';
    }
    
    if (!preg_match('/^(,? ?`?[a-z0-9_]+`?(\.`?[a-z0-9_]+`?)? (asc|desc))+$/i', $orderBy)) {
      return '';
    }

    return " ORDER BY {$orderBy} ";
  }
  
  /**
   * Build <code>limit</code> clause
   * 
   * @param string|null $range
   * 
   * @return string
   */
  protected function buildRange(string|null $range): string {
    if (is_null($range)) {
      return '';
    }
    
    if (!preg_match('/^\d+,\d+$/', $range)) {
      return '';
    }
    
    return " LIMIT {$range} ";
  }
  
  /**
   * Build executed params
   * 
   * @param string $columnOrCondition
   * @param string $operator
   * @param array|null|string|int $params
   * 
   * @return array
   */
  protected function buildParams(string $columnOrCondition, string $operator, array|null|string|int $params): array {
    // When column is specified in magic get{Table_name}By{Column_name} call or when update{Table_name} is called
    if ($columnOrCondition != 'Condition') {
      return $this->buildParamsByColumn($columnOrCondition, $operator, $params);
    }

    return $this->buildParamsByCondition($params);
  }
  
  /**
   * Build query params array
   * 
   * @param array|null $params
   * 
   * @return array
   */
  protected function buildParamsByCondition(array|null $params): array {
    if (is_null($params['params'] ?? NULL) || !is_array($params['params'])) {
      return [];
    }
 
    foreach (($params['values'] ?? []) as $field => $value) {
      $params['params'][":{$field}"] = $value;
    }

    return $params['params'];
  }
  
  /**
   * Build query params array
   * 
   * @param string $column
   * @param string $operator
   * @param int|string|array $params
   * 
   * @return array
   */
  protected function buildParamsByColumn(string $column, string $operator, int|string|array $params): array {
    // When update{Table_name} is called
    if ($params[$column] ?? NULL) {
      $executeParams = [];

      foreach ($params as $field => $value) {
        $executeParams[":{$field}"] = $value;
      }

      return $executeParams;
    }
    
    // When column is specified in magic get{Table_name}By{Column_name} call
    // When delete{Table_name} is called
    if (!is_int($params) && !is_string($params)) {
      trigger_error('Invalid model call (invalid params)', E_USER_ERROR);
    }

    if ($operator == 'Like') {
      return [
        ':' . strtolower($column) => "%{$params}%",
      ];

    } else {
      return [
        ':' . strtolower($column) => $params,
      ];
    }
    
    return [];
  }
  
  /**
   * Build [field = value] pairs for update query
   * 
   * @param array|null $params
   * 
   * @return string
   */
  protected function getUpdateFieldsAndValues(array|null $params, string $column): string {
    if (!is_array($params) || empty($params)) {
      trigger_error('Invalid model call (invalid params)', E_USER_ERROR);
    }
    
    if ($params[$column] ?? NULL) {
      unset($params[$column]);
    }
    
    $fields = [];
    foreach (array_keys($params) as $field) {
      $fields[] = "`{$field}` = :{$field}";
    }

    return ' ' . implode(', ', $fields) . ' ';
  }
}