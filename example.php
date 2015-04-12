<?php

// Initiate the class
$mailingList = new SimpleMailingList();
// Configure the DB
$db = array(
	'host'=>'localhost',
	'username'=>'me',
	'password'=>'mypassword',
	'database'=>'dbname',
	'table'=>'subscribers'
);
$mailingList->setDbConfig($db);

// Subscribe a new email
$mailingList->subscribe('me@example.com');
$mailingList->subscribe('you@example.com');

// Unsubscribe an existing email
$mailingList->unsubscribe('you@example.com');

// Get a list of subscribers
echo implode(', ',$mailingList->getAllSubscribers());

// Get a list of subscribers including those who have opted out
echo implode(', ',$mailingList->getAllSubscribers(true));

// Checl to see if an email is subscribed
var_dump($mailingList->emailExists('me@example.com'));
