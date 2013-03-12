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
	$user = DB::row('SELECT id, name, email FROM users WHERE gender = :gender and country = :country', array
	(
		'gender' => 'Male',
		'country' => 'Canada'
	));
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

use Reinink\Query\Table;

class User extends Table
{
	const DB_TABLE = 'users';
	protected $id;
	protected $name;
	protected $gender;
	protected $birthday;
	protected $email;
	protected $country;

	public function get_birthday()
	{
		return date_create($this->birthday);
	}

	public function set_birthday(DateTime $birthday)
	{
		$this->birthday = $birthday->format('Y-m-d');
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

	// Specific fields
	Model::select('id, name, email')

	// All fields
	// Be sure to read the "Returned objects" section below
	Model::select()
	Model::select('*')

	// Where conditions
	->email($val)						// email = $val
	->email_not($val)					// email != $val
	->email_null()						// email IS null
	->email_not_null()					// email IS NOT null
	->email_like($val)					// email LIKE $val
	->email_not_like($val)				// email NOT LIKE $val
	->email_in(array($val1, $val2))		// email IN ($val1, $val2)
	->email_not_in(array($val1, $val2))	// email NOT IN ($val1, $val2)
	->age_greater($val)					// age > $val
	->age_less($val)					// age < $val
	->age_greater_equal($val)			// age >= $val
	->age_less_equal($val)				// age <= $val

	// Logical operators
	->or_email($val)					// OR
	->and_email_not($val)				// AND

	// Order by
	->order_by('name DESC')

	// Limits
	->limit(10)							// LIMIT 10
	->limit(0, 10)						// LIMIT 0, 10

	// Returning results
	->rows()							// Multiple rows
	->row()								// Single row
	->field()							// Single field
```

### Examples

```php
<?php

	// Select all users
	User::select('id, name, email')->order_by('name')->rows();

	// Select all users from Canada or the USA
	User::select('id, name, email')->country_in(array('Canada', 'USA'))->order_by('name')->rows();

	// Select all users with the name Jonathan who don't live in Canada
	User::select('id, name, email')->name('Jonathan')->and_country_not('Canada')->rows();

	// Select all users with no birthday set
	User::select('id, name, email')->birthday_null()->order_by('name')->rows();

	// Select a specific user
	User::select('id, name, email')->id(1)->row();

	// Select a specific user's email
	User::select('email')->id(1)->field();

	// Select COUNT of users in Canada
	User::select('COUNT(*)')->country('Canada')->field();
```

### Returned objects

Query subscribes to the belief that `SELECT *` should never be used, unless prior to performing an `UPDATE` or `DELETE` query. Since these are methods built into the Table ORM, when you do select all fields, Select will automatically return `Model` objects.

Conversely, when selecting specific table fields, Select will return `stdClass` objects. Some examples:

```php
<?php

	$user = User::select()						// returns User
	$user = User::select('*')					// returns User
	$user->update()								// Works
	$user->delete()								// Works

	$user = User::select('id, name, email')		// returns stdClass
	$user = User::select('COUNT(*)')			// returns stdClass
	$user->update()								// Does not work
	$user->delete()								// Does not work
```

## Query logging

Query has a built-in logger, which tracks all queries run, including those executed by the Table ORM and Select Query Builder. The logger keeps a record of the SQL, bindings, and execution time of each query. To view this information (as an array), simply call the `log()` method:

```php
<?php

	$log = DB::log();
```