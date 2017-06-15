<?php
namespace City\City;
use \City\City\Soldiers\Batch;

class Soldiers
{
    const SOLDIER_DECREASE_RATE = 0.1;
    const SOLDIER_DECREASE_MIN = 1;
    const SOLDIER_MIN = 0;

    const FOOD_CONSUMPTION_RATE_PIKEMEN = 10/3600;
    const FOOD_CONSUMPTION_RATE_ARCHER = 13/3600;
    const FOOD_CONSUMPTION_RATE_CAVALRY = 30/3600;

    private $_id;
    private $_cityId;
    private $_soldierType;
    private $_num;
    private $_timeAtLastEating;
    private $_needSave = false;

    public function __construct($cityId, $soldierType, $num, 
        $timeAtLastEating = null)
    {
        $this->_cityId = $cityId;
        $this->_soldierType = $soldierType;
        $this->_num = $num;     
        if (!is_null($timeAtLastEating)) {
            $this->_timeAtLastEating = $timeAtLastEating;   
        } else {
            $this->_timeAtLastEating = time();  
        }
    }   

    public function getCityId()
    {
        return $this->_cityId;  
    }

    public function getSoldierType()
    {
        return $this->_soldierType; 
    }

    public function getNum()
    {
        return $this->_num; 
    }

    public function setNum($num)
    {
        if ($num < self::SOLDIER_MIN) {
            $num = self::SOLDIER_MIN;
        }
        $this->_num = $num;
        $this->_needSave = true;
    }

    public function addNum($num)
    {
        if ($num <=0) {
            return;
        }
        $this->setNum($this->getNum()+$num);
    }

    public function lessen($num)
    {
        if ($num <= 0)  {
            return;
        }
        $this->setNum($this->getNum()-$num);
    }

    public function getId()
    {
        return $this->_id;  
    }

    public function setId($id)
    {
        $this->_id = $id;   
    }

    public function getTimeAtLastEating()
    {
        return $this->_timeAtLastEating;    
    }

    public function setTimeAtLastEating($timeAtLastEating)
    {
        $this->_timeAtLastEating = $timeAtLastEating;   
        $this->_needSave = true;
    }

    public function needSave()
    {   if (empty($this->_id)) {
            return true;
        }
        return $this->_needSave;
    }

    public function getConsumedFood($time)
    {
        if ($time <$this->getTimeAtLastEating())  {
            return 0;
        }

        return $this->getFoodConsumptionRate()
            * ($time-$this->getTimeAtLastEating());
    }

    public function consumeFood($time)
    {
        $foodConsumed = $this->getConsumedFood($time);
        if ($foodConsumed == 0) {
            return 0;
        }
        $this->setTimeAtLastEating($time);
        return $foodConsumed;
    }

    public function getFoodConsumptionRate()
    {
        if ($this->_soldierType == Batch::PIKEMEN) {
            return $this->_num * self::FOOD_CONSUMPTION_RATE_PIKEMEN;
        } elseif ($this->_soldierType == Batch::ARCHER) {
            return $this->_num * self::FOOD_CONSUMPTION_RATE_ARCHER;
        } else {
            return $this->_num * self::FOOD_CONSUMPTION_RATE_CAVALRY;
        }   
    }

    public function getLessened()
    {
        $decreased = round($this->getNum() * self::SOLDIER_DECREASE_RATE);
        if ($decreased < self::SOLDIER_DECREASE_MIN) {
            $decreased = self::SOLDIER_DECREASE_MIN;
        }

        return $decreased;
    }

    public function fluctuate()
    {
        $this->lessen($this->getLessened());
    }
}
