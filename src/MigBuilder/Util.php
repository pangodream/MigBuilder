<?php
/**
 * Date: 20/12/2021
 * Time: 19:38
 */

namespace MigBuilder;


class Util
{
    public static function firstUpper($name, $evenFirstOne = true){
        $resName = "";
        $ucase = $evenFirstOne;
        $skip = false;
        for($i=0;$i<strlen($name);$i++){
            $l= strtolower(substr($name, $i, 1));
            if($l == "_"){
                $skip = true;
                $ucase = true;
            }else{
                if($ucase == true){
                    $l = strtoupper($l);
                    $ucase = false;
                }
                $resName .= $l;
                $skip = false;
            }

        }
        return $resName;
    }
}
