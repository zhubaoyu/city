<?php
namespace City\City;

use \City\DB\Adapter;
use \City\DB\CatchedAdapter;
use \City\City;

class Mapper
{
    use \City\Time;
    private static $_fields = array(
        'player_id' => ':player_id',
        'name' => ':name',
        'coordinate_x' => ':coordinate_x',
        'coordinate_y' => ':coordinate_y',
        'type' => ':type',
        'tax_rate' => ':tax_rate',
        'food' => ':food',
        'gold' => ':gold',
        'population' => ':population',
        'time_at_creation' => ':time_at_creation',
        'time_at_last_food' => ':time_at_last_food',
        'time_at_last_tax' => ':time_at_last_tax',
    );

    public static function create (City $city)
    {

        $sql = 'INSERT INTO city(' 
            . implode(',', array_keys(self::$_fields))
            . ') VALUES('
            . implode(',', array_values(self::$_fields))
            . ')';
        $cityInfo = array(
            ':player_id' => $city->getPlayerId(),
            ':name' => $city->getName(),
            ':coordinate_x' => $city->getCoordinateX(),
            ':coordinate_y' => $city->getCoordinateY(),
            ':type' => $city->getType(),
            ':tax_rate' => $city->getTaxRate(),
            ':food' => $city->getFood(),
            ':gold' => $city->getGold(),
            ':population' => $city->getPopulation(),
            ':time_at_creation' => self::toDateTimeString($city->getTimeAtCreation()),
            ':time_at_last_food' => self::toDateTimeString($city->getTimeAtLastFood()),
            ':time_at_last_tax' => self::toDateTimeString($city->getTimeAtLastTax()),
        );

        
        $ret  = CatchedAdapter::create($sql, $cityInfo);
        if ($ret === false) {
            return false;
        }

        $city->setId($ret);
        return true;
    }

    public static function findById($id)
    {
        assert($id>0,"city id:{$id} must greater than 0");
        $sql = 'SELECT * FROM city WHERE id=:id';   
        $info = array(':id' => $id);
        $data = CatchedAdapter::select($sql, $info);
        if ($data === false) {
            return false;
        }
        if(empty($data)) {
            return null;    
        }

        return Builder::build($data[0]);
    }

    public static function update(City $city)
    {   
        if (!$city->needSave()) {
            return 0;   
        }
        $sql = self::_getUpdateSql();
        $cityInfo = self::_getUpdateInfo($city);

        return CatchedAdapter::update($sql, $cityInfo);
    }

    private static function _getUpdateSql() 
    {
        $fields = array();
        foreach (self::$_fields as $k => $v) {
            $fields[] = "$k=$v";
        }

        return 'UPDATE city set ' . implode(',', $fields) . ' WHERE id=:id'; 
    }

    private static function _getUpdateInfo(City $city)
    {
         return array(
            ':player_id' => $city->getPlayerId(),
            ':name' => $city->getName(),
            ':coordinate_x' => $city->getCoordinateX(),
            ':coordinate_y' => $city->getCoordinateY(),
            ':type' => $city->getType(),
            ':tax_rate' => $city->getTaxRate(),
            ':food' => $city->getFood(),
            ':gold' => $city->getGold(),
            ':population' => $city->getPopulation(),
            ':time_at_creation' => self::toDateTimeString($city->getTimeAtCreation()),
            ':time_at_last_food' => self::toDateTimeString($city->getTimeAtLastFood()),
            ':time_at_last_tax' => self::toDateTimeString($city->getTimeAtLastTax()),
            ':id' => $city->getId(),
        );
    }

    public static function changeCapital(City $newCapital, City $oldCapital)
    {
        try {
            $pdo = Adapter::getPdo();
            if($pdo->beginTransaction()) {
                $sql = self::_getUpdateSql();
                $newCapitalInfo = self::_getUpdateInfo($newCapital);
                Adapter::update($sql, $newCapitalInfo);
                $oldCapitalInfo = self::_getUpdateInfo($oldCapital);
                Adapter::update($sql, $oldCapitalInfo);
                $pdo->commit();
                return true;
            }
        } catch (\PDOException $e) {
            try{
                $pdo->rollBack();
            } catch (\Exception $e) {}
        } catch (\Exception $e) {}

        return false;
    }

    public static function findByPlayerId($playerId)
    {
        assert($playerId>0,"player id:{$playerId} must greater than 0");
        $sql = 'SELECT * FROM city WHERE player_id=:player_id'; 
        $info = array(':player_id' => $playerId);
        $data = CatchedAdapter::select($sql, $info);
        if (false === $data) {
            return false;
        }

        $cities = array();
        foreach ($data as $d) {
            $cities[] = Builder::build($d);
        }
        return $cities;
    }

    public static function findByPlayerIdAndType($playerId, $type)
    {
        assert($playerId>0,"player id:{$playerId} must greater than 0");
        assert($type>0,"city type:{$type} must greater than 0");
        $sql = 'SELECT * FROM city WHERE player_id=:player_id'
            . ' AND type=:type';    
        $info = array(':player_id' => $playerId, ':type' => $type);
        $data = CatchedAdapter::select($sql, $info);
        if (false === $data) {
            return false;
        }
        $cities = array();
        foreach ($data as $d) {
            $cities[] = Builder::build($d);
        }
        return $cities;
    }
}
