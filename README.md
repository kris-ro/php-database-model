# PHP Database Modeling

The scope of this class is to streamline the development. If you need a well structured library or system like ORM or Active Record then this is not for you. My aim here was to have a way to make an easy semantic request and get a result set from database. PDO is working behind the scene.  

## Installation

Use composer to install *PHP Database Modeling*.

```bash
composer require kris-ro/php-database-model
```

## Usage

These are all magic calls (processed with __call()) in the form:\
`get(Assoc|Objects|Unique|Column|Indexed|Grouped){Table_name}ByCondition()->(all|next)()`\
`get(Assoc|Objects|Unique|Column|Indexed|Grouped){Table_name}By{Column_name}()->(all|next)()`\
`set{Table_name}()`\
`set{Table_name}AndGetId()`\
`update{Table_name}ByCondition()`\
`update{Table_name}()`\
`delete{Table_name}ByCondition()`\
`delete{Table_name}()`\
`count{Table_name}ByCondition()`\
 

```php
require YOUR_PATH . '/vendor/autoload.php';

use KrisRo\PhpDatabaseModel\Model;

# Ge the model instance
$model = new Model([
  'host' => 'localhost',
  'database' => 'db_name',
  'username' => 'db_user',
  'password' => 'db_password',
]);

# get the result set as a list of associative array
$rows = $model->getAssocUsersByCondition([
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
])->all();

# fetching one by one
$query = $model->getAssocUsersByCondition([
  'select' => 'salt, user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
]);
$oneRow = $query->next();
$oneRow = $query->next();
$oneRow = $query->next();
$oneRow = $query->next();

# get the result set as a list of stdClass objects
$rows = $model->getObjectsUsersByCondition([
  'select' => 'salt, user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
])->all();

# fetch the result set indexed by table's id or another unique column
# first column (`salt` in this case) is used for indexing the result set
$rows = $model->getUniqueUsersByCondition([
  'select' => 'salt, user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
])->all();

# get rows with key => values pairs
$rows = $model->getIndexedUsersByCondition([
  'select' => 'salt, user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
])->all();

# get one column indexed numerically
$rows = $model->getColumnUsersByCondition([
  'select' => 'user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
])->all();

# group and index the result set by the first column specified in the query (`user_profiles_id` in this case)
$rows = $model->getGroupedUsersByCondition([
  'select' => 'user_profiles_id, user_name, email',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 100]
])->all();

# get users with email equal to this value
$model->getAssocUsersByEmail('some-mailbox@some-non-domain.con')->all();

# get users with email containing this value (like %%)
$model->getAssocUsersLikeEmail('some-mailbox')->all();

# get users as stdClass objects with email containing this value (like %%)
$rows = $model->getObjectsUsersLikeEmail('kris_ro')->all();

# this will get you the whole table
$rows = $model->getallUsers();

# update values
$updateCount = $model->updateUsersByCondition([
  'condition' => '`users_id` = :id',
  'params' => [':id' => 60],
  'values' => [
    'email' => 'tralala@some-domain.toto',
  ],
]);

# update a row by its primary key determined as {table_name}_id
$updated = $model->updateUsers([
  'users_id' => 1200,
  'email' => 'tralala-again@some-domain.toto',
]);

# delete users
$deleted = $model->deleteUsersByCondition([
  'condition' => '`users_id` = :id',
  'params' => [':id' => 101],
]);

# delete row by its primary key determined as {table_name}_id
$deleted = $model->deleteUsers(102);

# counting 
$count = $model->countUsersByCondition([
  'select' => 'salt, user_name',
  'condition' => '`users_id` < :id',
  'params' => [':id' => 20]
]);

# adding new records
$count = $model->setUsers([
  'user_profiles_id' => '4', 
  'salt' => 'ecvfr56765ty', 
  'user_name' => 'Test Test', 
  'email' => 'test.test@some-domain.toto',
]);

# save the record and return the last inserted ID
$id = $model->setUsersAndGetId([
  'user_profiles_id' => '4', 
  'salt' => 'cx6uykuy', 
  'user_name' => 'Another Test', 
  'email' => 'another.test@some-domain.toto',
]);

# batch insert; returns the number of rows inserted
$count = $model->setUsers([
  [
    'user_profiles_id' => '4', 
    'salt' => 'ecvfr56765ty', 
    'user_name' => 'Test Test', 
    'email' => bin2hex(random_bytes(8)) . '-testy.test@some-domain.toto',
  ],
  [
    'user_profiles_id' => '4', 
    'salt' => 'cx6uykuy', 
    'user_name' => 'Another Test', 
    'email' => bin2hex(random_bytes(8)) . '-another.testy@some-domain.toto',
  ]
]);
```

## License

[MIT](https://choosealicense.com/licenses/mit/)