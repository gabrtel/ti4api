<?php

class SQLiteConnection {
    private $pdo;
    private $dbFile;

    public function __construct($dbFile, $readonly = false) {
		if (!file_exists($dbFile)) {
			// Create the database file if it doesn't exist
			touch($dbFile);
		}
        $this->dbFile = $dbFile;
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbFile);
            // Set error mode to exceptions
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
			if($readonly){
				$this->pdo->setAttribute(PDO::SQLITE_ATTR_OPEN_FLAGS, PDO::SQLITE_OPEN_READONLY);
			} else{
				$this->pdo->setAttribute(PDO::SQLITE_ATTR_OPEN_FLAGS, PDO::SQLITE_OPEN_READWRITE);
			}
			// Set other attributes as needed
			$this->pdo->query('PRAGMA auto_vacuum = FULL;');
			$this->pdo->query('PRAGMA journal_mode = WAL;');
			$this->pdo->query('PRAGMA synchronous = NORMAL;');
            $this->pdo->query('CREATE TABLE IF NOT EXISTS "rooms" (
				"id" INTEGER PRIMARY KEY AUTOINCREMENT,
				"hashes" VARCHAR,
				"game" VARCHAR,
				"players" VARCHAR,
				"paused" VARCHAR,
                "logs" VARCHAR,
                "undo" VARCHAR,
				"others" VARCHAR
				);
			');
			return $this->pdo;
        } catch (PDOException $e) {
            die("SQLite connection failed: " . $e->getMessage());
        }
    }

    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $stmt->bindValue($key, json_encode($value), PDO::PARAM_STR);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();            
            //$stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}

	public function fetchAll($stmt) {
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function fetch($stmt) {
		// Fetch a single row
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			foreach ($row as $key => $value) {
				// Decode JSON strings if needed
				if (is_string($value)) {
					$row[$key] = json_decode($value, true);
				}
			}
			return $row;
		}
		// If no rows were found, return null or an empty array
		return null;
	}

	public function fetchColumn($stmt) {
		return $stmt->fetchColumn();
	}

	// Example usage:
	// Create table example
	//$db->executeQuery("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT)");

	// Insert data
	//$db->executeQuery("INSERT INTO users (name) VALUES (:name)", ['name' => 'Alice']);

    public function closeConnection() {
        $this->pdo = null;
    }
}




