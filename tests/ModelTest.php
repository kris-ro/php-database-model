<?php

use PHPUnit\Framework\TestCase;
use KrisRo\PhpDatabaseModel\Model;

class ModelTest extends TestCase {
  
  public function testGetTableFields() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $fields = $model->getTableFields('users');
    
    $this->assertEquals(TRUE, is_array($fields));
  }
  
  public function testFetchAllMagicCalls() {
    // get(Assoc|Unique|Column|Indexed|Grouped){Table_name}ByCondition
    
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $rows = $model->getAssocUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getObjectsUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));
    $this->assertEquals(TRUE, is_object($rows[0]));

    $rows = $model->getUniqueUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getIndexedUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getColumnUsersByCondition([
      'select' => 'user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getGroupedUsersByCondition([
      'select' => 'user_profiles_id, user_name, email',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 100]
    ])->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getAssocUsersByEmail('kris_ro@some-non-domain.con')->all();
    $this->assertEquals(TRUE, is_array($rows));

    $rows = $model->getAssocUsersLikeEmail('kris_ro')->all();
    $this->assertEquals(TRUE, is_array($rows));
    
    $rows = $model->getObjectsUsersLikeEmail('kris_ro')->all();
    $this->assertEquals(TRUE, is_array($rows));
    
    $rows = $model->getallUsers();
    $this->assertEquals(TRUE, is_array($rows));
  }

  public function testFetchMagicCalls() {
    // get(Assoc|Unique|Column|Indexed|Grouped){Table_name}ByCondition
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $row = $model->getAssocUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->next();
    $this->assertEquals(TRUE, is_array($row));

    $query = $model->getObjectsUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ]);
    $this->assertEquals(TRUE, $query->next() instanceof \stdClass);
    $this->assertEquals(TRUE, $query->next() instanceof \stdClass);
    $this->assertEquals(FALSE, $query->next());

    $row = $model->getUniqueUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->next();
    $this->assertEquals(TRUE, is_array($row));

    $row = $model->getIndexedUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ])->next();
    $this->assertEquals(TRUE, is_array($row));

    $row = $model->getAssocUsersByEmail('kris_ro@some-non-domain.com')->next();
    $this->assertEquals(TRUE, is_array($row));
//
    $query = $model->getAssocUsersLikeEmail('kris_ro');
    $this->assertEquals(TRUE, is_array($query->next()));
    $this->assertEquals(TRUE, is_array($query->next()));
    $this->assertEquals(FALSE, $query->next());
//
    $query = $model->getObjectsUsersLikeEmail('kris_ro');
    $this->assertEquals(TRUE, $query->next() instanceof \stdClass);
    $this->assertEquals(TRUE, $query->next() instanceof \stdClass);
    $this->assertEquals(FALSE, $query->next());
  }
  
  public function testUpdate() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $updated = $model->updateUsersByCondition([
      'condition' => '`users_id` = :id',
      'params' => [':id' => 60],
      'values' => [
        'email' => bin2hex(random_bytes(8)) . '-tralala@some-domain.toto',
      ],
    ]);
    $this->assertEquals(1, $updated);

    $updated = $model->updateUsersByCondition([
      'condition' => '`users_id` = :id',
      'params' => [':id' => 1200],
      'values' => [
        'email' => bin2hex(random_bytes(8)) . '-tralala-again@some-domain.toto',
      ],
    ]);
    $this->assertEquals(0, $updated);

    $updated = $model->updateUsers([
      'users_id' => 1200,
      'email' => bin2hex(random_bytes(8)) . '-tralala-again@some-domain.toto',
    ]);
    $this->assertEquals(0, $updated);

    $updated = $model->updateUsers([
      'users_id' => 60,
      'email' => bin2hex(random_bytes(8)) . '-tralala-again@some-domain.toto',
    ]);
    $this->assertEquals(1, $updated);
  }
  
  public function testDelete() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $deleted = $model->deleteUsersByCondition([
      'condition' => '`users_id` = :id',
      'params' => [':id' => 101],
    ]);
    $this->assertEquals(1, $deleted);
    
    $deleted = $model->deleteUsers(102);
    $this->assertEquals(1, $deleted);
  }
  
  public function testCounting() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);

    $count = $model->countUsersByCondition([
      'select' => 'salt, user_name',
      'condition' => '`users_id` < :id',
      'params' => [':id' => 20]
    ]);

    $this->assertEquals(2, $count);
  }
  
  public function testSet() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
    $count = $model->setUsers([
      'user_profiles_id' => '4', 
      'salt' => 'ecvfr56765ty', 
      'user_name' => 'Test Test', 
      'email' => bin2hex(random_bytes(8)) . '-test.test@some-domain.toto',
    ]);
    $this->assertEquals(1, $count);

    $id = $model->setUsersAndGetId([
      'user_profiles_id' => '4', 
      'salt' => 'cx6uykuy', 
      'user_name' => 'Another Test', 
      'email' => bin2hex(random_bytes(8)) . '-another.test@some-domain.toto',
    ]);
    $this->assertEquals(TRUE, is_int($id));
  }
  
  public function testSetBatch() {
    $model = new Model([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);
    
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
    $this->assertEquals(2, $count);
  }
}