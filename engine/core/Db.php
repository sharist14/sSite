<?php


class Database
{
    var $conn;
    var $cur_dbname;

    function __construct($user='', $pass='', $db=''){
        $this->conn = $this->getConnect($user, $pass, $db);
    }

    public function getConnect($user, $pass, $db){
        if(!$user || !$pass || !$db){
            $user = USER;
            $pass = PASS;
            $db = DB;
        }

        $this->cur_dbname = $db;

        try{
            $mysqli = new mysqli('localhost', $user, $pass, $db);
            $mysqli->set_charset('utf8');

            if (!mysqli_connect_errno())            {
                return $mysqli;
            }
            else            {
                throw new Exception("Connect failed: " . mysqli_connect_error());
            }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            die();
        }
    }

    function q($sql){
        $result = $this->conn->query($sql);

        return $result;

    }
}