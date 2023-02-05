<?php
/**
 * Simple class for connection
 * and sending query statements
 * and receive response from DB
 */
class DBConnector {
    const JSON = "JSON";
    const ASSOC_ARRAY = "ASSOC_ARRAY";
    private $connection;
    private $output_type = self::JSON;

    /**
     * Initializes a new connection
     *
     * @param string $db_name
     * @param string $db_driver @example mysql
     * @param string $db_host @example 127.0.0.1
     * @param string $db_port @example 3306
     * @param string $db_user @example root
     * @param string $db_pass @example root
     * @return PDO pdo instance
     */
    public function __construct (
        $db_name,
        $db_user = 'root', 
        $db_pass = 'root',
        $db_driver = 'mysql',
        $db_host = '127.0.0.1', 
        $db_port = '3306') {

        $dns = $db_driver.':'.'dbname='.$db_name.';host='.$db_host.';port='.$db_port;
        
        $this->connect($dns, $db_user, $db_pass);
    }

    /**
     * Trys build database connection in PDO
     *
     * @param string $dns
     * @param string $db_user
     * @param string $db_pass
     * @return void
     */
    private function connect ($dns, $db_user, $db_pass) {
        try {
            $this->connection = new \PDO($dns, $db_user, $db_pass);
        } catch (\PDOException $e) {
            $this->connection = null;
        } 
    }

    /**
     * Executes given sql statement
     * 
     * Optional:
     *      parameters as assosiate array,
     *      fetching type as PDO fetch constant
     *
     * @param string $sql
     * @param array $params
     * @param const $fetch_type
     * @return json string
     */
    public function statement($sql, $params = [], $fetch_type = \PDO::FETCH_ASSOC) {
        if ($this->connection != null) {
            $statement = $this->connection->prepare($sql);

            foreach($params as $paramName => $paramValue) {
                $statement->bindParam($paramName, $paramValue);
            }
            $statement->execute();

            $error_code = $statement->errorInfo()[1];
            $error_message = $statement->errorInfo()[2];
            if (!empty($error_code)) {
                switch($error_code) {
                    case 1064: return $this->generate_result(true, $error_message);
                }
            }
            $content = $statement->fetchAll($fetch_type);
            return $this->generate_result(false, $content);
        }
    }

    /**
     * Generates result as json object
     *
     * @param boolean $is_error
     * @param string $content
     * @return void
     */
    private function generate_result($is_error, $content) {
        switch ($this->output_type) {
            case self::ASSOC_ARRAY: 
                return ['error' => $is_error, 'content' => $content];
            case self::JSON: 
            default:
                return json_encode(['error' => $is_error, 'content' => $content]);
        }
    }

    /**
     * Sets output type of statement
     *
     * @param OUTPUT $output_type
     * @return void
     */
    public function set_output_type($output_type) {
        $this->output_type = $output_type;
    }

    /**
     * Closes the database connection
     *
     * @return void
     */
    public function close () {
        $this->connection = null;
    }

    /**
     * Destructs the pdo instance
     */
    public function __destruct() {
        $this->connection = null;
    }
}