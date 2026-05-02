<?php
class HashAccessor extends CustomUtility_ClassTemplate
{
    public function __construct($param)
    {
        $this->param((array) $param);
    }
    public function get_params()
    {
        return $this->param();
    }
}
