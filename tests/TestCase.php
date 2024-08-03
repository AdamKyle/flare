<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Tests\Setup\AttackDataCacheSetUp;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $useMockForAttackDataCache = true;

    public string $baseUrl = 'http://localhost';

    public ?AttackDataCacheSetUp $attackDataCacheSetUp;

    public function setUp(): void
    {

        parent::setUp();

        if ($this->useMockForAttackDataCache) {
            $this->attackDataCacheSetUp = new AttackDataCacheSetUp;

            $this->attackDataCacheSetUp->mockCacheBuilder($this->app);
        }
    }

    public function tearDown(): void
    {

        $this->attackDataCacheSetUp = null;

        $this->useMockForAttackDataCache = true;

        $this->cleanUp();

        parent::tearDown();
    }

    /**
     * Cleanup the database.
     *
     * Loop over any database table that has more than 0 records and delete them.
     *
     * We do this with no regard to foreign keys.
     */
    private function cleanUp(): void
    {
        $parentDirectory = dirname(__DIR__);
        $path = $parentDirectory.'/app/Flare/Models';

        $files = File::files($path);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);

            $fullyQualifiedClassName = '\\App\\Flare\\Models\\'.$className;

            if (class_exists($fullyQualifiedClassName) && is_subclass_of($fullyQualifiedClassName, Model::class)) {
                if ($fullyQualifiedClassName::count() > 0) {

                    if ($className === 'UserSiteAccessStatistics') {
                        $fullyQualifiedClassName::truncate();

                        continue;
                    }

                    $fullyQualifiedClassName::all()->each(function ($record) {
                        $record->delete();
                    });
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
