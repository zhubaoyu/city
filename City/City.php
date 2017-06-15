<?php
namespace City;

use \City\City\Soldiers\Mapper as SoldiersDB;
use \City\City\Soldiers\Collection as SoldiersCollection;
use \City\City\Soldiers\TrainingStrategy;
use \City\City\Soldiers\Batch;

class CityException extends \Exception
{
    const PLAYER_ID_ERROR = 1;
    const COORDINATE_ERROR = 2;
    const TYPE_ERROR = 3;
    const TAX_RATE_ERROR = 4;
    const POPULATION_ERROR = 5;
    const FOOD_ERROR = 6;
    const GOLD_ERROR = 7;
    const CITY_ID_ERROR = 8;
    const TIME_ERROR = 9;
    const NEGATIVE_ERROR = 10;
}

class City
{
    const INIT_POPULATION = 100;
    const INIT_TAX_RATE = 0.2;
    const INIT_FOOD = 0;
    const INIT_GOLD = 0;
    const CAPITAL = 1;
    const REGULAR = 2;

    const COORDINATE_MIN = 0;
    const COORDINATE_MAX = 99;

    const POPULATION_MIN = 0;
    const POPULATION_MAX = 1000;

    const HOUR = 3600;
    const CAPITAL_FOOD_RATE = 10000;
    const REGULAR_FOOD_RATE = 1000;
    
    const POP_INCR_RATE = 0.05;
    const POP_DECR_RATE = 0.05;
    const POP_FLUC_MIN = 1;
    const POP_FLUC = 1000;


    
    private $_id;
    private $_playerId;
    private $_name;
    private $_coordinateX;
    private $_coordinateY;
    private $_type;
    private $_taxRate;
    private $_food;
    private $_gold;
    private $_population;
    private $_timeAtCreation;   
    private $_timeAtLastTax;
    private $_timeAtLastFood;       

    private $_soldiers;
    private $_soldierTrainingStrategy;
    private $_needSave = false;


    public function __construct($playerId, $name, $x, $y
    , $type = self::REGULAR, $taxRate = self::INIT_TAX_RATE
    , $food = self::INIT_FOOD, $gold = self::INIT_GOLD
    , $population = self::INIT_POPULATION, $timeAtCreation = null
    , $timeAtLastFood = null, $timeAtLastTax = null, $id = null) 
    {
        $this->_setPlayerId($playerId);
        $this->_setName($name);
        $this->_setCoordinate($x, $y);
        $this->_setType($type);
        $this->_setTaxRate($taxRate);
        $this->_setFood($food);
        $this->_setGold($gold);
        $this->_setPopulation($population);
        $this->_setTimeAtCreation($timeAtCreation); 
        $this->setTimeAtLastFood($timeAtLastFood);
        $this->setTimeAtLastTax($timeAtLastTax);
        $this->_setId($id); 
        if ($id>0) {
            $this->_getSoldierTrainingStrategy()->trainSolders();
            $this->develop();
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
        if (empty($id)) {
            return; 
        }
            
        if($id <= 0) {
            throw new CityException(
            "city id:$id, must greater than 0"
            , CityException::CITY_ID_ERROR);    
        }   

        $this->_id = $id;
    }

    public function getPlayerId()
    {
        return $this->_playerId;    
    }

    private function _setPlayerId($playerId)
    {
        if($playerId <= 0) {
            throw new CityException("player id:$playerId, must greater than zero"
            , CityException::PLAYER_ID_ERROR);  
        }   

        $this->_playerId = $playerId;
    }

    public function getName()
    {
        return $this->_name;    
    }

    public function setName($name)
    {
        $this->_setName($name);
        $this->_needSave = true;    
    }

    private function _setName($name)
    {
        if (empty($name)) {
            throw new CityException("city name:$name, must not be empty" , 
                CityException::PLAYER_ID_ERROR);
        }

        $this->_name = $name;
    }

    public function getCoordinateX()
    {
        return $this->_coordinateX; 
    }

    public function getCoordinateY()
    {
        return $this->_coordinateY; 
    }
    
    private function _setCoordinate($x,$y) 
    {
        if (!$this->isValidateCoordinate($x)) {
            throw new CityException(
            "city coordinate x:$x, must greater than or equal to " 
            . self::COORDINATE_MIN 
            . ", smallter than or equal to " 
            . self::COORDINATE_MAX 
            ,CityException::COORDINATE_ERROR);  
        }   
        if (!$this->isValidateCoordinate($y)) {
            throw new CityException(
            "city coordinate y:$y, must greater than or equal to "
            . self::COORDINATE_MIN 
            . ", smallter than or equal to " 
            . self::COORDINATE_MAX 
            ,CityException::COORDINATE_ERROR);  
        }   
        
        $this->_coordinateX = $x;
        $this->_coordinateY = $y;
    }

    public function isValidateCoordinate($coordinate) 
    {   
        return $coordinate >= self::COORDINATE_MIN 
        && $coordinate <= self::COORDINATE_MAX;
    }

    public function getType()
    {
        return $this->_type;    
    }

    public function setType($type)
    {
        $this->develop();
        $this->_setType($type);
        $this->_needSave = true;    
    }

    private function _setType($type)
    {
        if (!$this->isValidType($type)) {
            throw new CityException(
            "city must be a regular city or capital" 
            ,CityException::TYPE_ERROR);    
        }       

        $this->_type = $type;
    }

    public function isValidType($type)
    {
        return $type == self::REGULAR || $type == self::CAPITAL;    
    }

    public function getTaxRate()
    {
        return $this->_taxRate;
    }

    public function setTaxRate($taxRate)
    {
        $this->develop();   
        $this->_setTaxRate($taxRate);
        $this->_needSave = true;
    }

    private function _setTaxRate($taxRate)
    {
        if ($taxRate < 0) {
            throw new CityException(
            "tax rate must greater than or equal to 0"
            , CityException::TAX_RATE_ERROR);   
        }       

        $this->_taxRate = $taxRate;
    }

    public function increaseFood($num)
    {
        
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->setFood($this->getFood() + $num);
    }

    public function decreaseFood($num)
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        if ($num > $this->getFood()) {
            $this->setFood(0);  
        } else {
            $this->setFood($this->getFood() - $num);    
        }
    }

    public function getFood()
    {
        return $this->_food;    
    }       
    
    public function setFood($food)
    {
        $this->_setFood($food);
        $this->_needSave = true;    
    }

    private function _setFood($food)
    {
        if ($food < 0) {
            throw new CityException(
            "food must must greater than or equal to 0"
            , CityException::FOOD_ERROR);   
        }       

        $this->_food = $food;
    }

    public function increaseGold($num)
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->setGold($this->getGold() + $num);    
    }

    public function decreaseGold($num)
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        if ($num > $this->getGold()) {
            $this->setGold(0);  
        } else {
            $this->setGold($this->getGold() - $num);    
        }   
    }

    public function getGold()
    {
        return $this->_gold;    
    }

    public function setGold($gold)
    {
        $this->_setGold($gold);
        $this->_needSave = true;    
    }

    private function _setGold($gold)
    {
        if ($gold < 0) {
            throw new CityException(
            "gold must must greater than or equal to 0"
            , CityException::GOLD_ERROR);   
        }       

        $this->_gold = $gold;   
    }

    public function increasePopulation($num) 
    {
//      var_dump(debug_backtrace()[1]['function']);
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->setPopulation($this->getPopulation() + $num);
    }

    private function _increasePopulation($num)
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->_setPopulation($this->getPopulation() + $num);
    }

    public function decreasePopulation($num) 
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->setPopulation($this->getPopulation()-$num);
    }

    private function _decreasePopulation($num)
    {
        if ($num < 0) {
            throw new CityException(
                "num must not be smaller than 0",
                CityException::NEGATIVE_ERROR); 
        }

        $this->_setPopulation($this->getPopulation()-$num);
    }
    
    public function getPopulation()
    {
        return $this->_population;  
    }
    
    public function setPopulation($population)
    {
        if (!$this->isValidPopulation($population)) {
            throw new CityException(
            "population must must greater than or equal to " 
            . self::POPULATION_MIN 
            . ", smaller than or equal to " 
            .  self::POPULATION_MAX , CityException::GOLD_ERROR);
        }
        $this->develop();
        $this->_setPopulation($population);
        $this->_needSave = true;    
    }

    private function _setPopulation($population)
    {
        if ($population > self::POPULATION_MAX) {
            $population = self::POPULATION_MAX; 
        }
        if ($population < self::POPULATION_MIN) {
            $population = self::POPULATION_MIN; 
        }

        $this->_population = $population;
    }

    public function isValidPopulation($population)
    {
        return $population >= self::POPULATION_MIN && $population <= self::POPULATION_MAX;  
    }

    public function getTimeAtCreation()
    {
        return $this->_timeAtCreation;  
    }

    private function _setTimeAtCreation($timeAtCreation = null)
    {
        if (empty($timeAtCreation)) {
            $this->_timeAtCreation = time();
            return;
        }
        if ($timeAtCreation <= 0) {
            throw new CityException(
            "format of time at creation:$timeAtCreation invalid"
            , CityException::TIME_ERROR);   
        }

        $this->_timeAtCreation = $timeAtCreation;   
    }

    public function getTimeAtLastFood()
    {
        return $this->_timeAtLastFood;  
    }

    public function setTimeAtLastFood($timeAtLastFood = null)
    {
        if (empty($timeAtLastFood)){
            $this->_timeAtLastFood = $this->_timeAtCreation;    
            return;
        }

        if ($timeAtLastFood <= 0) {
            throw new CityException(
            "format of time at last food:$timeAtLastFood invalid"
            , CityException::TIME_ERROR);   
        }

        $this->_timeAtLastFood = $timeAtLastFood;
    }

    public function getTimeAtLastTax()
    {
        return $this->_timeAtLastTax;   
    }

    public function setTimeAtLastTax($timeAtLastTax = null)
    {
        if (empty($timeAtLastTax)){
            $this->_timeAtLastTax = $this->_timeAtCreation; 
            return;
        }

        if ($timeAtLastTax <= 0) {
            throw new CityException(
            "format of time at last tax:$timeAtLastTax invalid"
            , CityException::TIME_ERROR);   
        }

        $this->_timeAtLastTax = $timeAtLastTax;
    }
    
    public function getFoodProductionRate()
    {
        return self::CAPITAL == $this->getType() 
        ? (float)self::CAPITAL_FOOD_RATE / self::HOUR
        : (float)self::REGULAR_FOOD_RATE / self::HOUR;  
    }

    public function getProducedFood($time) 
    {
        if ($time < $this->getTimeAtLastFood()) {
            return 0;
        }
        return round($this->getFoodProductionRate() 
            * ($time - $this->getTimeAtLastFood()));        
    }

    public function produceFood($time)
    {
        if ($food = $this->getProducedFood($time)) {
            $this->increaseFood($food);
            $this->setTimeAtLastFood($time);
            return $food;
        }

        return 0;
    }

    public function consumeFood($time)
    {
        $foodConsumed = $this->soldiers()->consumeFood($time);
        $this->decreaseFood($foodConsumed);
        return $foodConsumed;
    }

    public function hasMoreTax($time)
    {
        return $time - $this->getTimeAtLastTax() >= self::HOUR 
        ? true : false; 
    }

    public function getTaxCount($time)
    {
        return floor(($time-$this->getTimeAtLastTax())/self::HOUR);
    }

    private function _collectTax($time)
    {
        if ($time-$this->getTimeAtLastTax() < self::HOUR) {
            return;
        }
        $tax = round($this->getTaxRate()*$this->getPopulation());
        $this->increaseGold($tax);
        $this->setTimeAtLastTax($this->getTimeAtLastTax() + self::HOUR);
    }

    private function _fluctuatePopulation()
    {
        if($this->isPopulationIncreased()) {
            $this->_increasePopulation($this->_getFlucPop(self::POP_INCR_RATE));
        } elseif ($this->isPopulationDecreased()) {
            $this->_decreasePopulation($this->_getFlucPop(self::POP_DECR_RATE));
        }
    }

    public function  isPopulationIncreased() 
    {
        return $this->getPopulation()<$this->getTaxRate()*self::POP_FLUC
            ? true : false;
    }

    public function isPopulationDecreased() 
    {
        return $this->getPopulation()>$this->getTaxRate()*self::POP_FLUC
            ? true : false; 
    }

    private function _getFlucPop($rate)
    {
        $fluctuate = round($this->getPopulation() * $rate); 
        return $fluctuate >= self::POP_FLUC_MIN 
            ? $fluctuate : self::POP_FLUC_MIN;
    }

    private function _fluctuateSoldiers()
    {
        if ($this->getFood() > 0) {
            return; 
        }

        $this->soldiers()->fluctuate();
    }

    public function collectTax($time)
    {
        for ($i = 1; $i <= $this->getTaxCount($time); $i++) {
            $this->_collectTax($time);
            $this->_fluctuatePopulation();
            $this->_fluctuateSoldiers();
        }
    }

    public function develop($time = null)
    {
//      var_dump(debug_backtrace()[1]['function']);
        if (empty($time)) {
            $time = time();
        }
        $this->produceFood($time);
        $this->consumeFood($time);

        if ($this->hasMoreTax($time)) {
            $this->collectTax($time);
        }
    }

    public function soldiers()
    {
        if (empty($this->_soldiers)) {
            $soldiers = SoldiersDB::findByCityId($this->getId());
            $this->_soldiers = new SoldiersCollection($soldiers);
        }

        return $this->_soldiers;
    }

    private function _getSoldierTrainingStrategy()
    {
        if (!$this->_soldierTrainingStrategy) {
            $this->_soldierTrainingStrategy = new TrainingStrategy($this);
        }

        return $this->_soldierTrainingStrategy;
    }

    public function createPikemen($num) 
    {
        return $this->_getSoldierTrainingStrategy()
            ->createSoldiers($num, Batch::PIKEMEN); 
    }

    public function createArcher($num) 
    {
        return $this->_getSoldierTrainingStrategy()
            ->createSoldiers($num, Batch::ARCHER);  
    } 

    public function createCavalry($num) 
    {
        return $this->_getSoldierTrainingStrategy()
            ->createSoldiers($num, Batch::CAVALRY); 
    }

    public function cancelSoldiers($qIndex, $batchId)
    {
        return $this->_getSoldierTrainingStrategy()
            ->cancelSoldiers($qIndex, $batchId);        
    }

    public function setNeedSave($needSave = true)
    {
        $this->_needSave = $needSave ? true : false;    
    }

    public function needSave()
    {
        return $this->_needSave;    
    }
}
