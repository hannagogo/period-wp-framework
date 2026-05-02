<?php
class HashAccessor extends ClassTemplate {
 public function __construct($param) {
  $this->param( (array) $param );
 }
 public function get_params() {
  return $this->param();
 }
}