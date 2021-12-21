<?php
/**
 * Date: 20/12/2021
 * Time: 12:59
 */

namespace MigBuilder;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Explorer
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $cdb = null;
    private $db = null;
    public $tables = [];
    public $sortedTables = [];

    public function __construct($conName = null){
        if($conName == null){
            $conName = Config::get('database.default');
        }
        $connections = Config::get('database.connections');
        if(!isset($connections[$conName])){
            die("Fatal error: Connection $conName doesn't exist\n");
        }
        $this->db = $connections[$conName]['database'];
        $this->cdb = DB::connection($conName);

        //Initialize data
        $this->readTables();

        $this->sortTables();

    }
    public function listTables(){
        return $this->tables;
    }
    public function listSortedTables(){
        return $this->sortedTables;
    }
    public function listColumns($table){
        $cols = [];
        if(!isset($this->tables[$table])){
            die("Fatal error: Table $table doesn't exist\n");
        }
        $sql = "select * from information_schema.columns where TABLE_SCHEMA = ? and table_name = ?";
        $columns = $this->select($sql, [$this->db, $table]);
        foreach ($columns as $column){
            $cols[$column->COLUMN_NAME] = (object) [
                'name'=>$column->COLUMN_NAME,
                'position'=>$column->ORDINAL_POSITION,
                'default'=>$column->COLUMN_DEFAULT,
                'nullable'=>$column->IS_NULLABLE,
                'data_type'=>$column->DATA_TYPE,
                'max_length'=>$column->CHARACTER_MAXIMUM_LENGTH,
                'octet_length'=>$column->CHARACTER_OCTET_LENGTH,
                'num_precision'=>$column->NUMERIC_PRECISION,
                'num_scale'=>$column->NUMERIC_SCALE,
                'datetime_precision'=>$column->DATETIME_PRECISION,
                'char_set_name'=>$column->CHARACTER_SET_NAME,
                'collation'=>$column->COLLATION_NAME,
                'column_type'=>$column->COLUMN_TYPE,
                'column_key'=>$column->COLUMN_KEY,
                'extra'=>$column->EXTRA,
                'privileges'=>$column->PRIVILEGES,
                'comment'=>$column->COLUMN_COMMENT,
                'isReferred'=>isset($this->tables[$table]['referredColumns'][$column->COLUMN_NAME])
            ];
        }
        return $cols;
    }
    public function listConstraints($table){
        $cons = [];
        if(!isset($this->tables[$table])){
            die("Fatal error: Table $table doesn't exist\n");
        }
        $sql = "select *
                from information_schema.KEY_COLUMN_USAGE kcu inner join
                (
                      select TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME
                        from information_schema.KEY_COLUMN_USAGE
                        where 1
                        and REFERENCED_TABLE_SCHEMA is not null
                        group by TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME
                        having count(*) = 1
                  ) joc
                on kcu.CONSTRAINT_NAME = joc.CONSTRAINT_NAME and kcu.TABLE_SCHEMA = joc.TABLE_SCHEMA and kcu.TABLE_NAME = joc.TABLE_NAME
                where 1
                  and kcu.REFERENCED_TABLE_SCHEMA is not null
                  and kcu.TABLE_SCHEMA = ?
                  and kcu.TABLE_NAME = ?";
        $constraints = $this->select($sql, [$this->db, $table]);
        foreach ($constraints as $constraint){
            $cons[$constraint->COLUMN_NAME] = (object) [
                'column_name'=>$constraint->COLUMN_NAME,
                'ref_table'=>$constraint->REFERENCED_TABLE_NAME,
                'ref_column'=>$constraint->REFERENCED_COLUMN_NAME
            ];
        }
        return $cons;
    }
    private function readTables(){
        $sql = "select TABLE_NAME from information_schema.TABLES where TABLE_SCHEMA = ?";
        $tables = $this->select($sql, [$this->db]);
        foreach ($tables as $table){
            $this->tables[$table->TABLE_NAME] = [
                'name'=>$table->TABLE_NAME,
                'dependencies'=>[],
                'referredColumns'=>[]
            ];
        }
        foreach ($tables as $table){
            $cons = $this->listConstraints($table->TABLE_NAME);
            foreach ($cons as $c){
                if(!isset($this->tables[$table->TABLE_NAME]['dependencies'][$c->ref_table])){
                    $this->tables[$table->TABLE_NAME]['dependencies'][$c->ref_table] = $c->ref_column;
                }
                if(!isset($this->tables[$c->ref_table]['referredColumns'][$c->ref_column])){
                    $this->tables[$c->ref_table]['referredColumns'][$c->ref_column]=true;
                }
            }
        }
    }
    private function sortTables(){
        $sorted = [];
        $tables = $this->tables;
        while(sizeof($tables)){
            $st = sizeof($tables);
            foreach($tables as $table=>$data){
                foreach($sorted as $s){
                    if(isset($tables[$table]['dependencies'][$s])){
                        unset($tables[$table]['dependencies'][$s]);
                    }
                }
                if(sizeof($tables[$table]['dependencies']) == 0){
                    $sorted[$table] = $table;
                    unset($tables[$table]);
                }
            }
            if(sizeof($tables) == $st){
                echo "Fatal error: some not existing table is being referenced by another.";
                var_dump($tables); die();
            }
        }
        $this->sortedTables = $sorted;
    }

    private function select($sql, $params = []){
        $res = $this->cdb->select($sql, $params);
        return $res;
    }
}
