Query
=====

## Introduction

Query is a database layer for developers who enjoy writing SQL. While it does include an ORM and query builder, these components don't try to do too much. If a query starts getting complicated, simply pass a raw SQL statement.

### Features

- Query is designed for SQL performance.
- Query provides an organized way to run queries using raw SQL.
- Query is built on top of the PDO library, and is therefore very safe.
- Query currently supports MySQL and SQLite databases.
- Query has a built-in logger.
- Table ORM makes `INSERT`, `UPDATE` and `DELETE` statements effortless.
- Table ORM requires minimal setup (only one additional model parameter needed).
- Select Query Builder automates basic `SELECT` statements.

## Raw SQL examples

```php
<?php

// Select all users
$users = DB::rows('SELECT id, name, email FROM users ORDER BY name');

// Select a specific user
$user = DB::row('SELECT id, name, email FROM users WHERE id = ?', array($id));

// Select a specific user's email
$email = DB::field('SELECT email FROM users WHERE id = ?', array($id));

// Select a specific user by multiple fields
$user = DB::row(
    'SELECT id, name, email FROM users WHERE gender = :gender and country = :country',
    array(
        'gender' => 'Male',
        'country' => 'Canada'
    )
);
?>
```

## Table ORM

### Usage
```php
<?php

// Insert (create) a user
$user = new User();
$user->name = 'Jonathan';
$user->country = 'Canada';
$user->insert();

// Select a user
$user = User::select(1);

// Update a user
$user->gender = 'Male';
$user->update();

// Delete a user
$user->delete();
```

### Setup

To enable the ORM on a model class, simply extend it with the `Table` class, add a constant named `DB_TABLE`, and set all your database table fields as `protected` parameters.

Table comes with built-in magic methods, which will first check for `getter` and `setter` methods, and will otherwise give direct access to the object paramaters. This gives all the benefits of `getter` and `setter` methods, without changing the way you access your object's parameters.

#### Example model

```php
<?php
namespace Your\Name\Space;

use Reinink\Query\Table;

class User extends Table
{
    const DB_TABLE = 'users';
    protected $id;
    protected $name;
    protected $gender;
    protected $birth_date;
    protected $email;
    protected $country;

    public function getBirthDate()
    {
        return date_create($this->birth_date);
    }

    public function setBirthDate(DateTime $birth_date)
    {
        $this->birth_date = $birth_date->format('Y-m-d');
    }
}
```

## Select Query Builder

Select sits on top of Table, and therefore requires models to work. Select will not be the answer to all your `SELECT` statements. It simply provides some automation to more basic, and more common queries.

### Notable limitations

- No `JOIN` support
- No nested `WHERE` statements

### Usage

```php
<?php

// Define fields
Model::select('id, name, email')

// All fields
// IMPORTANT: Read the "Returned objects" section below
Model::select()
Model::select('*')

// Where conditions
->where('email', $val)                      // email = $val
->whereNot('email', $val)                   // email != $val
->whereNull('email')                        // email IS null
->whereNotNull('email')                     // email IS NOT null
->whereLike('email', $val)                  // email LIKE $val
->whereNotLike('email', $val)               // email NOT LIKE $val
->whereIn('email', array($val1, $val2))     // email IN ($val1, $val2)
->whereNotIn('email', array($val1, $val2))  // email NOT IN ($val1, $val2)
->whereGreater('age', $val)                 // age > $val
->whereLess('age', $val)                    // age < $val
->whereGreaterOrEqual('age', $val)          // age >= $val
->whereLessOrEqual('age', $val)             // age <= $val

// Logical operators
->or($val)                                  // OR email = $val
->orNot($val)                               // OR email != $val
->and($val)                                 // AND email = $val
->andNot($val)                              // AND email != $val

// Order by
->orderBy('name DESC')

// Limits
->limit(10)                                 // LIMIT 10
->limit(0, 10)                              // LIMIT 0, 10

// Returning results
->rows()                                    // Multiple rows
->row()                                     // Single row
->field()                                   // Single field
```

### Examples

```php
<?php

// Select all users
User::select('id, name, email')->orderBy('name')->rows();

// Select all users from Canada or the USA
User::select('id, name, email')->whereIn('country', array('Canada', 'USA'))->orderBy('name')->rows();

// Select all users with the name Jonathan who don't live in Canada
User::select('id, name, email')->where('name', 'Jonathan')->andNot('country', 'Canada')->rows();

// Select all users with no birthday set
User::select('id, name, email')->whereNull('birthday')->orderBy('name')->rows();

// Select a specific user
User::select('id, name, email')->where('id', 1)->row();

// Select a specific user's email
User::select('email')->where('id', 1)->field();

// Select COUNT of users in Canada
User::select('COUNT(*)')->where('country', 'Canada')->field();
```

### Returned objects

Query subscribes to the belief that `SELECT *` should never be used, unless prior to performing an `UPDATE` or `DELETE` query. Since these are methods built into the Table ORM, when you do select all fields, Select will automatically return `Model` objects.

Conversely, when selecting specific table fields, Select will return `stdClass` objects. Some examples:

```php
<?php

$user = User::select();                     // returns User
$user = User::select('*');                  // returns User
$user->update();                            // Works
$user->delete();                            // Works

$user = User::select('id, name, email');    // returns stdClass
$user = User::select('COUNT(*)');           // returns stdClass
$user->update();                            // Does not work
$user->delete();                            // Does not work
```

## Query logging

Query has a built-in logger, which tracks all queries run, including those executed by the Table ORM and Select Query Builder. The logger keeps a record of the SQL, bindings, and execution time of each query. To view this information (as an array), simply call the `log()` method:

```php
<?php

$log = DB::log();
```