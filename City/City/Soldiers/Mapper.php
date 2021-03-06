<?php
namespace City\City\Soldiers;

use \City\City\Soldiers;
use \City\DB\CatchedAdapter;

class Mapper
{
    use \City\Time;

    public function create(Soldiers $soldiers)
    {
        $sql = 'INSERT INTO soldiers(city_id,soldier_type,num,time_at_last_eating)'
            . ' VALUES(:city_id,:soldier_type,:num,:time_at_last_eating)';  
        $data = array(
            ':city_id' => $soldiers->getCityId(),
            ':soldier_type' => $soldiers->getSoldierType(),
            ':num' => $soldiers->getNum(),
            ':time_at_last_eating' => self::toDateTimeString($soldiers->getTimeAtLastEating()),
        );
        $ret = CatchedAdapter::create($sql, $data);
        if (false === $ret) {
            return false;
        }
        $soldiers->setId($ret);
        return true;
    }

    public static function findByCityId($cityId)
    {
        $sql = 'SELECT * FROM soldiers  WHERE city_id=:city_id';
        $info = array(':city_id' => $cityId,);
        $data = CatchedAdapter::select($sql, $info);
        if (false === $data) {
            return false;
        }
        $soldiersArr = array();
        foreach ($data as $d) {
            $soldiers = new Soldiers($d['city_id'],
                $d['soldier_type'],$d['num'],
                strtotime($d['time_at_last_eating']));
            $soldiers->setId($d['id']);
            $soldiersArr[] = $soldiers;
        }
    
        return $soldiersArr;    
    }

    public function update(Soldiers $soldiers)
    {
        if (!$soldiers->needSave()) {
            return 0;
        }
        $sql = 'UPDATE soldiers SET '
        . 'num=:num,time_at_last_eating=:time_at_last_eating'
        . ' WHERE id =:id'; 
        $data = array(
            ':num' => $soldiers->getNum(),
            ':time_at_last_eating' => self::toDateTimeString($soldiers->getTimeAtLastEating()),
            ':id' => $soldiers->getId(),
        );

        return CatchedAdapter::update($sql, $data);
    }
}

