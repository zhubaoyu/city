<?php
namespace City\City\Soldiers;

use \City\City;

class Batch
{
    const PIKEMEN = 1;
    const ARCHER = 2;
    const CAVALRY = 3;
    const BATCH_MAX = 5;

    const STATE_TO_TRAIN = 1;
    const STATE_TRAINING = 2;
    const STATE_TRAINED = 3;
    const STATE_CANCELED = 4;

    const TRAINING_GOLD_PIKEMEN = 1;
    const TRAINING_GOLD_ARCHER = 3;
    const TRAINING_GOLD_CAVALRY = 10;

    const TRAINING_TIME_PIKEMEN = 3 * 60;
    const TRAINING_TIME_ARCHER = 12 * 60;
    const TRAINING_TIME_CAVALRY = 50 * 60;
    
    const BATCH_CREATE_SUCCESS = 6;
    const BATCH_CREATE_FAIL_MAX = 7;
    const BATCH_CREATE_FAIL_POPULATION = 8;
    const BATCH_CREATE_FAIL_INVALID_NUM = 9;
    const BATCH_CREATE_FAIL_INVALID_TYPE = 10;
    const BATCH_CREATE_FAIL_GOLD =11;
    const BATCH_CREATE_FAIL_DB = 12;
    const BATCH_CANCEL_SUCCESS = 13;
    const BATCH_CANCEL_FAIL_NOT_EXIST = 14;
    const BATCH_CANCEL_FAIL_INVALID_CITY_ID = 15;
    const BATCH_CANCEL_FAIL_DB = 16;

    private $_cityId;   
    private $_num;  
    private $_soldierType;
    private $_state;
    private $_timeAtCreation;
    private $_timeAtTraining;
    private $_id;
    private $_needSave = false;

    public static function createSoldiers(City $city, $num, $soldierType)
    {
        assert($num>0,'soldier num must greater than 0');
        assert($soldierType>0,'soldier type must greater than 0');

        $city->develop();
        if($num > $city->getPopulation()) {
            return self::BATCH_CREATE_FAIL_POPULATION;
        }

        if (!self::isValidSoldierType($soldierType)) {
            return self::BATCH_CREATE_FAIL_INVALID_TYPE;
        }

        $batches = $city->soldiers()->getBatches();
        if (count($batches) >= self::BATCH_MAX) {
            return self::BATCH_CREATE_FAIL_MAX; 
        }

        $batch = new Batch($city->getId(), $num, $soldierType);

        if($city->getGold() < $batch->getGoldCost()) {
            return self::BATCH_CREATE_FAIL_GOLD;    
        }

        if(empty($city->soldiers()->getTrainingBatch())) {
            $batch->setState(Batch::STATE_TRAINING);
            $batch->setTimeAtTraining($batch->getTimeAtCreation());
        }

        if(!Batch\Mapper::create($batch)) {
            return self::BATCH_CREATE_FAIL_DB;
        }

        if (Batch::STATE_TRAINING == $batch->getState()) {
            $city->soldiers()->setTrainingBatch($batch);
        } else {
            $batches->enqueue($batch);
        }

        $city->decreasePopulation($num);
        $city->decreaseGold($batch->getGoldCost());

        return self::BATCH_CREATE_SUCCESS;
    }

    public static function cancelSoldiers(City $city, $qIndex, $batchId)
    {
        assert($qIndex>=0,'batch postion must >= 0');
        assert($batchId>0,'batch id must >0');

        $batches = $city->soldiers()->getBatches();
        if (empty($batches) || !$batches->offsetExists($qIndex)) {
            return self::BATCH_CANCEL_FAIL_NOT_EXIST;
        }
        $batch = $batches->offsetGet($qIndex);
        if($batch->getId() != $batchId) {
            return self::BATCH_CANCEL_FAIL_NOT_EXIST;
        }

        if ($city->getId() != $batch->getCityId()) {
            return self::BATCH_CANCEL_FAIL_INVALID_CITY_ID;
        }

        $batch->setState(Batch::STATE_CANCELED);
        if(!Batch\Mapper::update($batch)){
            return self::BATCH_CANCEL_FAIL_DB;
        }
        $batches->offsetUnset($qIndex);

        $city->develop();
        $city->increasePopulation($batch->getNum());
        $city->increaseGold($batch->getGoldCost());

        return self::BATCH_CANCEL_SUCCESS;
    }

    public function __construct($cityId, $num, $soldierType, 
        $state = self::STATE_TO_TRAIN, $createTime = null)
    {
        $this->_cityId = $cityId;
        $this->_num = $num; 
        $this->_soldierType = $soldierType;
        $this->_state = $state;
        if (is_null($createTime)) {
            $this->_timeAtCreation = time();
        } else {
            $this->_timeAtCreation = $createTime;   
        }
    }

    public function getCityId()
    {
        return $this->_cityId;  
    }

    public function getNum()
    {
        return $this->_num; 
    }

    public function getSoldierType()
    {
        return $this->_soldierType; 
    }

    public function getState()
    {
        return $this->_state;   
    }

    public function setState($state = self::STATE_TRAINING)
    {
        $this->_state = $state; 
        $this->_needSave = true;
    }

    public function getTimeAtCreation()
    {
        return $this->_timeAtCreation;  
    }

    public function setTimeAtTraining($timeAtTraining)
    {
        assert($timeAtTraining>0,"time must greater than 0");
        $this->_timeAtTraining = $timeAtTraining;   
        $this->_needSave = true;
    }

    public function getTimeAtTraining()
    {
        return $this->_timeAtTraining;  
    }

    public function getTimeAtFinished()
    {
        return $this->_timeAtTraining + $this->getTimeCost();
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;   
    }

    public function needSave()
    {
        return $this->_needSave;
    }

    public function getTimeCost() 
    {
        return $this->getNum() * $this->_getTimeCostPerSoldier();
    }

    private function _getTimeCostPerSoldier()
    {
        if ($this->_soldierType == self::PIKEMEN) {
            return self::TRAINING_TIME_PIKEMEN; 
        } else if ($this->_soldierType == self::ARCHER) {
            return self::TRAINING_TIME_ARCHER;
        } else {
            return self::TRAINING_TIME_CAVALRY; 
        }
    }

    public function getGoldCost()
    {
        return $this->getNum() * $this->_getGoldCostPerSoldier();
    }

    private function _getGoldCostPerSoldier()
    {
        if ($this->_soldierType == self::PIKEMEN) {
            return self::TRAINING_GOLD_PIKEMEN; 
        } else if ($this->_soldierType == self::ARCHER) {
            return self::TRAINING_GOLD_ARCHER;
        } else {
            return self::TRAINING_GOLD_CAVALRY; 
        }
    }

    public static function isValidSoldierType($soldierType)
    {
        return $soldierType == self::PIKEMEN
            || $soldierType == self::ARCHER
            || $soldierType == self::CAVALRY;       
    }
}
