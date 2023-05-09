## About Validator

A PHP package that allows you to handling form request validation.

## Requirements

- PHP "^8.0"



## Installation

You can install the package via composer:

```bash
composer require satanvir/validator
```



## Usage

Here's an example of how to use this Validator package for validating input reques: 

```php
use Satanvir\Validator\Validator;

$validator = new Validator($config); // Validator(array $config = [])
```


## Passing  User's Inputs

```php

$validator->request($_POST); // Validator::request(array $inputs): self 
```

## Define Rules

You can define rules by two ways.
1. Bulk way
2. Single Input

### Bulk way

```php

$rules = [
'name' => 'required|min:3|max:60',
'email' => ['required', 'email']
];

$validator->rules($rules); // Validator::rules(array $rules): self
 
```

### Single Input

```php

$validator->rule('email', 'required|email'); // Validator::rule(string $name, array|string $rule): self

 
```

## Validation

```php

$validator->validate(); // Validator::validate(): self
 
```
*** This method validate the given inputs by using the given rules ***

### Check: Validation has failed

```php
if ($validation->fails()) {
// do something
}
// Validator::fails(): bool
 
```
*** Return true if validation failed ***

### Check: Validation has passed

```php

if ($validation->passed()) {
// do something
}
// Validator::passed(): bool
 
```
*** Return true if validation passed ***

## Getting Errors

### Getting all errors

```php

foreach ($validator->errors() as $error) {
// do something
}
// Validator::errors(): array

 
```
*** errors() method returns all generated errors from validators ***

### Getting all errors of a single input

```php

foreach ($validator->error('email') as $error) {
// do something
}
// Validator::error(string $name): array
 
```

### Getting first error


```php

echo $validator->errorFirst();
//or
echo $validator->errorFirst('email');
// Validator:errorFirst(?string $name = null): ?string

```

*** if did not pass any param, then return first error from all otherwise return from specific input. ***
