<?php
namespace City\Player;

use City\DB\CatchedAdapter;
use City\Player;

class Mapper
{
    public static function findById($id)
    {
        
        $sql = 'SELECT * FROM player WHERE id=:id';
        $info = array(':id' => $id);
        $data = CatchedAdapter::select($sql, $info);
        if (false === $data) {
            return false;
        }
        if(empty($data)) {
            return null;    
        }
        $data = $data[0];

        return new Player($data['name'], $data['id']);
    }

    public static function create (Player $player)
    {
        $sql = 'INSERT INTO player(name) VALUES(:name)';    
        $info = array(':name' => $player->getName());
        $ret = CatchedAdapter::create($sql, $info);
        if (false === $ret){
            return false;
        }
        $player->setId($ret);
        return true;
    }
}

