<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_code', 40)->unique();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();

            $table->index(['book_id', 'status']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('book_copy_id')->nullable()->constrained('book_copies')->nullOnDelete();
        });

        $now = now();

        foreach (DB::table('books')->orderBy('id')->get(['id', 'status', 'total_copies']) as $book) {
            $activeLoanIds = DB::table('loans')
                ->where('book_id', $book->id)
                ->whereIn('status', ['borrowed', 'overdue'])
                ->orderBy('id')
                ->pluck('id');
            $copyCount = max(1, (int) $book->total_copies, $activeLoanIds->count());
            $copyIds = [];

            for ($index = 1; $index <= $copyCount; $index++) {
                $copyIds[] = DB::table('book_copies')->insertGetId([
                    'book_id' => $book->id,
                    'inventory_code' => sprintf('BK-%06d-%03d', $book->id, $index),
                    'status' => $index <= $activeLoanIds->count()
                        ? 'borrowed'
                        : ($book->status === 'inactive' ? 'inactive' : 'available'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($activeLoanIds as $index => $loanId) {
                DB::table('loans')->where('id', $loanId)->update([
                    'book_copy_id' => $copyIds[$index],
                ]);
            }

            if ($copyCount !== (int) $book->total_copies) {
                DB::table('books')->where('id', $book->id)->update(['total_copies' => $copyCount]);
            }

            if ($book->status !== 'inactive') {
                DB::table('books')->where('id', $book->id)->update([
                    'status' => $copyCount > $activeLoanIds->count() ? 'available' : 'borrowed',
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('book_copy_id');
        });

        Schema::dropIfExists('book_copies');
    }
};
