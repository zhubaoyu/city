<?php
namespace City;

class PlayerException extends \Exception 
{
    const PLAYER_ID_ERROR = 1;
    const PLAYER_NAME_ERROR = 2;    
    const PLAYER_CITY_COUNT_ERROR = 3;
    const PLAYER_CITY_MAX_COUNT_ERROR = 4;
    const CAPITAL_MORE_THAN_ONE_ERROR = 5;
    const PLAYER_ID_NOT_MATCH_ERROR = 6;
    const CITY_NOT_EXIST_ERROR = 7; 
    const CITY_NOT_CAPITAL_ERROR = 8;
    const CITY_NOT_REULAR_ERROR = 9;
}

class Player
{
    const MAX_CITY_COUNT =10;

    private $_id;
    private $_name;

    private $_cities = array();

    public function __construct($name, $id = null)
    {
        $this->_setName($name); 
        if (!is_null($id)) {
            $this->_setId($id);
        }
    }

    public function getId()
    {
        return $this->_id;  
    }

    public function setId($id)
    {
        $this->_setId($id); 
    }

    private function _setId($id)
    {
        if($id <= 0) {
            throw new PlayerException("player id:$id, must greater than zero", 
                    PlayerException::PLAYER_ID_ERROR);   
        }

        $this->_id = $id;       
    }

    public function getName()
    {
        return $this->_name;    
    }

    private function _setName($name)
    {
        if(empty($name)) {
            throw new PlayerException("player name must not be empty", 
                    PlayerException::PLAYER_NAME_ERROR); 
        }

        $this->_name = $name;   
    }

    public function getCityCount()
    {
        $this->getCities();
        return count($this->_cities);
    }

    public function getCities()
    {
        if (!empty($this->_cities)) {
            return $this->_cities;  
        }   

        if (is_null($this->_id)) {
            return array(); 
        }
        $this->_cities = City\Mapper::findByPlayerId($this->_id);
        return $this->_cities;
    }

    public function createCity($name, $x, $y, $type = City::REGULAR)
    {
        if (self::MAX_CITY_COUNT == count($this->_cities)) {
            throw new PlayerException("every player can't have more than " 
                    . PlayerException::PLAYER_CITY_MAX_COUNT_ERROR);
        }
        $city = new City($this->_id, $name, $x, $y, $type);
        City\Mapper::create($city);
        return $city;
    }

    public function createCapital($name, $x, $y)
    {
        if ($this->getCapital()) {
            throw new PlayerException(
                "player:{$this->_id} already have a capital",
                PlayerException::CAPITAL_MORE_THAN_ONE_ERROR);
        }

        return $this->createCity($name, $x, $y, City::CAPITAL);        
    }

    public function getCity($cityId) 
    {
        $data = City\Mapper::findById($cityId); 
        if (empty($data)) {
            return $data;   
        }

        if($data->getPlayerId() != $this->_id) {
            throw new PlayerException(
                "playerid:{$this->_id} does not match player".
                " id:{$data->getPlayerId()} in city:{$this->getId()}",
                PlayerException::PLAYER_ID_NOT_MATCH_ERROR);
        }
        
        return $data;
    }

    public function getCapital()
    {
        $data = City\Mapper::findByPlayerIdAndType($this->_id, City::CAPITAL);  
        if (empty($data)) {
            return $data;
        }
        $data = $data[0];

        if($data->getPlayerId() != $this->_id) {
            throw new PlayerException(
                "playerid:{$this->_id} does not match player".
                " id:{$data->getPlayerId()} in city:{$this->getId()}",
                PlayerException::PLAYER_ID_NOT_MATCH_ERROR);
        }
        
        return $data;
    }

    public function changeCapital($oldId, $newId)
    {
        $oldCapital = $this->getCity($oldId);
        if (!$oldCapital) {
            throw new PlayerException(
                "city:{$oldCapital->getId()} doesn't exist",
                PlayerException::CITY_NOT_EXIST_ERROR);
        }
        if ($oldCapital->getType() != City::CAPITAL) {
            throw new PlayerException(
                "city:{$oldCapital->getId()} isn't the captical",
                PlayerException::CITY_NOT_CAPITAL_ERROR);
        }
        $oldCapital->setType(City::REGULAR);

        $newCapital = $this->getCity($newId);
        if (!$newCapital) {
            throw new PlayerException(
                "city:{$newCapital->getId()} doesn't exist",
                PlayerException::CITY_NOT_EXIST_ERROR);
        }

        if ($newCapital->getType() != City::REGULAR) {
            throw new PlayerException(
                "city:{$newCapital->getId()} isn't a regular",
                PlayerException::CITY_NOT_REULAR_ERROR);
        }
        $newCapital->setType(City::CAPITAL);
        return City\Mapper::changeCapital($newCapital, $oldCapital);
    }

    public function changeCityTaxRate($cityId, $taxRate)
    {
        $city = $this->getCity($cityId);
        if ($city->getPlayerId() != $this->_id) {
            throw new PlayerException(
                "player id :{$this->_id} not match that in" .
                "city:{$city->getPlayerId()}",
                PLAYER_ID_NOT_MATCH_ERROR);
        }

        $city->setTaxRate($taxRate);
        return $this->saveCity($city);
    }

    public function saveCity(City $city)
    {
        return City\Mapper::update($city); 
    }

    public function getCitiesInfo()
    {
        $citiesInfo = array();
        foreach ($this->getCities() as $city) {
            $citiesInfo[] = array(
                'cityId' => $city->getId(),
                'food' => $city->getFood(),
                'gold' => $city->getGold(),
                'population' => $city->getPopulation(),
                'taxRate' => $city->getTaxRate(),
                'soldiers' => $this->_getSoldiersInfo($city),
                'trainingSoldiers' => $this->_getTrainingSoldiersInfo($city),
                'soldiersBatch' => $this->_getSoldierBatchesInfo($city),
            );  
        }
        return $citiesInfo;
    }

    public function getCityInfo($cityId)
    {
        $city = $this->getCity($cityId);
        if (empty($city)) {
            return array(); 
        }

        return array(
            'cityId' => $city->getId(),
            'food' => $city->getFood(),
            'gold' => $city->getGold(),
            'population' => $city->getPopulation(),
            'taxRate'   => $city->getTaxRate(),
            'soldiers' => $this->_getSoldiersInfo($city),
            'trainingSoldiers' => $this->_getTrainingSoldiersInfo($city),
            'soldiersBatch' => $this->_getSoldierBatchesInfo($city),
        );
    }

    private function _getSoldiersInfo(City $city)
    {
        $info = array();
        foreach ($city->soldiers()->getSoldiers() as $soldiers) {
            $info[] = array(
                'soldiersId' => $soldiers->getId(),
                'type' => $soldiers->getSoldierType(),
                'num'  => $soldiers->getNum(),
            );  
        }       

        return $info;
    }

    private function _getTrainingSoldiersInfo(City $city)
    {
        $data = $city->soldiers()->getTrainingBatch();
        if(empty($data)) {
            return array(); 
        }
        return array(
            'type' => $data->getSoldierType(),
            'num' => $data->getNum(),
        );  
    }

    private function _getSoldierBatchesInfo(City $city)
    {
        $info = array();
        foreach($city->soldiers()->getBatches() as $batch) {
            $info[] = array(
                'type' => $batch->getSoldierType(),
                'num'  => $batch->getNum(),
            );
        }

        return $info;
    }
}
