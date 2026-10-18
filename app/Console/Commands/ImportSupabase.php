<?php

namespace App\Console\Commands;

use App\Exceptions\DryRunFinished;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PDO;
use Throwable;

/**
 * Copies all EazyPOS data from Supabase into this MySQL database.
 *
 * Source is either the JSON produced by database/supabase/export.sql, or a
 * direct connection to the Supabase Postgres database (SUPABASE_DB_URL).
 * Rows keep their original UUIDs, so every relation and document number is
 * preserved. Re-running is safe: existing rows are updated, not duplicated.
 */
class ImportSupabase extends Command
{
    protected $signature = 'eazypos:import-supabase
        {file? : Path to the JSON export created with database/supabase/export.sql}
        {--pgsql : Read directly from the Supabase database in SUPABASE_DB_URL}
        {--dry-run : Validate and count rows without writing anything}';

    protected $description = 'Import businesses, users, products, sales and all other data from Supabase';

    /** Import order follows foreign keys. */
    private const TABLES = [
        'businesses', 'business_users', 'staff_invitations', 'document_sequences',
        'categories', 'brands', 'units', 'products', 'customers', 'suppliers',
        'cash_registers', 'register_transactions',
        'sales', 'sale_items', 'sale_payments', 'invoices', 'sale_returns', 'sale_return_items',
        'purchases', 'purchase_items', 'purchase_returns', 'purchase_return_items',
        'expense_categories', 'expenses', 'stock_movements', 'quotations', 'quotation_items', 'shipments',
    ];

    private const JSON_COLUMNS = ['business_users.permissions', 'staff_invitations.permissions'];

    /** Movement types that take stock out, so their quantity must be negative. */
    private const OUTGOING_MOVEMENTS = ['sale', 'purchase_return', 'adjustment_remove', 'damage', 'transfer_out'];

    public function handle(): int
    {
        try {
            $source = $this->option('pgsql') ? $this->fromPostgres() : $this->fromJson((string) $this->argument('file'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $counts = [];

        try {
            DB::transaction(function () use ($source, $dryRun, &$counts) {
                $users = array_map(fn ($u) => $this->mapUser($u), $source['users'] ?? []);
                $counts['users'] = $this->write('users', $users, $dryRun);

                foreach (self::TABLES as $table) {
                    $columns = $this->columns($table);
                    $rows = [];
                    foreach ($source[$table] ?? [] as $row) {
                        $rows[] = $this->mapRow($table, (array) $row, $columns);
                    }
                    $counts[$table] = $this->write($table, $rows, $dryRun);
                }

                if ($dryRun) {
                    throw new DryRunFinished;
                }
            });
        } catch (DryRunFinished) {
            $this->warn('Dry run: nothing was written.');
        } catch (Throwable $e) {
            $this->error('Import failed and was rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Table', 'Rows'], collect($counts)->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->info($dryRun ? 'Dry run complete.' : 'Import complete. Users can sign in with their existing email and password.');

        return self::SUCCESS;
    }

    private function fromJson(string $file): array
    {
        if ($file === '' || ! is_file($file)) {
            throw new \RuntimeException('Give the path to the JSON export, or use --pgsql. See database/supabase/export.sql.');
        }

        $raw = trim((string) file_get_contents($file));
        // The SQL editor may wrap the cell in quotes or a {"eazypos_export": ...} object.
        $data = json_decode($raw, true);
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (is_array($data) && isset($data['eazypos_export'])) {
            $data = is_string($data['eazypos_export']) ? json_decode($data['eazypos_export'], true) : $data['eazypos_export'];
        }
        if (! is_array($data) || ! isset($data['businesses'])) {
            throw new \RuntimeException('The file is not a valid EazyPOS export (no "businesses" key found).');
        }

        return $data;
    }

    private function fromPostgres(): array
    {
        if (! extension_loaded('pdo_pgsql')) {
            throw new \RuntimeException('The pdo_pgsql PHP extension is not enabled. Enable it in php.ini or use the JSON export instead.');
        }

        $url = (string) env('SUPABASE_DB_URL', '');
        $parts = parse_url($url);
        if (! $url || ! $parts || empty($parts['host'])) {
            throw new \RuntimeException('Set SUPABASE_DB_URL in .env (Supabase > Project Settings > Database > Connection string).');
        }

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require', $parts['host'], $parts['port'] ?? 5432, ltrim($parts['path'] ?? '/postgres', '/'));
        $pdo = new PDO($dsn, urldecode($parts['user'] ?? ''), urldecode($parts['pass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $data = ['users' => $pdo->query('select id, email, encrypted_password, email_confirmed_at, raw_user_meta_data, last_sign_in_at, created_at, updated_at from auth.users')->fetchAll(PDO::FETCH_ASSOC)];
        foreach (self::TABLES as $table) {
            $data[$table] = $pdo->query("select * from public.{$table}")->fetchAll(PDO::FETCH_ASSOC);
        }

        return $data;
    }

    private function mapUser(array $u): array
    {
        $meta = $u['raw_user_meta_data'] ?? [];
        $meta = is_string($meta) ? (json_decode($meta, true) ?: []) : (array) $meta;
        $hash = (string) ($u['encrypted_password'] ?? '');

        // Supabase stores bcrypt as $2a$; PHP's equivalent prefix is $2y$.
        $password = str_starts_with($hash, '$2a$') ? '$2y$'.substr($hash, 4) : $hash;
        if (! str_starts_with($password, '$2y$')) {
            // Invited users who never set a password get an unusable one.
            $password = bcrypt(Str::random(48));
        }

        $email = mb_strtolower(trim((string) $u['email']));

        return [
            'id' => $u['id'],
            'name' => mb_substr((string) ($meta['full_name'] ?? $meta['owner_name'] ?? $meta['name'] ?? $email), 0, 255),
            'email' => $email,
            'email_verified_at' => $this->timestamp($u['email_confirmed_at'] ?? null),
            'password' => $password,
            'is_super_admin' => 0,
            'last_login_at' => $this->timestamp($u['last_sign_in_at'] ?? null),
            'created_at' => $this->timestamp($u['created_at'] ?? null) ?? now()->format('Y-m-d H:i:s'),
            'updated_at' => $this->timestamp($u['updated_at'] ?? null) ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  array<string, string>  $columns  name => MySQL type
     */
    private function mapRow(string $table, array $row, array $columns): array
    {
        $out = [];
        foreach ($columns as $column => $type) {
            if (! array_key_exists($column, $row)) {
                continue;
            }
            $value = $row[$column];

            if (in_array("{$table}.{$column}", self::JSON_COLUMNS, true)) {
                $value = is_string($value) ? $value : json_encode($value ?? new \stdClass);
            } elseif (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
                $value = $this->timestamp($value);
            } elseif ($type === 'tinyint' || $type === 'tinyint(1)') {
                $value = in_array($value, [true, 1, '1', 't', 'true'], true) ? 1 : 0;
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }

            $out[$column] = $value;
        }

        if ($table === 'stock_movements' && in_array($out['movement_type'] ?? null, self::OUTGOING_MOVEMENTS, true)) {
            // Older Supabase code stored some removals as positive numbers.
            $out['quantity'] = -abs((float) $out['quantity']);
        }
        if ($table === 'staff_invitations') {
            // Supabase invitation links cannot be redeemed here; owners can re-invite.
            $out['token_hash'] = null;
            if (($out['status'] ?? '') === 'pending') {
                $out['status'] = 'expired';
            }
        }
        if ($table === 'sales' && ($out['payment_status'] ?? '') === 'due') {
            $out['payment_status'] = 'credit';
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function columns(string $table): array
    {
        $columns = [];
        foreach (Schema::getColumns($table) as $column) {
            $columns[$column['name']] = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));
        }

        return $columns;
    }

    private function write(string $table, array $rows, bool $dryRun): int
    {
        if ($rows === [] || $dryRun) {
            return count($rows);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $update = array_values(array_diff(array_keys($chunk[0]), ['id']));
            DB::table($table)->upsert($chunk, ['id'], $update);
        }

        return count($rows);
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->utc()->format('Y-m-d H:i:s.u');
    }
}
