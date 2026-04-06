<?php

function get_url($dest, $isEcho = false)
{
    global $BASE_PATH;
    // assumes absolute path by default
    // check if not absolute
    if (!str_starts_with($dest, "/")) {
        //handle relative path
        $dest = "$BASE_PATH/$dest";
    }
    if($isEcho){
        echo $dest;
        return;
    }
    return $dest;

}