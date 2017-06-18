<?php
namespace City\City;

use \City\City;
use \City\City\Soldiers\Batch;
use \City\City\Soldiers\Batch\Mapper as BatchMapper;

class Builder
{
    private $_city;

    public function __construct($cityInfo)
    {
        $city = new City($cityInfo['player_id'],$cityInfo['name']
            , $cityInfo['coordinate_x'], $cityInfo['coordinate_y']
            , $cityInfo['type'], $cityInfo['tax_rate']
            , $cityInfo['food'], $cityInfo['gold'], $cityInfo['population']
            , strtotime($cityInfo['time_at_creation'])
            , strtotime($cityInfo['time_at_last_food'])
            , strtotime($cityInfo['time_at_last_tax']), $cityInfo['id']);
        $city->setId($cityInfo['id']);

        $this->_city = $city;
    }

    public function buildCity()
    {
        $this->_buildSoldiers();
        if($batches = BatchMapper::findByCityId($this->_city->getId())) {
            $timeAtStart = 
                ($batches[0]->getState() == Batch::STATE_TRAINING)
                ? $batches[0]->getTimeAtTraining()
                : $batches[0]->getTimeAtCreation();

            $this->_trainSoldiers($batches, $timeAtStart);
        }

        $this->_city->develop();
    }

    private function _buildSoldiers()
    {
        $soldiers = Soldiers\Mapper::findByCityId($this->_city->getId());
        $this->_city->setSoldiers(new Soldiers\Collection($soldiers));
    }

    private function _trainSoldiers(array $batches, $time)
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

    public function getCity()
    {
        return $this->_city;
    }
}
