<?php
namespace City\City\Soldiers;

use City\City;

class TrainingStrategy
{
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

    private $_city;

    public function __construct(City $city)
    {
        $this->_city = $city;           
    }   

    public function createSoldierBatch($num, $soldierType)
    {   
        if ($num <= 0) {
            return self::BATCH_CREATE_FAIL_INVALID_NUM; 
        }

        $this->_city->develop();
        if($num > $this->_city->getPopulation()) {
            return self::BATCH_CREATE_FAIL_POPULATION;  
        }

        if (!Batch::isValidSoldierType($soldierType)) {
            return self::BATCH_CREATE_FAIL_INVALID_TYPE;    
        }
    
        $batches = $this->_city->soldiers()->getBatches();
        if (count($batches) >= Batch::BATCH_MAX) {
            return self::BATCH_CREATE_FAIL_MAX; 
        }

        $batch = new Batch($this->_city->getId(), $num, $soldierType);

        if($this->_city->getGold() < $batch->getGoldCost()) {
            return self::BATCH_CREATE_FAIL_GOLD;    
        }
        
        if(empty($this->_city->soldiers()->getTrainingBatch())) {
            $batch->setState(Batch::STATE_TRAINING);
            $batch->setTimeAtTraining($batch->getTimeAtCreation());
        }
        if(!Batch\Mapper::create($batch)) {
            return self::BATCH_CREATE_FAIL_DB;
        }

        if (Batch::STATE_TRAINING == $batch->getState()) {
            $this->_city->soldiers()->setTrainingBatch($batch);
        } else {
            $batches->enqueue($batch);
        }
        
        $this->_city->decreasePopulation($num);
        $this->_city->decreaseGold($batch->getGoldCost());

        return self::BATCH_CREATE_SUCCESS;
    } 

    public function cancelSoldierBatch($qIndex, $batchId)
    {
        $batches = $this->_city->soldiers()->getBatches();
        if (empty($batches) || !$batches->offsetExists($qIndex)) {
            return self::BATCH_CANCEL_FAIL_NOT_EXIST;   
        }
        $batch = $batches->offsetGet($qIndex);
        if($batch->getId() != $batchId) {
            return self::BATCH_CANCEL_FAIL_NOT_EXIST;   
        }

        if ($this->_city->getId() != $batch->getCityId()) {
            return self::BATCH_CANCEL_FAIL_INVALID_CITY_ID; 
        }

        $this->_city->develop();
        $this->_city->increasePopulation($batch->getNum());
        $this->_city->increaseGold($batch->getGoldCost());
        $batch->setState(Batch::STATE_CANCELED);
        Batch\Mapper::update($batch);
        $batches->offsetUnset($qIndex);

        return self::BATCH_CANCEL_SUCCESS;
    }

    public function trainSolders()
    {
        $batches = Batch\Mapper::findByCityId($this->_city->getId());
        if (empty($batches)) {
            return;
        }

        $timeAtStart = 
            ($batches[0]->getState() == Batch::STATE_TRAINING)
            ? $batches[0]->getTimeAtTraining()
            : $batches[0]->getTimeAtCreation();

        $this->_trainSoldiers($batches, $timeAtStart);
    }

    private function _trainSoldiers($batches, $time)
    {
        $currentTime = time();
        $timeAtStart = $time;
        for ($i = 0; $i< count($batches); $i++) {
            $batch = $batches[$i];
            if ($currentTime - $timeAtStart < $batch->getTimeCost()) {
                if ($batch->getState() != Batch::STATE_TRAINING) {
                    $batch->setState(Batch::STATE_TRAINING);
                    $batch->setTimeAtTraining($timeAtStart);
                }
                $this->_city->soldiers()->setTrainingBatch($batch);
                for ($j=$i+1; $j<count($batches); $j++) {
                    $this->_city->soldiers()->getBatches()->enqueue($batches[$j]);
                }
                return;
            }
            $batch->setState(Batch::STATE_TRAINED);
            $batch->setTimeAtTraining($timeAtStart);
            $this->_city->develop($batch->getTimeAtFinished());
            $this->_city->soldiers()->accept($batch);

            $timeAtStart += $batch->getTimeCost();
        }
    }
}
