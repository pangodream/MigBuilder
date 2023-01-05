## MigBuilder

MigBuilder is a tool to reverse engineer a MySQL database into Models, Factories, Seeders & Migrations files for Laravel Eloquent ORM.

It reads the database structure and completes as much code as possible to ease the model generation in Laravel.

Unless the model is structured exactly as expected, the code files will contain non accurate code. So ALWAYS it is recommended to check the code once is generated.

The 'perfect' tables definition is as follows:

* Tables should contain a **single column primary key**, better if its name is 'id'
* Relationships have to be declared and always be composed of one single column
* Not frequent datatypes (binary, ~longtext~, ...) have not been tested yet

##### INSTALLATION
```
composer require pangodream/migbuilder
```

##### USAGE
```
php artisan migbuilder:build originschema
```

Migbuilder will read originschema database (originschema is the connection name in Laravel config/app.php file) and will generate the migration, model, seeder and factory files for eaxh of the tables
A good practice is two have two different connections declared in Laravel config/database.php file:
* One alternative connection pointing to the existing database to be reverse engineered
* The main connection that will be used to deploy migrate files and use models in

**CAUTION**:
If you use the command above and **any** of the generated files already exists, the generation will not begin unless you specify the **--overwrite** parameter. Notice that using overwrite will destroy the information you have in any of your files having the same name.

**NOTE**:
Migbuilder is not an error free code generator, but a help to save hand code lines, so you should review the generated code in every case.

### Sample generated files:
**Source DDL**
```sql
CREATE TABLE product (
id INT(11) NOT NULL,
name VARCHAR(45) NOT NULL DEFAULT '',
slug VARCHAR(45) NOT NULL DEFAULT '',
description VARCHAR(45) NOT NULL DEFAULT '',
price VARCHAR(45) NOT NULL DEFAULT '',
subcategory_id INT(11) NOT NULL DEFAULT '0',
brand_id INT(11) NOT NULL DEFAULT '0',
quantity VARCHAR(45) NOT NULL DEFAULT '0',
PRIMARY KEY (id),
INDEX FK_products_subcategories (subcategory_id),
INDEX FK_products_brands (brand_id),
CONSTRAINT FK_products_brands FOREIGN KEY (brand_id) REFERENCES brand (id),
CONSTRAINT FK_products_subcategories FOREIGN KEY (subcategory_id) REFERENCES subcategory (id)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
```
**Migration file**
```php
<?php
/* Generated automatically using MigBuilder by Pangodream */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
        
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 45)->default('');
            $table->string('slug', 45)->default('');
            $table->string('description', 45)->default('');
            $table->string('price', 45)->default('');
            $table->unsignedBigInteger('subcategory_id')->default(0);
            $table->unsignedBigInteger('brand_id')->default(0);
            $table->string('quantity', 45)->default('0');
            $table->timestamps();

            // Indexes
            $table->index('id');

            // Constraints & Foreign Keys
            $table->foreign('subcategory_id')->references('id')->on('subcategory');
            $table->foreign('brand_id')->references('id')->on('brand');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('product');
        Schema::enableForeignKeyConstraints();
    }

}
```
**Model file**
```php
<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'product';

    // Fillables (remove the columns you don't need)
    protected $fillable = ['id', 'name', 'slug', 'description', 'price', 'subcategory_id', 'brand_id', 'quantity', ];

    // Parent relationships (change belongsTo to belongsToMany or similar if needed)
    public function Subcategory(){
        return $this->belongsTo(Subcategory::class);
    }
    public function Brand(){
        return $this->belongsTo(Brand::class);
    }
    // Child relationships (change hasMany to hasOne or similar if needed)
    public function ColorProduct(){
        return $this->hasMany(ColorProduct::class);
    }
    public function Size(){
        return $this->hasMany(Size::class);
    }

}
```
**Seeder file**
```php
<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    // Record sample structure
    $product = [
        //'id' =>  ,
        //'name' => '',
        //'slug' => '',
        //'description' => '',
        //'price' => '',
        //'subcategory_id' =>  ,
        //'brand_id' =>  ,
        //'quantity' => '',
    ];

    }
}
```
**Factory file**
```php
<?php
/* Generated automatically using MigBuilder by Pangodream */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;
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
}
```
