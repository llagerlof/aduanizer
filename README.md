aduanizer
=========

Aduanizer is an import and export tool to migrate specific rows and their
related data across databases. It was specifically created to help populating
an staging environment with a subset of real data from production.

## Getting started

```
$ git clone https://github.com/garotosopa/aduanizer
$ cd aduanizer
$ composer install
$ bin/aduanizer -h
Usage: aduanizer -e table.primarykey=id [-a adapter.yml] [-m map.yml] [-o data.json]
       aduanizer -i data.json [-a adapter.yml] [-m map.yml]

Actions
  -e  export rows from the specified table according to the given criteria
  -i  import data from the specified file
  -h  display this usage message

Configuration files
  -a  adapter settings file specifying how to connect to the database
  -m  map file describing the relevant database structure
  -o  output data to the specified file
```

## Usage

The example below exports from a production database all books from the year 2014 into an YML file, then imports this file into another database used in an staging environment.

### Mapping tables

The first step is to create a `map` file describing the database relationships between the tables that are going to the be exported.

Example map.yml:

```
- table: book
  uniqueKeys:
    - isbn
  foreignKeys:
    author_id: author
  children:
    review: book_id

- table: author
  uniqueKeys:
    - [country_id, name]

- table: review
  foreignKeys:
    user_id: user
  replaceCode:
    description: return substr($review['description'], 0, 2000);
  uniqueKeys:
    - [user_id, date]

- table: user
  replace:
    address_id: null
    email: aduanizer@example.com

```

The map file is a list of tables with the following parameters:

#### table

The table name.

#### uniqueKeys

List of column names that uniquely identifies each row. Compound unique keys are defined in a list.

#### foreignKeys

Map of column names that are foreign keys pointing to their respective table names.

#### children

Map of table names with related rows pointing to the column name on the foreign table.

#### replace

Map of column names pointing to a string that should replace the actual value.

During export, the replacement string gets outputed to the export file, regardless of the actual value on the database. During import, the replacement string is insert into the database, regardless of the actual value on the export file.

#### replaceCode

Map of column names pointing to a PHP code that's going to be used inside a callback function.

The function in which this code is used accepts only one parameter named the same as the table name. When this function gets called it's passed an array representing the row being processed, indexed by the column names. It must return a replacement string for the

 replaces user emails with a fake address, according to `replace` param in the `user` table. Also, book reviews are limited to 2000 characters, according to `replaceCode` param in the `review` table. This code is compiled into a PHP function that takes a single argument named the same as the table and is passed the array representing the row being processed.

Also all users from reviews  will appear to be have a fake e-mail address, as per the map definition. This might be specially useful to omit personal information, or to avoid importing unecessary relationships. The review descriptions
Then you need a `configuration` file with database parameters.

Example production.yml:

```
adapter: oci8
username: aduanizer
password: 4du4n1z3r
connectionString: 10.0.0.1:1521/MYPROJECT
```

Finally the cli program should be called with an expression specifying the
table and the criteria to be used:

```
bin/aduanizer -e "book.year=2014" -a production.yml -m map.yml -o books-2014.yml
```

This should export all books from year 2014 along with their authors and reviews.

### Importing data

Data can then be imported to another server using the same `map` file and a
different database configuration.

Example staging.yml:

```
adapter: pdo
username: myproject
password: m1pr0j3ct
dsn: mysql:dbname=myproject;host=192.168.56.101
```

```
bin/aduanizer -i books-2014.yml -a staging.yml -m map.yml
```

