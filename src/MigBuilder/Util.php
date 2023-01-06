<?php

namespace MigBuilder;

use Illuminate\Support\Str;

class Util
{
    public static function firstUpper($name, $evenFirstOne = true){
        $name = (stripos($name, '_') === false) ? Str::snake($name) : $name;

        $processedName = Str::of($name)->camel();

        if ($evenFirstOne === false) {
            return $processedName;
        }
        
        return ucfirst($processedName);
    }
}
