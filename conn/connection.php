<?php
class Connection {
    public $dbUrl;
    public $uname;
    public $pwd = '';
    public $dbName;
    private $conn;
    public $varData = [];
    function __construct($uname,$pwd) {
        $this->uname = $uname;
        $this->pwd = $pwd;
        $this->dbUrl = "localhost";
        $this->dbName = "restTest";
        $this->dsn = 'mysql:dbname=restTest;host=localhost';

    }
    function connectToDb() {
        try{
            $this->conn = new PDO(
                $this->dsn,
                $this->uname,$this->pwd
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Error connecting: " . $e->getMessage();
        }
    }
    function executeLoginTimestampUpdate($args) {
        $this->varData = array(
            ":username" => $args['username'],
            ":password" => $args['password']
        );
        try {
        $stmt = $this->conn->prepare('UPDATE Users SET lastLogin=NOW() WHERE username=:username AND Users.password=:password');
        $stmt->execute($this->varData);
        } catch (PDOException $e) {
            $error = $stmt->errorInfo();
            var_dump($error);
            echo $e->getMessage();
        }
        if($stmt->rowCount() == 1) {
            //login succeeded
            //get the role
            $role = $this->getRoleType();
            return true;
        } else {
            //login failed
            return false;
        }
    }
    function getRoleType() {
        try {
            $stmt = $this->conn->prepare('SELECT role FROM Users WHERE username=:username AND Users.password=:password');
            $stmt->execute($this->varData);

        } catch (PDOException $e) {
            $error = $stmt->errorInfo();
            var_dump($error);
            echo $e->getMessage();
        }
        $role = $stmt->fetchColumn();
        return $role;
    }
    function getUserList() {
        $stmt = $this->conn->prepare("SELECT username FROM Users");
        $stmt->execute();
        $result = $stmt->fetchAll();
        //remove the non-numeric indexes
        foreach($result as $key => $value) {
            echo $key.'<br/>'.$value.'<br/>';
            foreach($value as $k => $v) {
                echo $k.'<br/>'.$v.'<br/>';
                if(is_numeric($v)) {
                    unset($value[$k]);
                }
            }
        }
        return json_encode($result);
    }
    function getRefOrg($params) {
        $stmt = $this->conn->prepare("SELECT * FROM RefOrg WHERE referringOrganization=:org AND referringProgOrLocation=:prog");
        $this->varData = array(
            ':org' => $params['org'],
            ':prog' => $params['prog']
        );
        $stmt->execute($this->varData);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        // var_dump($res);
        return $res;
    }
    function entryExists($params) {
        $stmt = $this->conn->prepare("SELECT * FROM RefOrg WHERE referringOrganization=:org AND referringProgOrLocation=:prog");
        $this->varData = array(
            ':org' => $params['org'],
            ':prog' => $params['prog']
        );
        $stmt->execute($this->varData);
        if($stmt->rowCount > 0) {
            //exists
            return true;
        } else {
            //doesn't exist
            return false;
        }
    }
    function updateRefOrg($params) {
        $stmt = $this->conn->prepare("UPDATE RefOrg SET
        referringOrganization=:org,
        referringProgOrLocation=:prog,
        orgAddressOne=:orgAddressOne,
        orgAddressTwo=:orgAddressTwo,
        orgAddressCity=:orgAddressCity,
        orgAddressState=:orgAddressState,
        orgAddressZip=:orgAddressZip 
        WHERE 
        referringOrganization=:org AND referringProgOrLocation=:prog");
        $this->varData = array(
            ':org' => $params['org'],
            ':prog' => $params['prog'],
            ':orgAddressOne' => $params['orgAddressOne'],
            ':orgAddressTwo' => $params['orgAddressTwo'],
            ':orgAddressCity' => $params['orgAddressCity'],
            ':orgAddressState' => $params['orgAddressState'],
            ':orgAddressZip' => $params['orgAddressZip'],            
        );
        $stmt->execute($this->varData);
        return ($stmt->rowCount > 0);
    }
    function createNewRefOrg($params) {

    }
    function disconnect() {
        try {
            $this->conn = null;
        } catch(PDOException $e) {
            echo $e->message();
        }
    }
}
?>