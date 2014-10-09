<?php
namespace dbFacile;

/**
 * TODO: Recommend and enforce use of these methods
 * Factory-ish static methods for instantiating driver-specific subclasses
 */
class factory
{
    public static function mysql()
    {
        return new \dbFacile\mysql();
    }
    public static function mysqli()
    {
        if (method_exists('mysqli_result', 'fetch_all')) {
            $o = new \dbFacile\mysqli();
        } else {
            $o = new \dbFacile\mysqli2();
        }
        return $o;
    }
    public static function postgresql()
    {
        return new \dbFacile\postgresql();
    }
    public static function sqlite2()
    {
        return new \dbFacile\sqlite2();
    }
    public static function sqlite3()
    {
        return new \dbFacile\sqlite3();
    }
}
