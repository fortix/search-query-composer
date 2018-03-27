Search Query Composer
=====================

This library is intended for use in cases, where you want to extend the search abilities of simple search input. It takes the input from user, and tries to interpret them as an expression.
For example, user enters something like **a AND NOT (b OR c)** and this library will tokenize this input and construct the valid SQL statement fragment like
```sql
`field` LIKE '%a%'
AND NOT(
  `field` LIKE '%b%'
  OR
  `field` LIKE '%c%'
)
```

Features
--------

* PSR-4 autoloading compliant structure
* Supports operators AND, OR, NOT
* Supports subquery nesting
* Easy to use

Use
---

Basic usage is very simple:

```php
$userInput = 'a AND NOT (b OR c)';
echo RisoToth\Database\SearchQuery::tokenizeAndCompose($userInput, '`field`');
```