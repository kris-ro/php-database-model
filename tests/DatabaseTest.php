<?php

use PHPUnit\Framework\TestCase;
use KrisRo\PhpDatabaseModel\Database;

class DatabaseTest extends TestCase {
  public function testConnection() {
    $database = new Database([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);

    $this->assertEquals(TRUE, $database instanceof \KrisRo\PhpDatabaseModel\Database);

    $newDatabase = new Database($database->getConnection());
    $this->assertEquals(TRUE, $newDatabase instanceof \KrisRo\PhpDatabaseModel\Database);
  }

  public function testFetch() {
    $database = new Database([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);

    $rows = $database->query('SELECT * FROM users LIMIT 0,1')->execute()->fetchAllObject();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT * FROM users LIMIT 0,2')->execute()->fetchAllAssoc();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT * FROM users LIMIT 0,2')->execute()->fetchAllNum();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT * FROM users LIMIT 0,2')->execute()->fetchAllBoth();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT users_id, user_name, email FROM users LIMIT 0,2')->execute()->fetchAllUnique();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT user_name FROM users LIMIT 0,2')->execute()->fetchAllOneColumn();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT user_name, email FROM users LIMIT 0,2')->execute()->fetchAllIndexedByColumn();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT user_profiles_id, user_name, email FROM users LIMIT 0,10')->execute()->fetchAllGrouped();
    $this->assertEquals(TRUE, !empty($rows));

    $rows = $database->query('SELECT user_profiles_id, email FROM users LIMIT 0,10')->execute()->fetchAllGroupedColumn();
    $this->assertEquals(TRUE, !empty($rows));
  }

  public function testRowCount() {
    $database = new Database([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);

    $rowCount = $database->query('SELECT user_profiles_id, email FROM users LIMIT 0,8')->execute()->rowCount();
    $this->assertEquals(TRUE, ($rowCount <= 8));
  }

  public function testInsert() {
    $database = new Database([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);


    /**
     * Get inserted ID
     */
    $queryString = "INSERT INTO `users` (`users_id`, `user_profiles_id`, `salt`, `user_name`, `email`) VALUES
                      (100, 2, 'Y2hU1zY5y', 'Cristian Radu', 'some.email@address.somewhere')";

    $insertedId = $database->query($queryString)->execute()->returnId();
    $this->assertEquals(TRUE, is_int($insertedId));

    /**
     * Get number of inserted rows
     */
    $queryString = "INSERT INTO `users` (`users_id`, `user_profiles_id`, `salt`, `user_name`, `email`) VALUES
                      (101, 2, 'd2hU1frh5y', 'Cristi Radu', 'other.email@address.somewhere'),
                      (102, 2, 'd2h3r6u5y', 'Cris Radu', 'another.email@address.somewhere')";
    $rowCount = $database->query($queryString)->execute()->rowCount();
    $this->assertEquals(TRUE, ($rowCount == 2));
  }

  public function testDelete() {
    $database = new Database([
      'host' => 'localhost',
      'database' => 'test',
      'username' => 'k',
      'password' => '123456',
    ]);

    $params = [
      ':id_100' => 100,
    ];

    $queryString = "DELETE FROM `users` WHERE (`users_id` = :id_100)";
    $rowCount = $database->query($queryString)->execute($params)->rowCount();
    $this->assertEquals(1, $rowCount);
  }
}