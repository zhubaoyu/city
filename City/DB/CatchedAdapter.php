<?php
namespace City\DB;
class CatchedAdapter
{
    public static function create($sql, $data) 
    {
        try {
            return Adapter::create($sql, $data);
        } catch (\PDOException $e) { }
            return false;
    }

    public static function update($sql ,$data)
    {
        try {
            return Adapter::update($sql, $data);
        } catch(\PDOException $e) { }
            return false;
    }
    
    public static function select($sql ,$data)
    {
        try {
            return Adapter::select($sql, $data);
        } catch(\PDOException $e) { }
            return false;
    }
    
    public static function delete($sql ,$data)
    {
        return self::update($sql, $data);
    }
}

