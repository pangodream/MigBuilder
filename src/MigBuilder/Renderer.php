<?php
/**
 * Date: 20/12/2021
 * Time: 15:11
 */

namespace MigBuilder;


use Illuminate\Database\Schema\Blueprint;

class Renderer
{
    private static $columnTypes = [
        'bigint'=>'bigInteger',
        'binary'=>'binary',
        'tinyint'=>'boolean',
        'bit'=>'boolean',
        'char'=>'char',
        'date'=>'date',
        'dateTime'=>'dateTime',
        'decimal'=>'decimal',
        'double'=>'double',
        'float'=>'float',
        'smallint'=>'integer',
        'mediumint'=>'integer',
        'int'=>'integer',
        'time'=>'time',
        'timestamp'=>'timestamp',
        'xxxbigint'=>'unsignedBigInteger',
        'xxxfloat'=>'unsignedFloat',
        'varchar'=>'string',
        'tinytext'=>'string',
        'text'=>'string',
        'mediumtext'=>'string',
        'longtext'=>'string',
    ];

    public static function migration($table, $columns, $constraints, $timestamps = true){
        $code = "";
        $indexCode = "";
        $constraintsCode = "";
        $code .= self::migration_001_class_start($table);
        $code .= self::migration_002_up_start($table);
        foreach($columns as $column){
            if(isset($constraints[$column->name])){
                $column->fk = (object) ['ref_table'=>$constraints[$column->name]->ref_table, 'ref_column'=>$constraints[$column->name]->ref_column];
            }
            $totalCode = self::columnCode($column);
            $code .= $totalCode['code'];
            $indexCode .= $totalCode['indexCode'];
            $constraintsCode .= $totalCode['constraintsCode'];
        }
        if($timestamps == true){
            $code .= "            \$table->timestamps();"."\r\n";
        }
        $code .= "\r\n";
        $code .= "            // Indexes\r\n";
        $code .= $indexCode;
        $code .= "\r\n";
        $code .= "            // Constraints & Foreign Keys\r\n";
        $code .= $constraintsCode;

        $code .= self::migration_003_up_end($table);
        $code .= self::migration_004_down($table);
        $code .= self::migration_005_class_end();
        return $code;
    }

    public static function model($table, $columns, $constraints, $children){
        $code = "";
        $code .= self::model_001_class_start($table);
        //Fillable
        $code .= "    // Fillables (remove the columns you don't need)\r\n";
        $code .= "    protected \$fillable = [";
        $idx = 0;
        foreach($columns as $column){
            $idx++;
            $code .= "'$column->name', ";
            if($idx % 8 == 0 && $idx < sizeof($columns)){
                $code .= "\r\n                           ";
            }
        }
        $code .= "];\r\n";
        $code .= "\r\n";
        //Relationships
        $code .= "    // Parent relationships (change belongsTo to belongsToMany or similar if needed)\r\n";
        foreach($columns as $column){
            if(isset($constraints[$column->name])){
                $code .= self::modelRelationship(Util::firstUpper($constraints[$column->name]->ref_table), "belongsTo");
            }
        }
        $code .= "    // Child relationships (change hasMany to hasOne or similar if needed)\r\n";
        foreach($children as $child){
            $code .= self::modelRelationship(Util::firstUpper($child), "hasMany");
        }

        $code .= self::model_002_class_end();
        return $code;
    }
    public static function factory($table){
        $code = "";
        $code .= self::factory_001($table);
        return $code;
    }
    public static function seeder($table, $columns){
        $code = "";
        $code .= self::seeder_001_start($table);
        $code .= "    // Record sample structure\r\n";
        $code .= "    \$".Util::firstUpper($table, false)." = [\r\n";
        foreach($columns as $column){
            $code .= "        //'$column->name' => ";
            if(in_array($column->data_type, ['varchar','char', 'text', 'date', 'time', 'datetime', 'timestamp'])){
                $code .= "'',\r\n";
            }else{
                $code .= " ,\r\n";
            }
        }
        $code .= "    ];\r\n";
        $code .= self::seeder_002_end();
        return $code;
    }


    /***********************************************************************************
     *                                  UTILITIES
     **********************************************************************************/
    private static function columnCode($column){
        $columnType = null;
        $precision = null;
        $scale = null;
        $length = null;
        $nullable = null;
        $default = $column->default;
        $isReferred = $column->isReferred;
        $columnType = self::$columnTypes[$column->data_type];
        if($columnType == "decimal"){
            $precision = $column->num_precision;
            $scale = $column->num_scale;
        }
        $nullable = $column->nullable == 'YES' ? true : false;
        if(in_array($column->data_type, ['varchar','char'])){
            $length = $column->max_length;
            if($default !== null){
                $default = "'".$default."'";
            }
        }
        if($isReferred == true && $column->name == "id"){
            $columnType = "id";
        }
        if(isset($column->fk)){
            if($column->fk->ref_column == "id"){
                $columnType = "unsignedBigInteger";
            }
        }
        $indexCode = "";
        $constraintsCode = "";

        $code = "            \$table->";
        $code .= $columnType."('$column->name'";
        if($length != null){
            $code .= ", $length";
        }
        if($columnType == "decimal"){
            $code .= ", $precision";
            $code .= ", $scale";
        }
        $code .= ")";
        if($nullable){
            $code .= "->nullable()";
        }

        if($default !== null){
            if ($default === 'CURRENT_TIMESTAMP') {
                $code .= "->useCurrent()";
            } else {
                $code .= "->default($default)";
            }
        }
        $code .= ";\r\n";
        if($isReferred == true){
            $indexCode .= "            \$table->index('$column->name')";
            $indexCode .= ";\r\n";
        }
        if(isset($column->fk)){
            $constraintsCode .= "            \$table->foreign('$column->name')->references('{$column->fk->ref_column}')->on('{$column->fk->ref_table}')";
            $constraintsCode .= ";\r\n";
        }
        return ['code'=>$code, 'indexCode'=>$indexCode, 'constraintsCode'=>$constraintsCode];
    }

    /***********************************************************************************
     *                                  TEMPLATES
     **********************************************************************************/
    /******************************************************************
     * MIGRATION
     */
    private static function migration_001_class_start($table){
        return "
<?php
/* Generated automatically using MigBuilder by Pangodream */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create".Util::firstUpper($table)."Table extends Migration
{
        ";
    }
    private static function migration_002_up_start($table){
        return "
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('$table', function (Blueprint \$table) {
";
    }
    private static function migration_003_up_end($table){
        return "        });
    }
";
    }
    private static function migration_004_down($table){
        return "
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('$table');
        Schema::enableForeignKeyConstraints();
    }
";
    }

    private static function migration_005_class_end(){
        return "
}
";
    }

    /******************************************************************
     * MODEL
     */
    private static function model_001_class_start($table){
        return "<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ".Util::firstUpper($table)." extends Model
{
    use HasFactory;
    protected \$table = '$table';

";
    }
    private static function model_002_class_end(){
        return "
}
";
    }

    /******************************************************************
     * FACTORY
     */
    private static function factory_001($table){
        return "<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\\".Util::firstUpper($table).";

class ".Util::firstUpper($table)."Factory extends Factory
{
    protected \$model = ".Util::firstUpper($table)."::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
        ];
    }
}";
    }

    /******************************************************************
     * SEEDER
     */
    private static function seeder_001_start($table){
        return "<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\\".Util::firstUpper($table).";

class ".Util::firstUpper($table)."Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
";
    }

    private static function seeder_002_end(){
        return "
    }
}";
    }

    /*********************************************************************
     * MODEL Relationship
     */
    private static function modelRelationship($modelName, $relationship){
        return "    public function $modelName(){
        return \$this->$relationship($modelName::class);
    }
";
    }

}
