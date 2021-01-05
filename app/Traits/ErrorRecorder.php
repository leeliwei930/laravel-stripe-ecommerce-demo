<?php
namespace App\Traits;

trait ErrorRecorder {

    protected $error = null;

    public function recordError(\Exception $error)
    {
        $this->error = $error;
    }

    public function anyError()
    {
        return !is_null($this->error);
    }

    public function getError() : \Exception
    {
        return $this->error;
    }
}
