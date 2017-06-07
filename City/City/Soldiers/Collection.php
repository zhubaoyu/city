<?php
namespace City\City\Soldiers;

use City\City\Soldiers;

class Collection
{
    private $_soldiers = array();
    private $_trainingBatch;
    private $_trainedBatches = array();
    private $_batches;

    public function __construct($soldiers)
    {
        $this->_soldiers = $soldiers;
        $this->_batches = new \SplQueue();
    }

    public function getSoldiers()
    {
        return $this->_soldiers;
    }

    public function getBatches()
    {
        return $this->_batches;
    }

    public function getTrainingBatch()
    {
        return $this->_trainingBatch;
    }

    public function setTrainingBatch(Batch $batch)
    {
        $this->_trainingBatch = $batch;
    }

    public function find($soldierType) 
    {
        for ($i=0; $i<count($this->_soldiers); $i++) {
            if ($this->_soldiers[$i]->getSoldierType() == $soldierType) {
                return $i;  
            }
        }

        return -1;
    }

    public function accept(Batch $batch) 
    {
        $this->_trainedBatches[] = $batch;

        $index = $this->find($batch->getSoldierType());
        if ($index>=0) {
            $this->_soldiers[$index]->addNum($batch->getNum());
            $this->_soldiers[$index]->setTimeAtLastEating($batch->getTimeAtFinished());
            return;
        }
        $this->_accept($batch);
    }

    private function _accept(Batch $batch) 
    {
        $soldiers = new Soldiers($batch->getCityId(),
            $batch->getSoldierType(),
            $batch->getNum(),
            $batch->getTimeAtFinished());
        $this->_soldiers[] = $soldiers;
    }

    public function consumeFood($time) 
    {
        $foodConsumed = 0;
        foreach ($this->_soldiers as $soldiers) {
            $foodConsumed += $soldiers->consumeFood($time);
        }
        return $foodConsumed;
    }

    public function fluctuate()
    {
        foreach ($this->_soldiers as $soldiers) {
            $soldiers->fluctuate();
        }
    }

    public function save()
    {
        foreach($this->_soldiers as $soldiers) {
            if ($soldiers->getId()) {
                Soldiers\Mapper::update($soldiers);
            } else {
                Soldiers\Mapper::create($soldiers);
            }
        }

        Batch\Mapper::update($this->_trainingBatch);

        foreach ($this->_trainedBatches as $batch) {
            Batch\Mapper::update($batch);
        }
    }
}
