[![PHP Composer](https://github.com/waldirborbajr/syncstock/actions/workflows/php.yaml/badge.svg)](https://github.com/waldirborbajr/syncstock/actions/workflows/php.yaml)

# PHPSynC - SyncStock

PHPSynC is a PHP command-line tool that synchronizes stock data from a Firebird database to a MySQL database. It efficiently transfers product information (IDs, descriptions, quantities, costs and dollar prices) using batch processing and transactions, and runs two MySQL stored procedures after synchronization.

## Features

- Data synchronization from Firebird (TB_ESTOQUE, TB_EST_PRODUTO, TB_EST_INDEXADOR) to MySQL (`tb_estoque_sinc`).
- Batch processing (default batch size: 1000) for memory and performance efficiency.
- PDO with transactions for safe, consistent writes to MySQL.
- Error logging to `sincronizacao.log`.
- Configuration via `.env` using `vlucas/phpdotenv`.
- Executes stored procedures `UpdateQtdVirtual` and `SP_ATUALIZAR_PART_NUMBER` after sync.
- Tracks statistics: records read, inserted, updated and total execution time.

## Requirements

- PHP 7.4 or higher
- PDO Firebird driver (`pdo_firebird`) for Firebird access
- PDO MySQL driver (`pdo_mysql`) for MySQL access
- Composer[![PHP Composer](https://github.com/waldirborbajr/syncstock/actions/workflows/php.yaml/badge.svg)](https://github.com/waldirborbajr/syncstock/actions/workflows/php.yaml)
## Installation

1. Clone the repository:

```bash
git clone https://github.com/waldirborbajr/syncstock.git
cd syncstock
```

2. Install PHP dependencies with Composer (example installs `vlucas/phpdotenv`):

```bash
composer require vlucas/phpdotenv
```

3. Create a `.env` file in the project root and add your database configuration. Example:

```ini
FIREBIRD_DBNAME=C:\path\to\database.fdb
FIREBIRD_USERNAME=sysdba
FIREBIRD_PASSWORD=masterkey
FIREBIRD_CHARSET=UTF8

MYSQL_HOST=localhost
MYSQL_DBNAME=your_mysql_db
MYSQL_USERNAME=your_user
MYSQL_PASSWORD=your_password
MYSQL_CHARSET=utf8mb4
```

On Linux, restrict the `.env` permissions:

```bash
chmod 600 .env
```

> The script will create the `tb_estoque_sinc` table automatically if it doesn't exist.

## Usage

Run the synchronization script from the command line:

```bash
php index.php
```

What the script does:

- Connects to Firebird and MySQL using values from `.env`.
- Reads stock data from Firebird and inserts/updates rows in MySQL in batches.
- Executes the stored procedures `UpdateQtdVirtual` and `SP_ATUALIZAR_PART_NUMBER`.
- Logs steps and statistics to `sincronizacao.log`.

### Example log output

```
2025-10-24 20:40:00 - === INICIANDO SINCRONIZAÇÃO FIREBIRD -> MySQL ===
2025-10-24 20:40:00 - Conectando aos bancos de dados...
2025-10-24 20:40:00 - Conexões estabelecidas com sucesso!
2025-10-24 20:40:00 - Tabela tb_estoque_sinc criada/verificada com sucesso!
2025-10-24 20:40:00 - Total de registros encontrados: 10000
2025-10-24 20:40:01 - Executando stored procedure UpdateQtdVirtual...
2025-10-24 20:40:01 - Stored procedure UpdateQtdVirtual executada com sucesso.
2025-10-24 20:40:01 - Executando stored procedure SP_ATUALIZAR_PART_NUMBER...
2025-10-24 20:40:01 - Stored procedure SP_ATUALIZAR_PART_NUMBER executada com sucesso.
2025-10-24 20:40:01 - Sincronização concluída!
2025-10-24 20:40:01 - Registros lidos: 10000
2025-10-24 20:40:01 - Registros inseridos: 8000
2025-10-24 20:40:01 - Registros atualizados: 2000
2025-10-24 20:40:01 - Tempo de execução: 1.23 segundos
2025-10-24 20:40:01 - === SINCRONIZAÇÃO FINALIZADA ===
2025-10-24 20:40:01 - Tempo total de execução: 1.45 segundos
2025-10-24 20:40:01 - Estatísticas: {"lidos":10000,"inseridos":8000,"atualizados":2000,"tempo":1.23}
```

## Configuration

The script reads configuration from `.env`. Required variables:

- `FIREBIRD_DBNAME` — Path or DSN to Firebird database (e.g. `C:\path\to\database.fdb` or `localhost/3050:C:\path\database.fdb`).
- `FIREBIRD_USERNAME` — Firebird username.
- `FIREBIRD_PASSWORD` — Firebird password.
- `FIREBIRD_CHARSET` — Firebird charset (e.g. `UTF8`).
- `MYSQL_HOST` — MySQL host (e.g. `localhost`).
- `MYSQL_DBNAME` — MySQL database name.
- `MYSQL_USERNAME` — MySQL username.
- `MYSQL_PASSWORD` — MySQL password.
- `MYSQL_CHARSET` — MySQL charset (e.g. `utf8mb4`).

Adjust the `batch_size` variable in `index.php` if you need a different batch size.

## Performance notes

- Batch inserts/updates reduce memory usage and improve throughput. Default: 1000 records.
- MySQL transactions group writes for speed and consistency.
- Ensure proper indexing on Firebird and MySQL for best performance.

## Troubleshooting

- Connection errors: verify `.env` credentials and network access.
- Stored procedure errors: confirm `UpdateQtdVirtual` and `SP_ATUALIZAR_PART_NUMBER` exist and the MySQL user has execute permissions.
- Missing dependencies: run `composer install` to restore required packages.

## Contributing

Contributions are welcome.

1. Fork the repo
2. Create a branch: `git checkout -b feature/your-feature`
3. Make changes and commit: `git commit -m "Add your feature"`
4. Push and open a pull request

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## Contact

Use GitHub Issues for questions or support.

---

© 2025 waldirborbajr
