<?php
namespace City\DB;
use \City\Config;

class Adapter
{
    private static $_pdo;

    public static function init()
    {
        $dsn = 'mysql:dbname='.Config::DATABASE.';host='.Config::HOST;
        $options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8');
        return new \PDO($dsn,Config::USER,COnfig::PASSWD,$options);
    }

    public static function getPdo()
    {
        if (empty(self::$_pdo)) {
            self::$_pdo = self::init();
        }
        return self::$_pdo;
    }

    public static function create ($sql, $data) 
    {
        $pdo = self::getPdo();
        $sth = $pdo->prepare($sql);
        $sth->execute($data);   
        //var_dump($sth->errorInfo());exit;
        return $pdo->lastInsertId();
    }

    public static function select($sql, $data)
    {
        $sth = self::getPdo()->prepare($sql);     
        $sth->execute($data);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function update($sql, $data)
    {
        $sth = self::getPdo()->prepare($sql);
        $sth->execute($data);
        //var_dump($sth->errorInfo());
        return $sth->rowcount();
    }

    public static function delete($sql, $data)
    {
        return self::update($sql, $data);   
    }
}
