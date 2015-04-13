# Simple PHP Mailing List Docs

## Initiate the class
```php
$mailingList = new SimpleMailingList();
```

## Configure the DB
```php
$db = array(
	'host'=>'localhost',
	'username'=>'me',
	'password'=>'mypassword',
	'database'=>'dbname',
	'table'=>'subscribers'
);
$mailingList->setDbConfig($db);
```

## Subscribe a new email
```php
$mailingList->subscribe('me@example.com');
$mailingList->subscribe('you@example.com');
```

## Unsubscribe an existing email
```php
$mailingList->unsubscribe('you@example.com');
```

## Get a list of subscribers
```php
echo implode(', ',$mailingList->getAllSubscribers());
```

## Get a list of subscribers including those who have opted out
```php
echo implode(', ',$mailingList->getAllSubscribers(true));
```

## Check to see if an email is subscribed
```php
var_dump($mailingList->emailExists('me@example.com'));
```
