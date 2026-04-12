<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DbCheckCommand extends Command
{
    protected $signature = 'db:check';

    protected $description = 'Verify PostgreSQL and Redis connectivity';

    public function handle(): int
    {
        $result = ['db' => 'disconnected', 'redis' => 'disconnected'];

        try {
            DB::select('SELECT 1');
            $result['db'] = 'connected';
        } catch (\Throwable) {
            // db stays disconnected
        }

        try {
            Redis::connection()->ping();
            $result['redis'] = 'connected';
        } catch (\Throwable) {
            // redis stays disconnected
        }

        $this->output->write((string) json_encode($result));

        if ($result['db'] === 'connected' && $result['redis'] === 'connected') {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
