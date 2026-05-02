<?php
class WP_CustomUtility_Class_Template extends CustomUtility_ClassTemplate
{
    public function __construct()
    {
        $this->setup_filters();
    }
    private function setup_filters()
    {
    }

    public function __object_func($func_name)
    {
        return array(&$this, $func_name);
    }
}
