<?php
/**
 * Date: 20/12/2021
 * Time: 15:28
 */

namespace MigBuilder;


class Builder
{
    /**
     * @var Explorer;
     */
    private $explorer = null;
    public function __construct($connection = null){

        $this->explorer = new Explorer($connection);
    }
    public function buildDatabase($timestamps = true, $overwrite = false){

        $tables = $this->explorer->listSortedTables();
        $index = 0;
        foreach($tables as $table){
            $index++;
            $this->buildAll($table, $index, $timestamps, $overwrite);
        }
    }
    private function buildAll($table, $index, $timestamps = true, $overwrite = false){
        $modelFile = app_path().'/Models/'.self::modelFileName($table);
        $factoryFile = database_path().'/factories/'.self::factoryFileName($table);
        $seederFile = database_path().'/seeders/'.self::seederFileName($table);
        $migrationFileName = $this->getExistingMigrationFileName($table);
        $migrationFile = database_path().'/migrations/'.$migrationFileName;
        if($overwrite == false && (
                file_exists($modelFile) ||
                file_exists($factoryFile) ||
                file_exists($seederFile) ||
                ($migrationFileName !== false && file_exists($migrationFile))
            )){
            die("One of the files to be generated exists and overwrite option was not specified.");
        }else{
            @unlink($modelFile);
            @unlink($factoryFile);
            @unlink($seederFile);
            @unlink($migrationFile);
        }

        $this->buildModel($table, $timestamps);
        $this->buildFactory($table);
        $this->buildSeeder($table);
        $this->buildMigration($table, $index, $timestamps);
    }
    private function buildModel($table, $timestamps){
        $columns = $this->explorer->listColumns($table);
        $constraints = $this->explorer->listConstraints($table);
        $children = [];
        $tables = $this->explorer->listTables();
        foreach($tables as $t){
            if(isset($t['dependencies'][$table])){
                $children[] = $t['name'];
            }
        }
        $code = Renderer::model($table, $columns, $constraints, $children, $timestamps);
        file_put_contents(app_path().'/Models/'.self::modelFileName($table), $code);
    }
    private function buildFactory($table){
        $code = Renderer::factory($table);
        file_put_contents(database_path().'/factories/'.self::factoryFileName($table), $code);
    }
    private function buildSeeder($table){
        $columns = $this->explorer->listColumns($table);
        $code = Renderer::seeder($table, $columns);
        file_put_contents(database_path().'/seeders/'.self::seederFileName($table), $code);
    }
    private function buildMigration($table, $index, $timestamps = true){
        $columns = $this->explorer->listColumns($table);
        $constraints = $this->explorer->listConstraints($table);
        $code = Renderer::migration($table, $columns, $constraints, $timestamps);
        file_put_contents(database_path().'/migrations/'.self::migrationFileName($table, $index), $code);
    }

    private function modelFileName($table){
        return Util::firstUpper($table).".php";
    }
    private function factoryFileName($table){
        return Util::firstUpper($table)."Factory.php";
    }
    private function seederFileName($table){
        return Util::firstUpper($table)."Seeder.php";
    }
    private function migrationFileName($table, $index = null){
        if($index == null){
            $idx = date("His");
        }else{
            $idx = substr("000000".$index, -6);
        }
        $date = date("Y_m_d_");
        return $date.$idx."_create_".strtolower($table)."_table.php";
    }
    private function getExistingMigrationFileName($table){
        $pattern = "_create_".strtolower($table)."_table.php";
        $filename = false;
        $hd = dir(database_path().'/migrations/');
        while(false !== ($entry = $hd->read())){
            if(substr($entry, -strlen($pattern)) == $pattern){
                $filename = $entry;
                break;
            }
        }
        return $filename;
    }
}
