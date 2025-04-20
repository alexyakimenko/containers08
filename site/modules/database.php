<?php

class Database {
    private PDO $connection;

    public function __construct(string $path) {
        try {
            $this->connection = new PDO("sqlite:$path");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public function Execute(string $sql): bool {
        try {
            $statement = $this->connection->prepare($sql);
            return $statement->execute();
        } catch (PDOException $e) {
            die("Query execution error: " . $e->getMessage());
        }
    }

    public function Fetch(string $sql): array {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Query fetch error: " . $e->getMessage());
        }
    }

    public function Create(string $table, array $data): false|string {
        try {
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));

            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $statement = $this->connection->prepare($sql);

            foreach ($data as $key => $value) {
                $statement->bindValue(":$key", $value);
            }

            $statement->execute();
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            die("Create record error: " . $e->getMessage());
        }
    }

    public function Read(string $table, int $id): mixed {
        try {
            $sql = "SELECT * FROM $table WHERE id = :id LIMIT 1";
            $statement = $this->connection->prepare($sql);
            $statement->bindValue(":id", $id);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            die("Read record error: " . $e->getMessage());
        }
    }

    public function Update(string $table, int $id, array $data): bool {
        try {
            $setParts = [];
            foreach (array_keys($data) as $key) {
                $setParts[] = "$key = :$key";
            }
            $setClause = implode(", ", $setParts);

            $sql = "UPDATE $table SET $setClause WHERE id = :id";
            $statement = $this->connection->prepare($sql);

            $statement->bindValue(":id", $id);
            foreach ($data as $key => $value) {
                $statement->bindValue(":$key", $value);
            }

            return $statement->execute();
        } catch (PDOException $e) {
            die("Update record error: " . $e->getMessage());
        }
    }

    public function Delete(string $table, int $id): bool {
        try {
            $sql = "DELETE FROM $table WHERE id = :id";
            $statement = $this->connection->prepare($sql);
            $statement->bindValue(":id", $id);

            return $statement->execute();
        } catch (PDOException $e) {
            die("Delete record error: " . $e->getMessage());
        }
    }

    public function Count(string $table): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            die("Count records error: " . $e->getMessage());
        }
    }
}
