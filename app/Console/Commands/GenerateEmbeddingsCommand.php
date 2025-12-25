<?php

namespace App\Console\Commands;

use App\Jobs\GenerateEmbeddingJob;
use App\Models\AdvisoryMessage;
use App\Models\BusinessEvent;
use App\Models\NewsItem;
use App\Models\ProactiveInsight;
use Illuminate\Console\Command;

class GenerateEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate
                            {--model= : Specific model to embed (advisory_messages, news_items, business_events, proactive_insights)}
                            {--batch-size=50 : Number of records to process per batch}
                            {--sync : Run synchronously instead of dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for existing records that have not been embedded';

    /**
     * @var array<string, class-string>
     */
    protected array $modelMap = [
        'advisory_messages' => AdvisoryMessage::class,
        'news_items' => NewsItem::class,
        'business_events' => BusinessEvent::class,
        'proactive_insights' => ProactiveInsight::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelFilter = $this->option('model');
        $batchSize = (int) $this->option('batch-size');
        $sync = $this->option('sync');

        $modelsToProcess = $modelFilter
            ? [$modelFilter => $this->modelMap[$modelFilter] ?? null]
            : $this->modelMap;

        if ($modelFilter && ! isset($this->modelMap[$modelFilter])) {
            $this->error("Invalid model: {$modelFilter}");
            $this->info('Available models: '.implode(', ', array_keys($this->modelMap)));

            return self::FAILURE;
        }

        $totalDispatched = 0;

        foreach ($modelsToProcess as $name => $class) {
            if (! $class) {
                continue;
            }

            $this->info("Processing {$name}...");

            $count = $this->processModel($class, $batchSize, $sync);
            $totalDispatched += $count;

            $this->info("  Dispatched {$count} jobs for {$name}");
        }

        $this->newLine();
        $this->info("Total jobs dispatched: {$totalDispatched}");

        if (! $sync && $totalDispatched > 0) {
            $this->warn('Jobs have been dispatched to the queue. Run `php artisan queue:work` to process them.');
        }

        return self::SUCCESS;
    }

    /**
     * Process a single model type.
     *
     * @param  class-string  $modelClass
     */
    protected function processModel(string $modelClass, int $batchSize, bool $sync): int
    {
        $query = $modelClass::query()->where('is_embedded', false);
        $total = $query->count();

        if ($total === 0) {
            $this->info('  No unembedded records found.');

            return 0;
        }

        $this->info("  Found {$total} unembedded records.");

        $count = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($batchSize, function ($records) use ($modelClass, $sync, &$count, $bar) {
            foreach ($records as $record) {
                if ($sync) {
                    GenerateEmbeddingJob::dispatchSync($modelClass, $record->id);
                } else {
                    GenerateEmbeddingJob::dispatch($modelClass, $record->id);
                }
                $count++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        return $count;
    }
}
