<?php

use App\Models\Site;
use App\Support\SitePublishState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->longText('published_blocks_snapshot')->nullable()->after('published_at');
        });

        Site::query()->whereNotNull('published_at')->each(function (Site $site) {
            $site->update([
                'published_blocks_snapshot' => SitePublishState::snapshot($site->fresh()),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('published_blocks_snapshot');
        });
    }
};
