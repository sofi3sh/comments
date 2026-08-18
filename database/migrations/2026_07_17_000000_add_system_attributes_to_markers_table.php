<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Додає технічний код і ознаку системного маркера.
     */
    public function up(): void
    {
        Schema::table('markers', function (Blueprint $table): void {
            $table->string('code', 50)->nullable()->after('id');
            $table->boolean('is_system')->default(false)->after('is_active');
            $table->unique('code', 'markers_code_unique');
        });

        // Переносить системний маркер зі старого фіксованого ідентифікатора.
        DB::table('markers')
            ->where('id', 7)
            ->update([
                'code' => 'breaking_news',
                'is_system' => true,
            ]);

        DB::table('markers')
            ->whereNull('code')
            ->chunkById(10, function ($markers): void {
                foreach ($markers as $marker) {
                    $name = DB::table('marker_translations')
                        ->where('marker_id', $marker->id)
                        ->orderByRaw("locale = 'en' DESC")
                        ->orderBy('id')
                        ->value('name');

                    DB::table('markers')
                        ->where('id', $marker->id)
                        ->update([
                            'code' => $this->uniqueCode((int) $marker->id, $name),
                        ]);
                }
            });
    }

    /**
     * Видаляє технічні атрибути системних маркерів.
     */
    public function down(): void
    {
        Schema::table('markers', function (Blueprint $table): void {
            $table->dropUnique('markers_code_unique');
            $table->dropColumn(['code', 'is_system']);
        });
    }

    /**
     * Генерує унікальний технічний код маркера у форматі нижнього підкреслення.
     */
    private function uniqueCode(int $markerId, ?string $name): string
    {
        $baseCode = Str::slug((string) $name, '_');
        $baseCode = $baseCode !== '' ? $baseCode : 'marker_' . $markerId;
        $baseCode = Str::substr($baseCode, 0, 50);
        $code = $baseCode;
        $suffix = 2;
        $maxAttempts = 100;

        while (DB::table('markers')->where('code', $code)->exists()) {
            if ($suffix > $maxAttempts) {
                throw new RuntimeException(
                    "Unable to generate a unique marker code for '{$baseCode}'."
                );
            }

            $suffixValue = '_' . $suffix;
            $code = Str::substr($baseCode, 0, 50 - strlen($suffixValue)) . $suffixValue;
            $suffix++;
        }

        return $code;
    }

};
