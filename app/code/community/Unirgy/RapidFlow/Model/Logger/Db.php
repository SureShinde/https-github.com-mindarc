<?php

class Unirgy_RapidFlow_Model_Logger_Db extends Unirgy_RapidFlow_Model_Logger_Abstract
{
    public function start($mode)
    {
        return $this;
    }

    public function pause()
    {
        return $this;
    }

    public function stop()
    {
        return $this;
    }

    public function success($message)
    {
        return $this;
    }

    public function error($message)
    {
        return $this;
    }
}
