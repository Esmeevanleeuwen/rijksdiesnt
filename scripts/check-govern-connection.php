<?php

declare(strict_types=1);

// Executed through stdin in Govern's app container. No credentials are printed.
require '/var/www/src/bootstrap.php';

try {
    $base = Govern\Config::fromEnvironment();
    $scope = (new Govern\ConnectionScope($base))->select('rijksdienst', 'postgres', 'public');
    $database = new Govern\Database($scope);
    $reader = $database->reader();
    if ($reader->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql'
        || $reader->query('SELECT current_database()')->fetchColumn() !== 'rijksdienst'
        || $reader->query('SELECT current_user')->fetchColumn() !== 'rijksdienst_govern_reader') {
        throw new RuntimeException('Govern gebruikt niet de verwachte Rijksdienst-leesverbinding.');
    }

    $tables = (new Govern\Catalog($database))->tables();
    $names = array_column($tables, 'name');
    foreach (['entities', 'organizations', 'sources', 'source_records', 'source_record_versions', 'external_identifiers', 'entity_source_links'] as $name) {
        if (! in_array($name, $names, true)) {
            throw new RuntimeException('De Rijksdienst-tabellen zijn nog niet volledig zichtbaar in Govern.');
        }
    }

    // Test the actual table ACL, independently of the read-only session setting.
    // WHERE FALSE guarantees no record changes even if an account is misconfigured.
    $reader->exec('SET default_transaction_read_only = off');
    $denied = false;
    try {
        $reader->beginTransaction();
        $reader->exec('UPDATE public.sources SET name = name WHERE FALSE');
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '42501') {
            throw new RuntimeException('De controle van de PostgreSQL-leesrechten kon niet worden voltooid.');
        }
        $denied = true;
    } finally {
        if ($reader->inTransaction()) {
            $reader->rollBack();
        }
        $reader->exec('SET default_transaction_read_only = on');
    }
    if (! $denied) {
        throw new RuntimeException('Het Govern-account heeft onverwacht schrijfrechten. De koppeling is niet gevalideerd.');
    }

    echo json_encode([
        'status' => 'connected',
        'database' => 'rijksdienst',
        'schema' => 'public',
        'reader' => 'rijksdienst_govern_reader',
        'table_count' => count($tables),
        'write_denied' => true,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (PDOException) {
    fwrite(STDERR, "De PostgreSQL-leesverbinding kon niet worden gecontroleerd. Controleer het lokale verbindingsprofiel en de leesrechten.\n");
    exit(1);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
} catch (Throwable) {
    fwrite(STDERR, "Govern kon de Rijksdienst-leesverbinding niet controleren. Controleer het netwerk, de PostgreSQL-uitbreiding en het lokale verbindingsprofiel.\n");
    exit(1);
}
