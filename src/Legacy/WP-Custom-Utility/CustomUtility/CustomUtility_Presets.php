<?php
class CustomUtility_Presets
{
    public $CR = "\x0d";
    public $LF = "\x0a";
    public $UTF8 = 'UTF-8';
    public $CRLF;


    public function __construct()
    {
        $this->CRLF = $this->CR . $this->LF;
    }
}
