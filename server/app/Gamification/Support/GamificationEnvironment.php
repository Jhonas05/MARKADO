<?php

namespace App\Gamification\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GamificationEnvironment
{
    public static function enabled(): bool
    {
        if (! config('gamification.enabled')) {
            return false;
        }

        if (app()->environment('testing')) {
            return true;
        }

        if (! app()->environment('local') || config('database.default') !== 'pgsql') {
            return false;
        }

        $schema = (string) config('database.connections.pgsql.search_path');
        $host = strtolower((string) config('database.connections.pgsql.host'));
        if (! preg_match('/\Amarkado_demo_v89_[a-z0-9_]+\z/D', $schema)
            || ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return false;
        }

        $current = DB::connection('pgsql')->selectOne('SELECT current_schema() AS schema_name');
        if ($current?->schema_name !== $schema || ! Schema::hasTable('markado_demo_environment_guard')) {
            return false;
        }

        return DB::table('markado_demo_environment_guard')
            ->where('guard_key', 'markado-v8-9-functional-assessment-demo')
            ->where('schema_name', $schema)
            ->exists();
    }
}
