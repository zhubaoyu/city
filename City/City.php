<?php
namespace City;

use \City\City\Soldiers\Mapper as SoldiersDB;
use \City\City\Soldiers\Collection as SoldiersCollection;
use \City\City\Soldiers\TrainingStrategy;
use \City\City\Soldiers\Batch;
use \City\City\Soldiers\Batch\Mapper as BatchMapper;

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


    public static function makeCity($cityInfo)
    {
        $city = new City($cityInfo['player_id'],$cityInfo['name']
            , $cityInfo['coordinate_x'], $cityInfo['coordinate_y']
            , $cityInfo['type'], $cityInfo['tax_rate']
            , $cityInfo['food'], $cityInfo['gold'], $cityInfo['population']
            , strtotime($cityInfo['time_at_creation'])
            , strtotime($cityInfo['time_at_last_food'])
            , strtotime($cityInfo['time_at_last_tax']), $cityInfo['id']);
        $city->setId($cityInfo['id']);
        if($soldierBatches = BatchMapper::findByCityId($city->getId())) {
            $city->trainSolders($soldierBatches);
        }
        $city->develop();

        return $city;
    }

    public function __construct($playerId, $name, $x, $y
    , $type = self::REGULAR, $taxRate = self::INIT_TAX_RATE
    , $food = self::INIT_FOOD, $gold = self::INIT_GOLD
    , $population = self::INIT_POPULATION, $timeAtCreation = null
    , $timeAtLastFood = null, $timeAtLastTax = null)
    {
        $this->_setPlayerId($playerId);
        $this->_setName($name);
        $this->_setCoordinate($x, $y);
        $this->_setType($type);
        $this->_setTaxRate($taxRate);
        $this->setFood($food);
        $this->setGold($gold);
        $this->_setPopulation($population);
        $this->_setTimeAtCreation($timeAtCreation); 
        $this->setTimeAtLastFood($timeAtLastFood);
        $this->setTimeAtLastTax($timeAtLastTax);
    }

    public function getId()
    {
        return $this->_id;  
    }

    public function setId($id)
    {
        assert($id>0, "city id:{$id}, must greater than 0");
        $this->_id = $id;
    }

    public function getPlayerId()
    {
        return $this->_playerId;
    }

    private function _setPlayerId($playerId)
    {
        assert($playerId>0, "player id:$playerId, must greater than 0");
        $this->_playerId = $playerId;
    }

    public function getName()
    {
        return $this->_name;    
    }

    private function _setName($name)
    {
        assert(!empty($name),"city name:{$name}, must not be empty");
        $this->_name = $name;
        $this->_needSave = true;
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
        assert($this->isValidateCoordinate($x) && $this->isValidateCoordinate($y),
            "city coordinate{x:$y,y:$y}"
            . ", must greater than or equal to " . self::COORDINATE_MIN
            . ", smallter than or equal to " . self::COORDINATE_MAX);
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
    }

    private function _setType($type)
    {
        assert($this->isValidType($type),"city type:{$type} must be a regular city or capital");
        $this->_type = $type;
        $this->_needSave = true;
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
    }

    private function _setTaxRate($taxRate) 
    {
        assert($taxRate>=0,"tax rate must greater than or equal to 0");
        $this->_taxRate = $taxRate;
        $this->_needSave = true;
    }

    public function increaseFood($num)
    {
        assert($num >=0,"num:{$num} must not be smaller than 0");
        $this->setFood($this->getFood() + $num);
    }

    public function decreaseFood($num)
    {
        assert($num >=0,"num:{$num} must not be smaller than 0");
        $food = $this->getFood() - $num;
        $food = $food >0 ? $food : 0;
        $this->setFood($food);
    }

    public function getFood()
    {
        return $this->_food;
    }

    public function setFood($food)
    {
        assert($food>=0,"food:{$food} must must greater than or equal to 0");
        $this->_food = $food;
        $this->_needSave = true;    
    }

    public function increaseGold($num)
    {
        assert($num>=0,"gold:{$num} must must greater than or equal to 0");
        $this->setGold($this->getGold() + $num);    
    }

    public function decreaseGold($num)
    {
        assert($num>=0,"gold:{$num} must must greater than or equal to 0");
        $gold = $this->getGold() - $num;
        $gold = $gold>0 ? $gold : 0;
        $this->setGold($gold);
    }

    public function getGold()
    {
        return $this->_gold;
    }

    public function setGold($gold)
    {
        assert($gold>=0,"gold:{$gold} must must greater than or equal to 0");
        $this->_gold = $gold;
        $this->_needSave = true;    
    }

    public function increasePopulation($num) 
    {
        assert($num>=0,"population:{$num} must must greater than or equal to 0");
        $this->setPopulation($this->_getIncreasedPopulation($num));
    }

    private function _getIncreasedPopulation($num)
    {
        $population = $this->getPopulation() + $num;
        return $population < self::POPULATION_MAX
            ? $population
            : self::POPULATION_MAX;
    }

    private function _increasePopulation($num)
    {
        assert($num>=0,"population:{$num} must must greater than or equal to 0");
        $this->_setPopulation($this->_getIncreasedPopulation($num));
    }

    public function decreasePopulation($num) 
    {
        assert($num>=0,"population:{$num} must must greater than or equal to 0");
        $this->setPopulation($this->_getDecreasedPopulation($num));
    }

    private function _getDecreasedPopulation($num)
    {
        $population = $this->getPopulation() - $num;
        return $population > self::POPULATION_MIN
            ? $population
            : self::POPULATION_MIN;
    }

    private function _decreasePopulation($num)
    {
        assert($num>=0,"population:{$num} must must greater than or equal to 0");
        $this->_setPopulation($this->_getDecreasedPopulation($num));
    }

    public function getPopulation()
    {
        return $this->_population;  
    }

    public function setPopulation($population)
    {
        $this->develop();
        $this->_setPopulation($population);
    }

    private function _setPopulation($population)
    {
        assert($this->isValidPopulation($population),
            "population:{$population}"
            . ", must greater than or equal to " . self::POPULATION_MIN
            . ", smallter than or equal to " . self::POPULATION_MAX);

        $this->_population = $population;
        $this->_needSave = true;
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
        assert($timeAtCreation >0,"format of time at creation:{$timeAtCreation} invalid");
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
        assert($timeAtLastFood >0,"format of time at creation:{$timeAtLastFood} invalid");
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
        assert($timeAtLastTax >0,"format of time at creation:{$timeAtLastTax} invalid");
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

    public function trainSolders(array $soldierBatches)
    {
        $this->_getSoldierTrainingStrategy()->trainSolders($soldierBatches);
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
