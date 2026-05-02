<?php
class WP_CusomUtility__WH
{
    public function register_handle($var = NULL, $handle = NULL, $src = NULL, $deps = NULL, $ver = NULL, $rest = NULL)
    {
        if (!$var || !$handle || !$src)
            return;

        if ($var == 'css') {
            wp_register_style(
                $this->scripts_and_styles["styles"]["handles"][array_push($this->scripts_and_styles["styles"]["handles"], $handle) - 1],
                $src,
                $deps,
                $ver,
                ($rest ? $rest : 'screen')
            );
            return $handle;
        }
        if ($var == 'js') {
            wp_register_script(
                $this->scripts_and_styles["scripts"]["handles"][array_push($this->scripts_and_styles["scripts"]["handles"], $handle) - 1],
                $src,
                $deps,
                $ver,
                $rest
            );
            return $handle;
        }
        return false;
    }
    private function ______()
    {
        add_filter('WPCF_Modify_JQueryUI_Version', function () {
            return '1.12.1';
        });
    }
}
