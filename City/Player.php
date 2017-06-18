<?php
namespace City;

class Player
{
    const MAX_CITY_COUNT =10;

    private $_id;
    private $_name;

    private $_cities = array();

    public function __construct($name, $id = null)
    {
        assert(!empty($name),"player name must not be empty");
        $this->_name = $name;
        if (!is_null($id)) {
            $this->setId($id);
        }
    }

    public function getId()
    {
        return $this->_id;  
    }

    public function setId($id)
    {
        assert($id>0,"player id:$id, must greater than 0");
        $this->_id = $id;
    }


    public function getName()
    {
        return $this->_name;
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
        if (self::MAX_CITY_COUNT == $this->getCityCount()) {
            return false;
        }
        $city = new City($this->_id, $name, $x, $y, $type);
        return ($ret=City\Mapper::create($city)) ? $city : $ret;
    }

    public function createCapital($name, $x, $y)
    {
        return !$this->getCapital()
            ? $this->createCity($name, $x, $y, City::CAPITAL)
            : null;
    }

    public function getCity($cityId) 
    {
        assert($cityId>0,"city id:{$cityId} must greater than 0");
        if($data = City\Mapper::findById($cityId)) {
            if ($data->getPlayerId()==$this->_id) {
                return $data;
            }
        }
    }

    public function getCapital()
    {
        if($data = City\Mapper::findByPlayerIdAndType($this->_id, City::CAPITAL)) {
            $data = $data[0];
            if($data->getPlayerId() == $this->_id) {
                return $data;
            }
        }
    }

    public function changeCapital($oldId, $newId)
    {
        assert($oldId>0&&$newId>0,"city id:{$oldId},{$newId} must greater than 0");
        $oldCapital = $this->getCity($oldId);
        $newCapital = $this->getCity($newId);
        if (!$oldCapital || !$newCapital) {
            return false;
        }

        $oldCapital->setType(City::REGULAR);
        $newCapital->setType(City::CAPITAL);
        return City\Mapper::changeCapital($newCapital, $oldCapital);
    }

    public function changeCityTaxRate($cityId, $taxRate)
    {
        assert($cityId>0,"city id:{$cityId} must greater than 0");
        assert($taxRate>=0,"tax rate:{$taxRate} must greater than 0");
        $city = $this->getCity($cityId);
        if($city->getPlayerId() != $this->_id) {
            return false;
        }

        $city->setTaxRate($taxRate);
        return $this->saveCity($city);
    }

    public function saveCity(City $city)
    {
        $city->soldiers()->save();
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
        assert($cityId>0,"city id:{$cityId} must greater than 0");
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
