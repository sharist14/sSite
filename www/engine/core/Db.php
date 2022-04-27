<?php


class Database
{
    var $conn;

    function __construct(){
        $this->conn = $this->getConnect();
    }

    public function getConnect(){
        try{
            $mysqli = new mysqli(HOST, USER, PASS, DB);
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