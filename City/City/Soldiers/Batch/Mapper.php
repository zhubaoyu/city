<?php
namespace City\City\Soldier\Batch;

use City\City\Soldier\Batch; 
use City\Db\CatchedAdapter;

class Mapper
{
    public static function create(Batch $batch)
    {
        $sql = 'INSERT INTO soldier_batch(city_id,soldier_type,'
            . 'num,state,time_at_creation,time_at_training)'
            . ' VALUES(:city_id,:soldier_type,:num,:state,'
            . ':time_at_creation,:time_at_training)';
        $data = array(
            ':city_id' => $batch->getCityId(),
            ':soldier_type' => $batch->getSoldierType(),
            ':num' => $batch->getNum(),     
            ':state' => $batch->getState(),
            ':time_at_creation' => $batch->getTimeAtCreationString(),
            ':time_at_training' =>'',
        );
        if (Batch::STATE_TRAINING == $batch->getState()) {
            $data[':time_at_training'] = $batch->getTimeAtTrainingString();
        }
        $ret = CatchedAdapter::create($sql, $data);
        if (false === $ret) {
            return false;
        }
        $batch->setId($ret);
        return true;
    }

    public static function update(Batch $batch)
    {
        if (!$batch->needSave()) {
            return 0;
        }
        $sql = 'UPDATE soldier_batch SET state=:state,'
            .'time_at_training=:time_at_training  WHERE id=:id';    
        $info = array(
            ':state' => $batch->getState(), 
            ':time_at_training' => $batch->getTimeAtTrainingString(),
            ':id' => $batch->getId(),
        );
        return CatchedAdapter::update($sql, $info);
    }

    public static function findByCityId($cityId)
    {
        $sql = 'SELECT * FROM soldier_batch'
            . ' WHERE city_id=:city_id'
            . ' AND (state=:state1 OR state=:state2)';  
        $info = array(
            ':city_id' => $cityId, 
            ':state1' => Batch::STATE_TO_TRAIN,
            ':state2' => Batch::STATE_TRAINING,
        );
        $data = CatchedAdapter::select($sql, $info);
        if (false === $data) {
            return false;
        }
        $batches = array();
        foreach ($data as $d) {
            $batch = new Batch($d['city_id'],$d['num'],$d['soldier_type']
                , $d['state'],strtotime($d['time_at_creation']));
            $batch->setId($d['id']);
            if ($d['state'] == Batch::STATE_TRAINING) {
                $batch->setTimeAtTraining(strtotime($d['time_at_training']));
            }
            $batches[] = $batch;
        }

        return $batches;
    }
}
