<?php
/**
*	A super simple PHP / MySQL mailing list well suiting for websites prelaunch. Can add an email to the subscriptions, unsubscribe, and get a full list of available subscribers.
*
*	Even creates the table if it does not exist and the credentials have proper access.
*/
class SimpleMailingList extends SimpleMailingListDb
{

	/**
	*	Add a new email address to the MySQL database
	*	@param string $email The email to the added to the DB
	*	@return array
	*/
	public function subscribe($email)
	{
		if (!$this->isValidEmail($email))
		{
			throw new Exception('Invalid email', 1);
		}

		if ($this->emailExists($email))
		{
			$this->resubscribeEmail($email);
		}

		$this->insertEmail($email);
		return $this->getByEmail($email);
	}


	/**
	*	Get all of the data we have on this subscriber
	*	@param string $email The email address of the subscriber
	*	@return boolean
	*/
	public function getSubscriber($email)
	{
		return $this->getByEmail($email);
	}


	/**
	*	Checks to see if the email already exists in the database
	*	@param string $email The email address to be checked
	*	@return boolean
	*/
	public function emailExists($email)
	{
		return !!$this->totalSubscribers($email);
	}


	/**
	*	Gets all subscribers in the MySQL DB
	*	@param boolean $unsubscribed Gets all subscribers included those who are unsubscribed
	*	@return array
	*/
	public function getAllSubscribers($unsubscribed=false)
	{
		$subscribers = $this->getAll();

		function limitByActive($subscriber)
		{
			return $subscriber['active'];
		}

		if (!$unsubscribed)
		{
			$subscribers = array_filter($subscribers, 'limitByActive');
		}

		function getEmail($subscriber) 
		{
			return $subscriber['email'];
		}

		return array_map('getEmail', $subscribers);
	}


	/**
	*	Unsubscribe from this list
	*	@param string $email The email address to be unsubscribed
	*	@return void
	*/
	public function unsubscribe($email)
	{
		if (!$this->emailExists($email))
		{
			throw new Exception('Email is not subscribed.', 1);
		}

		$this->unsubscribeEmail($email);
	}


	/**
	*	Make sure this is a validate email
	*	$param string $email The email address to be checked
	*	@return array
	*/
	private function isValidEmail($email)
	{
		return preg_match('/^[a-z0-9]+([_\.-][a-z0-9]+)*@([a-z0-9]+([.-][a-z0-9]+)*)+\.[a-z]{2,}$/i', $email);
	}
}


class SimpleMailingListDb
{

	private $db;
	private $tableExists;

	protected $host;
	protected $username;
	protected $password;
	protected $database;

	protected $subscriptionsTable;


	public function __construct()
	{
		$this->host = '';
		$this->username = '';
		$this->password = '';
		$this->database = '';

		$this->subscriptionsTable = 'subscribers';
	}


	/**
	*	Set the DB config from a separate file. Never expose sensitive data.
	*	@param array $config Must contain the pertinent data to connect to the MySQL DB
	*		Required keys include: host, username, password, database
	*		Optional key is table for the name of the subscriptions table
	*	@return void
	*/
	public function setDbConfig($config)
	{
		// Make sure all required fields have values
		$requiredFields = array('host', 'username', 'password', 'database');
		foreach ($requiredFields as $field)
		{
			if (!$config[$field])
			{
				throw new Exception('Missing MySQL ' . $field, 1);
			}
		}

		$this->host = $config['host'];
		$this->username = $config['username'];
		$this->password = $config['password'];
		$this->database = $config['database'];

		if ($config['table'])
		{
			$this->subscriptionsTable = $config['table'];
		}

		$this->openConnection();

		if (!$this->tableExists)
		{
			$this->checkForTable();
		}
	}


	/**
	*	Open a new MySQL connection. If a current connection is open it will be overwritten
	*	@return void
	*/
	protected function openConnection()
	{
		$this->db = new mysqli($this->host, $this->username, $this->password, $this->database);
		if ($this->db->connect_errno) {
			throw new Exception('Could not connect to the MySQL DB. ' . $this->db->connect_error, 1);
		}
	}


	/**
	*	Checks to see if the email already exists in the database
	*	@param string $email The email address to be checked
	*	@return boolean
	*/
	protected function get($email = '')
	{
		$fields = '*';
		$filter = '';

		if ($email)
		{
			$filter = ' WHERE email = "'.$email.'"';
		}

		return $this->query('SELECT ' . $fields . ' FROM ' . $this->subscriptionsTable . $filter);
	}


	/**
	*	Get single subscriber by email
	*	@return array
	*/
	protected function getByEmail($email)
	{
		return $this->get($email)->fetch_assoc();
	}


	/**
	*	Get all the subscribers
	*	@return array
	*/
	protected function getAll()
	{
		return $this->get()->fetch_all(MYSQLI_ASSOC);
	}


	/**
	*	Get the total number of active subscribers
	*	@param string $email The optional email address to filter by
	*	@return integer
	*/
	protected function totalSubscribers($email='')
	{
		return $this->get($email)->num_rows;
	}


	/**
	*	Add the email as active to the MySQL DB
	*	@param string $email The email to be added
	*	@return void
	*/
	protected function insertEmail($email)
	{
		$this->db->query('INSERT INTO '.$this->subscriptionsTable.' SET email = "'.$email.'", active = 1')
			or die(mysqli_error($this->db));
	}


	/**
	*	Add the email as active to the MySQL DB
	*	@param string $email The email to be added
	*	@return void
	*/
	protected function unsubscribeEmail($email)
	{
		$this->db->query('UPDATE '.$this->subscriptionsTable.' SET active = 0 WHERE email = "' . $email . '"')
			or die(mysqli_error($this->db));
	}


	/**
	*	Add the email as active to the MySQL DB
	*	@param string $email The email to be added
	*	@return void
	*/
	protected function resubscribeEmail($email)
	{
		$this->db->query('UPDATE '.$this->subscriptionsTable.' SET active = 1 WHERE email = "' . $email . '"')
			or die(mysqli_error($this->db));
	}


	/**
	*	Executes a query against the MySQL DB
	*	@param string $query The MySQL query string you wish to execute
	*	@return object
	*/
	private function query($query)
	{
		$result = $this->db->query($query);
		if (!$result)
		{
			throw new Exception('Could not complete MySQL query. '. $this->db->error, 1);
		}
		return $result;
	}


	/**
	*	Checks to make sure the subscriptions table exists. Creates it if it is missing
	*	@return void
	*/
	private function checkForTable()
	{
		try
		{
			$result = $this->query('select 1 from ' . $this->subscriptionsTable);
		}
		catch(Exception $e)
		{
			if ($this->db->error !== 'Table \''.$this->database.'.'.$this->subscriptionsTable.'\' doesn\'t exist')
			{
				die('No table found');
			}

			$sql = 'CREATE TABLE IF NOT EXISTS `'.$this->subscriptionsTable.'` ('.
				'`id` int(11) NOT NULL AUTO_INCREMENT,'.
				'`email` varchar(512) NOT NULL,'.
				'`active` varchar(1) NOT NULL DEFAULT \'1\','.
				'PRIMARY KEY (`id`)'.
			')';
			$this->db->query($sql);

			$this->tableExists = true;
		}
	}
}
