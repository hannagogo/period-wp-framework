<?php
class CustomUtility_ClassTemplate
{
    protected $params = array();
    private $_param_var_name = 'params';
    private $_list_of_accepted_params = array();


    public function param($key = null, $value = null, &$param = null)
    {
        global $CUSTOM_UTILITY;
        if ($param === null) $param = &$this->{$this->_param_var_name()};
        if ($param === null || is_string($param)) $param = (array) $param;

        if (!empty($key) && $CUSTOM_UTILITY->is_hash($key)) {
            foreach ($key as $k => $v) {
                if ($this->_is_valid_param_key($k)) $param[$k] = $v; // !!! Accepts ALL KEYS at the first time !!!
            }
            return $this;
        }

        if (is_string($key)) {
            if (func_num_args() == 1) return isset($param[$key]) ? $param[$key] : null;
            else if (func_num_args() > 1) {
                list($k, $v) = func_get_args();
                if ($v === null) return isset($param[$k]) ? $param[$k] : NULL;
                else if ($this->_is_valid_param_key($k)) {
                    $param[$k] = $v;
                    return $v;
                }
            }
            return;
        }

        if (is_array($key)) {
            $r = array();
            for ($i = 0; $i < count($key); $i++) if (isset($param[$key[$i]])) $r[$key[$i]] = $param[$key[$i]];
            return $r;
        }

        return $this->param($this->accepted_param_keys());
    }

    public function set_param_var_name($var)
    {
        $this->_param_var_name = (string) $var;
        return $this;
    }

    public function set_accepted_param_keys($keys)
    {
        if (is_string($keys)) {
            $keys = func_get_args();
        }
        $this->_list_of_accepted_params = (array) $keys;
        return $this;
    }

    public function _param_var_name()
    {
        return $this->_param_var_name;
    }

    public function _is_valid_param_key($key)
    {
        return (count($this->_list_of_accepted_params) == 0) || in_array($key, $this->accepted_param_keys());
    }

    private function accepted_param_keys($key = null)
    {
        if ($key === null) return empty($this->_list_of_accepted_params) ? array_keys($this->{$this->_param_var_name()}) : (array) $this->_list_of_accepted_params;
    }

    public function add_accepted_param_keys($keys)
    {
        return $this->set_accepted_param_keys($this->accepted_param_keys() + (array) $keys);
    }

    public function delete_param_key($key)
    {
        if ($this->_is_valid_param_key($key)) {
            unset($this->_list_of_accepted_params[$key]);
            unset($this->{$this->_param_var_name}[$key]);
        }
        return $this;
    }
}// END OF CLASS: ClassTemplate;