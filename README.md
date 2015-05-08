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

## Exporting data

You first need a `map` file describing the database relationships of the data
that's about to be exported.

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
  replace:
    user_id: 1
  uniqueKeys:
    - [user_id, date]
```

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

This should import into the staging database all books from 2014 that have been previously exported from production. Existing rows with the same unique keys are skipped, according to the map file. Also all imported reviews will appear to be from user 1, as per the map definition. This might be specially useful to omit personal information, or to avoid importing unecessary relationships.
