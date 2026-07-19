<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations;

class PrepareTestDatabase extends BaseCommand
{
    protected $group = 'Tests';
    protected $name = 'tests:prepare-db';
    protected $description = 'Drop all tables in the tests database and rerun the App migrations.';
    protected $usage = 'tests:prepare-db';

    public function run(array $params)
    {
        CLI::write('Preparing test database (group "tests").');

        $db = $this->connectToTestsDatabase();
        if ($db === null) {
            return EXIT_ERROR;
        }

        $isSqlite = strtolower($db->DBDriver) === 'sqlite3';
        $this->dropAllTables($db);

        if ($isSqlite || in_array(strtolower($db->DBDriver), ['mysqli', 'mysql'], true)) {
            $db->close();
            $db = $this->connectToTestsDatabase();
            if ($db === null) {
                return EXIT_ERROR;
            }
        }
        $this->resetMigrationHistory($db);
        $this->migrateAppSchema($db);
        $ready = $this->ensureExpectedTablesPresent($db);

        if (! $ready) {
            CLI::error('Post-migration verification failed. Inspect the database and rerun the command.');
            return EXIT_ERROR;
        }

        CLI::write('Test database prepared.', 'green');
        return EXIT_SUCCESS;
    }

    /**
     * @return BaseConnection<object, object>|null
     */
    private function connectToTestsDatabase(): ?BaseConnection
    {
        try {
            $connection = Database::connect('tests');
            $connection->initialize();
            return $connection;
        } catch (DatabaseException $e) {
            CLI::error('Unable to connect to the tests database: ' . $e->getMessage());
            CLI::write('Ensure the `ci4_website_builder_domain_test` schema exists and the credentials in phpunit.xml/.env match.', 'yellow');
            return null;
        }
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function dropAllTables(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'sqlite3') {
            $path = $db->database;
            if (is_file($path)) {
                unlink($path);
                CLI::write('Deleted existing SQLite test database file.');
            }
            return;
        }

        if ($driver === 'mysqli' || $driver === 'mysql') {
            $database = (string) $db->database;
            if (! str_ends_with($database, '_test')) {
                throw new \RuntimeException('Refusing to recreate a database whose name does not end in `_test`.');
            }

            $identifier = $db->escapeIdentifiers($database);
            $db->query('DROP DATABASE IF EXISTS ' . $identifier);
            $db->query(
                'CREATE DATABASE ' . $identifier
                . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
            );
            $db->query('USE ' . $identifier);
            $db->resetDataCache();
            CLI::write('Recreated the MySQL test database.');
            return;
        }

        $db->resetDataCache();
        $existingTables = $db->listTables();
        $tables = $existingTables === false ? [] : $existingTables;

        // Drop the migration ledger with the schema. Keeping and truncating it
        // separately allowed stale connection metadata to preserve partial
        // history after a failed run, producing duplicate migration execution.
        if (empty($tables)) {
            CLI::write('No tables found to drop.');
            return;
        }

        $this->disableForeignKeys($db);

        foreach ($tables as $table) {
            if ($db->query('DROP TABLE IF EXISTS ' . $db->escapeIdentifiers($table)) === false) {
                throw new \RuntimeException('Unable to drop test table: ' . $table);
            }
        }

        $this->enableForeignKeys($db);
        $db->resetDataCache();

        $remainingTables = $db->listTables();
        if ($remainingTables !== false && $remainingTables !== []) {
            throw new \RuntimeException('Test database cleanup left tables behind: ' . implode(', ', $remainingTables));
        }
        CLI::write('Dropped all existing tables.');
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function disableForeignKeys(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function enableForeignKeys(BaseConnection $db): void
    {
        $driver = strtolower($db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            $db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function migrateAppSchema(BaseConnection $db): void
    {
        $config = new Migrations();
        $config->enabled = true;

        /** @var MigrationRunner $runner */
        $runner = service('migrations', $config, $db, false);
        $runner->setSilent(false);
        $runner->setNamespace('App');
        $runner->latest('tests');
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function resetMigrationHistory(BaseConnection $db): void
    {
        if (! $db->tableExists('migrations')) {
            return;
        }

        $db->table('migrations')->truncate();
    }

    /**
     * @param BaseConnection<object, object> $db
     */
    private function ensureExpectedTablesPresent(BaseConnection $db): bool
    {
        $existingTables = $db->listTables();
        $tables         = $existingTables === false ? [] : $existingTables;
        $required       = ['migrations'];
        foreach ($required as $table) {
            if (! in_array($table, $tables, true)) {
                CLI::error("Required table `{$table}` is missing after migrations.");
                return false;
            }
        }

        $count = 0;
        if ($db->tableExists('migrations')) {
            $count = $db->table('migrations')->countAllResults(false);
        }

        if ($count === 0) {
            CLI::error('`migrations` table does not contain entries.');
            return false;
        }

        return true;
    }
}
