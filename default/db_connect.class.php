<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 30.10.17
 * Time: 12:22
 */

require 'Medoo.php';
use Medoo\Medoo;

class db_connect
{
    public $sql_interface = null;
    private $debugclass;

    public function __construct($connectionInfo, $debug = false)
    {
        $this->debugclass = $debug;


        if (isset($connectionInfo)) {


            if (!isset($_SESSION["db_connect"]) && (is_null($_SESSION["db_connect"]["server"]))) {
                $_SESSION["db_connect"] = new Medoo($connectionInfo);
            }

            $this->sql_interface = $_SESSION["db_connect"];

            //$this->sql_interface->query("SET NAMES utf8");
            //$this->sql_interface->query("SET CHARACTER SET 'utf8'");

            $this->sql_interface->query ("set_client='utf8'");
            $this->sql_interface->query ("set character_set_results='utf8'");
            $this->sql_interface->query ("set collation_connection='utf8_general_ci'");
            $this->sql_interface->query ("SET NAMES utf8");


        };
    }

    private function gettruetype($base, $type) {
        $result = $type;
        if ($base == 'MSSQL') {
            if ($type == 'longtext') {
                $result = 'text';
            } elseif (strpos($type, "int") !== false) {
                $result = 'int';
            }
        }
        elseif ($base == 'sqlite') {
            if (strpos($type, "int") !== false) {
                $result = 'INTEGER';
            }
        }
        return $result;
    }

    private function getlines($base, $table, &$inc) {
        $inc = '';
        $lines = '';
        foreach ($table as $key => $value) {
            if ($lines != '') {
                $lines .= ",\r\n";
            }
            $lines .= " ".$key;
            if (is_array($value)) {
                foreach ($value as $key1 => $value1) {
                    if ($key1 == 'type') {
                        $type = $this->gettruetype($base, $value1);
                        $lines .= " ".$type;
                    } elseif ($key1 == 'null') {
                        $lines .= " ".$value1;
                    } elseif ($key1 == 'inc') {
                        if ($base == 'MSSQL') {
                            $lines .= " IDENTITY";
                        } elseif ($base == "sqlite") {
                            $lines .= " PRIMARY KEY AUTOINCREMENT";
                        }
                        $inc = $key;
                    }
                }
            }
        }
        return $lines;
    }

    private function getarlines($base, $table, &$inc) {
        $inc = '';

        $linear = [];
        $lines = '';
        foreach ($table as $key => $value) {
            if ($lines != '') {
                $lines = "";
            }
            $lines .= $key;
            if (is_array($value)) {
                foreach ($value as $key1 => $value1) {
                    if ($key1 == 'type') {
                        $type = $this->gettruetype($base, $value1);
                        $lines .= " ".$type;
                    } elseif ($key1 == 'null') {
                        $lines .= " ".$value1;
                    } elseif ($key1 == 'inc') {
                        if ($base == 'MSSQL') {
                            $lines .= " IDENTITY";
                        } elseif ($base == "sqlite") {
                            $lines .= " PRIMARY KEY AUTOINCREMENT";
                        }
                        $inc = $key;
                    }
                }
            }
            $linear[$key] = $lines;
            //$lines = '';
        }
        return $linear;
    }

    public function create ($database_type, $info) {

        $prepare = $this;
        $base = $database_type;

        foreach ($info as $classname => $table) {

            $inc = '';
            $lines = $this->getlines($base, $table, $inc);

            if ($base == 'MSSQL') {
                $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$classname';";
                $res = $prepare->query($sql)->fetchAll();
                if (count($res) == 0) {
                    $sql = 'CREATE TABLE "'.$classname.'" ('
                        .$lines
                        . ');'."\r\n";
                    if ($inc != "") {
                        $sql .= 'SET IDENTITY_INSERT "'.$classname.'" OFF;' . "\r\n"
                            . 'ALTER TABLE "'.$classname.'"'
                            . '  ADD PRIMARY KEY ("'.$inc.'");' . "\r\n";
                    }
                    $res = $prepare->query($sql);
                }
            }
            elseif ($base == 'mysql') {

                $sql = "SHOW TABLES LIKE '".$classname."'";
                $res = $prepare->query($sql)->fetchAll();

                if (count($res) == 0) {
                    $sql = "CREATE TABLE IF NOT EXISTS `$classname` ("
                        .$lines
                        .") ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"."\r\n";

                    $res = $prepare->query($sql);

//                    print_r($prepare->last_query());
//                    var_dump($prepare->error());

                    if ($inc != "") {
                        $sql = "    ALTER TABLE `$classname`"
                            ."      ADD PRIMARY KEY (`$inc`);\r\n";
                        $res = $prepare->query($sql);
                        //var_dump($prepare->error());

                        $sql = "    ALTER TABLE `$classname`"
                            ."      MODIFY `$inc` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;";
                        $res = $prepare->query($sql);
                        //var_dump($prepare->error());
                    }
                } else {
                    //echo "Table $classname is yes";
                    //SHOW COLUMNS FROM `mpcomponents`
                    $sql = "SHOW COLUMNS FROM `".$classname."`";
                    $res = $prepare->query($sql)->fetchAll();

                    //if ($classname == "mpcomponents") {
                        //print_r($res);
                        //print_r($table);

                        $nowtable = [];
                        foreach ($res as $value) {
                            $Field = $value["Field"];
                            $infofield = [
                                    "type" => $value["Type"],
                                    "null" => ($value["Null"] == "NO") ? "NOT NULL": ""
                                ];
                            if ($value["Extra"] == "auto_increment") {
                                $infofield["inc"] = 1;
                            }
                            $nowtable[$Field] = $infofield;
                        }

                        $addfields = array_diff_key($table, $nowtable);
                        $inc2 = '';
                        $linesarray = $this->getarlines($base, $addfields, $inc2);
                        foreach ($linesarray as $key => $line) {
                            if ($key != "id") {
                                $sql2 = "ALTER TABLE `$classname`
                                  ADD $line;";
                                $res = $prepare->query($sql2);
                            }
                        }

                        $delfields = array_diff_key($nowtable, $table);
                        $inc2 = '';
                        foreach ($delfields as $key => $line) {
                            if ($key != "id") {
                                $sql2 = "ALTER TABLE `$classname`
                                  DROP COLUMN $key;";
                                $res = $prepare->query($sql2);
                            }
                        }

                        $fields = array_intersect($table, $nowtable);
                        $linesarray = $this->getarlines($base, $fields, $inc2);
                        foreach ($fields as $key => $line) {
                            if ($key != "id") {
                                if ($line["type"] != $nowtable[$key]["type"]) {
                                    $sql2 = "ALTER TABLE `$classname`
                                            MODIFY ".$linesarray[$key].";";
                                    $res = $prepare->query($sql2);
                                }
                            }
                        }
                    //}
                }
            }
            elseif ($base == 'sqlite') {
                $res = $prepare->count($classname);
                $error = $prepare->error();
                if ($error[1] != 0) {
                    $sql = "CREATE TABLE IF NOT EXISTS '$classname' ("
                        .$lines
                        .")"."\r\n";

                    $res = $prepare->query($sql);
                }
            };
        }

        $result = $prepare->error();
        return $result;
    }

    /*SQL*/

    public function select($table, $join, $columns = null, $where = null)
    {
        return $this->sql_interface->select($table, $join, $columns, $where);
    }

    public function insert($table, $datas)
    {
        $this->sql_interface->insert($table, $datas);
        $account_id = $this->sql_interface->id();
        return $account_id;
    }

    public function update($table, $data, $where = null)
    {
        return $this->sql_interface->update($table, $data, $where);
    }

    public function delete($table, $where)
    {
        return $this->sql_interface->delete($table, $where);
    }

    public function replace($table, $columns, $search = null, $replace = null, $where = null)
    {
        return $this->sql_interface->replace($table, $columns, $search, $replace, $where);
    }

    public function get($table, $join = null, $columns = null, $where = null)
    {
        $result = $this->sql_interface->get($table, $join, $columns, $where);
        if (is_null($result)) $result = false;
        return $result;
    }

    public function has($table, $join, $where = null)
    {
        return $this->sql_interface->has($table, $join, $where);
    }

    public function count($table, $join = null, $column = null, $where = null)
    {
        return $this->sql_interface->count($table, $join, $column, $where);
    }

    public function max($table, $join, $column = null, $where = null)
    {
        return $this->sql_interface->max($table, $join, $column, $where);
    }

    public function min($table, $join, $column = null, $where = null)
    {
        return $this->sql_interface->min($table, $join, $column, $where);
    }

    public function avg($table, $join, $column = null, $where = null)
    {
        return $this->sql_interface->avg($table, $join, $column, $where);
    }

    public function sum($table, $join, $column = null, $where = null)
    {
        return $this->sql_interface->sum($table, $join, $column, $where);
    }

    public function action($actions)
    {
        return $this->sql_interface->action($actions);
    }

    public function error()
    {
        return $this->sql_interface->error();
    }

    public function last_query()
    {
        //1.7.2
        return $this->sql_interface->last();
    }

    public function log()
    {
        return $this->sql_interface->log();
    }

    public function debug()
    {
        return $this->sql_interface->debug();
    }

    public function query($sql)
    {
        return $this->sql_interface->query($sql);
    }


}