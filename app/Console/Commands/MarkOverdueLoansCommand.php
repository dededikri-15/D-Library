<?php

namespace App\Console\Commands;

use App\Actions\MarkOverdueLoans;
use Illuminate\Console\Command;

class MarkOverdueLoansCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'loans:mark-overdue';

    /**
     * @var string
     */
    protected $description = 'Tandai peminjaman yang lewat jatuh tempo sebagai overdue';

    public function handle(MarkOverdueLoans $markOverdue): int
    {
        $count = $markOverdue->handle();

        $this->info($count === 0
            ? 'Tidak ada peminjaman yang perlu ditandai terlambat.'
            : $count.' peminjaman ditandai terlambat.');

        return self::SUCCESS;
    }
}
