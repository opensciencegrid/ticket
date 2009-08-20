<?php

class Cache
{
    function __construct($path)
    {
        $this->path = $path;
        $this->ctime = @filectime($path);
    }
    function isFresh($fresh_for)
    {
        if($this->ctime === false) return false;
        return ($this->ctime > time() - $fresh_for) ? true : false;
    }
    function get()
    {
        return unserialize(file_get_contents($this->path));
    }
    function set($obj)
    {
        $fp = fopen($this->path, "w");
        fwrite($fp, serialize($obj)); 
        fclose($fp);
    }
}
