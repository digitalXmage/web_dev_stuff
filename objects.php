<?php

abstract class File_management
{
	protected $filename;
	protected $fd;
	protected $permissions;
	protected $buffer;

	/*needs to log itself, as nothing is logging this object's behaviour,as it's used for every other object's behaviour*/

	public function __construct($filename,$permissions)
	{
		$this->filename=$filename; 
		$this->permissions=$permissions;
	}
	public function open_file()
	{
		if(($this->fd = fopen($this->filename,$this->permissions))==false)
		{
			die("fopen failed: ");	
		}	
	}
	public function close_file()
	{
		fclose($this->fd);
	}

	abstract protected function write_file();
	abstract protected function read_file();

}

//log be a compounding object, so it appears inside other classes defined
class Log extends File_management
{
	//filename to log to, can have different files for logging different things. or to the same file.

	public function set_log_message($buffer)
	{
		$this->buffer=$buffer;
	}

	//assume we're open in append mode
	public function write_file()
	{
		//fprintf($this->fd,"%d [occured at (date and time here)...	
	}
	
	public function read_file()
	{

	}
}

//use for security
class Filter_info
{
	
	protected $err_msg; //use for the logging class , in latter version.

	function clean_input(&$buffer)
	{
		$buffer= htmlspecialchars( stripslashes( trim($buffer))) ;
	}

	//returns 0 if clean,else -1
	function letters_only(&$buffer)
	{
		if(!preg_match("/^[a-zA-Z]+$/",$buffer))
		{
			echo "[!]Letters only[!]";
			return -1;
		}
		return 0;
	}

	function numbers_only(&$buffer)
	{
		if(!preg_match("/^[0-9]+$/",$buffer))
		{
			echo "[!]Numbers only[!]";
			return -1;
		}
		return 0;		
	}	
}

class TableRows extends RecursiveIteratorIterator
	{
		function __construct($it)
		{
			parent::__construct($it,self::LEAVES_ONLY);
		}
		
		function current()
		{
			return "<td style='width: 150px; border: 1px solid black;'>" . parent::current(). "</td>";
		}

		function beginChildren()
		{
			echo "<tr>";
		}

		function endChildren()
		{
			echo "</tr>" . "\n";
		}
	}


abstract class Database_manager
{
	/*
	 * database information,use a file_manager class to read a database info file.
	 *
	 * basic functions, then inherit for our own use and overload and change any functions
	 * specifically
	 * */
	protected $db;
	protected $servername;
	protected $username;
	protected $password;
	protected $dbname;
	protected $table;
	 
//	abstract protected function create_database();

	function __construct($username,$password,$servername,$dbname,$table)
	{
		$this->username=$username;
		$this->password=$password;
		$this->servername=$servername;
		$this->dbname=$dbname;
		$this->table=$table;			
	} 

	function connect_to_db()
	{
		$_db="mysql:host=$this->servername;dbname=$this->dbname;charset=utf8mb4";
		$options=array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
		$this->db = new PDO($_db,$this->username,$this->password,$options);
	}

	function disconnect_from_db()
	{
		$this->db =null;
	}

	/*these functions can be defined here, as they are basic commands*/
	public function create_database()
	{
		$sql="create database if not exists " . $this->dbname;
		try
		{
			$this->db->exec($sql);
			echo "database created(if not already)<br>";
		}catch(PDOException $e)
		{
			echo $sql . "<br>" . $e->getMessage();
		}
	}
	public function delete_database()
	{
		try
		{
			$stmt=$this->db->prepare("drop database if exists (dbname) values (:dbname)");
			$stmt->bindParam(':dbname',$this->dbname);
			$stmt->execute();
			echo "database deleted successfully<br>";
		}catch(PDOException $e)
		{
			echo $stmt . "<br>" . $e->getMessage();
		}
	}

	abstract protected function add_table();
	abstract protected function delete_table();

	abstract protected function insert_data($input);
	abstract protected function delete_data($input);

	abstract protected function view_data();
}

//then inherite the database class  for our specific ration_table class
class Ration extends Database_manager
{
	//overload the functions we want with our custom sql statements
	

	public function add_table()
	{
		try
		{
			$sql="create table if not exists rationed_items(
			id INT AUTO_INCREMENT PRIMARY KEY,
			name	VARCHAR(30) NOT NULL,
			serving INT(6),
			days INT(6),
			UNIQUE KEY unique_name (name)
			);";

			/*this is causing the issue,for some reason connection has closed*/
			
			$this->db->exec($sql);
			echo "Table rationed_items created(if not already)<br>";
		}catch(PDOException $e)
		{
			echo $sql . "<br>" . $e->getMessage();
		}	
	}

	public function delete_table()
	{
		try
		{
			$sql="drop table if exists rationed_items;";
			$this->db->exec($sql);
			echo "table deleted";
		}catch(PDOException $e)
		{
			echo $sql . "<br>" . $e->getMessage();		
		}
	}

	public function insert_data($input)
	{
		try
		{
			$sql="insert into rationed_items(name,serving,days) values ('$input[0]','$input[1]','$input[2]') on duplicate key update serving='$input[1]',days='$input[2]'";
			$this->db->exec($sql);

		}catch(PDOException $e)
		{
			echo $sql . "<br>" . $e->getMessage();
		}
	}

	public function delete_data($input)
	{
		try
		{
			$sql="delete from rationed_items where id=$input[0]";
			$this->db->exec($sql);
			echo "Record deleted successfully";		
		}catch(PDOException $e)
		{
			echo $sql . "<br>" . $e->getMessage();
		}
	}

	public function view_data()
	{
		echo "<table style='border: solid 1px black;'>";
		echo "<tr><th>Id</th><th>name</th><th>serving</th><th>days</th></tr>";
		
		try
		{
			$stmt=$this->db->prepare("select id,name,serving,days from rationed_items");
			$stmt->execute();
			$result=$stmt->setFetchMode(PDO::FETCH_ASSOC);

			foreach(new TableRows(new RecursiveArrayIterator($stmt->fetchAll())) as $k=>$v)
			{
				echo $v;
			}
		}catch(PDOException $e)
		{
			echo "Error: " . $e->getMessage();
		}

		echo "</table>";
	
	}	
}



?>
