<?php
/**
 * Date: 21/12/2021
 * Time: 9:52
 */

namespace MigBuilder\Console;

use Illuminate\Console\Command;
use MigBuilder\Builder;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migbuilder:build {connection : The name of the database connection where tables are stored}, {--overwrite}, {--timestamps}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds Model, Factory, Seeder & Migration for the specified MySQL table';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $b = new Builder($this->argument('connection'));
        echo "Migbuilder starting...\r\n";
        $b->buildDatabase($this->option('timestamps'), $this->option('overwrite'));

        return true;
    }

}
