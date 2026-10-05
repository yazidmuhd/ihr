<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cached replacement for Schema::hasTable() / Schema::hasColumn().
 *
 * Older controllers check the schema at runtime (up to ~200 checks on some
 * pages, each one a database query). This loads the whole table → columns map
 * once, keeps it in the file cache, and answers every check from memory.
 * The cache is cleared whenever migrations run (see AppServiceProvider) and
 * starts empty on every new deploy.
 */
class DbSchema
{
    private const CACHE_KEY = 'db-schema-map';

    /** @var array<string, array<string, true>>|null */
    private static ?array $map = null;

    public static function hasTable(string $table): bool
    {
        return isset(self::map()[$table]);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return isset(self::map()[$table][$column]);
    }

    public static function flush(): void
    {
        self::$map = null;
        Cache::store('file')->forget(self::CACHE_KEY);
    }

    private static function map(): array
    {
        return self::$map ??= Cache::store('file')->rememberForever(self::CACHE_KEY, fn () => self::load());
    }

    private static function load(): array
    {
        $map = [];

        if (DB::getDriverName() === 'pgsql') {
            $rows = DB::select("select table_name, column_name from information_schema.columns where table_schema = current_schema()");
            foreach ($rows as $r) {
                $map[$r->table_name][$r->column_name] = true;
            }
            return $map;
        }

        foreach (Schema::getTableListing(schema: null, schemaQualified: false) as $table) {
            foreach (Schema::getColumnListing($table) as $column) {
                $map[$table][$column] = true;
            }
        }

        return $map;
    }
}
