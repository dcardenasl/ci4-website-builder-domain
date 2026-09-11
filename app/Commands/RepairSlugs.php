<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;

/** Repair generic CMS translation slugs from their current source text. */
final class RepairSlugs extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:repair-slugs';
    protected $description = 'Rebuilds generic CMS slugs from source text and reports the planned changes.';
    protected $usage = 'cms:repair-slugs [--confirm]';

    protected $options = [
        '--confirm' => 'Write the repaired slugs. Without it, the command only reports the planned changes.',
    ];

    public function run(array $params): void
    {
        $confirm  = (bool) CLI::getOption('confirm');
        $database = Database::connect();
        $slugs    = new SlugGenerator();

        /** @var array<string, list<array{id: int, new_slug: string}>> $changesByTable */
        $changesByTable = [
            'cms_page_translations' => $this->buildLocalizedSlugPlan(
                $this->loadRows($database, 'cms_page_translations', 'page_id', 'title'),
                'page_id',
                'title',
                $slugs,
            ),
            'cms_entry_translations' => $this->buildLocalizedSlugPlan(
                $this->loadRows($database, 'cms_entry_translations', 'entry_id', 'title'),
                'entry_id',
                'title',
                $slugs,
            ),
            'cms_collection_translations' => $this->buildLocalizedSlugPlan(
                $this->loadRows($database, 'cms_collection_translations', 'collection_id', 'name'),
                'collection_id',
                'name',
                $slugs,
            ),
            'cms_category_translations' => $this->buildLocalizedSlugPlan(
                $this->loadRows($database, 'cms_category_translations', 'category_id', 'name'),
                'category_id',
                'name',
                $slugs,
            ),
            'cms_tag_translations' => $this->buildLocalizedSlugPlan(
                $this->loadRows($database, 'cms_tag_translations', 'tag_id', 'name'),
                'tag_id',
                'name',
                $slugs,
            ),
        ];

        $labels = [
            'cms_page_translations'       => 'Pages',
            'cms_entry_translations'      => 'Entries',
            'cms_collection_translations' => 'Collections',
            'cms_category_translations'   => 'Categories',
            'cms_tag_translations'        => 'Tags',
        ];
        $totalChanges = 0;

        foreach ($labels as $table => $label) {
            $count = count($changesByTable[$table]);
            $totalChanges += $count;
            CLI::write($label . ' changes: ' . $count);
        }

        if ($totalChanges === 0) {
            CLI::write('No CMS slugs need repair.', 'green');

            return;
        }

        if (! $confirm) {
            CLI::write('Dry-run CMS slug repair. Re-run with --confirm to write the repaired slugs.', 'yellow');

            return;
        }

        $database->transStart();
        $this->applySlugChanges($database, 'cms_page_translations', $changesByTable['cms_page_translations'], true);
        $this->applySlugChanges($database, 'cms_entry_translations', $changesByTable['cms_entry_translations'], true);
        $this->applySlugChanges($database, 'cms_collection_translations', $changesByTable['cms_collection_translations'], false);
        $this->applySlugChanges($database, 'cms_category_translations', $changesByTable['cms_category_translations'], false);
        $this->applySlugChanges($database, 'cms_tag_translations', $changesByTable['cms_tag_translations'], false);
        $database->transComplete();

        if ($database->transStatus() === false) {
            CLI::error('Could not persist the repaired CMS slugs.');

            return;
        }

        CLI::write('CMS slug repair completed successfully.', 'green');
    }

    /**
     * @return list<array<string, mixed>>
     * @param BaseConnection<object, object> $database
     */
    private function loadRows(BaseConnection $database, string $table, string $resourceIdColumn, string $sourceColumn): array
    {
        $query = $database->table($table)
            ->select('id, ' . $resourceIdColumn . ', language_id, slug, ' . $sourceColumn)
            ->orderBy('language_id', 'ASC')
            ->orderBy($resourceIdColumn, 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $rows = $query === false ? [] : $query->getResultArray();

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => isset($row['id'], $row[$resourceIdColumn], $row['language_id'])
        ));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: int, new_slug: string}>
     */
    private function buildLocalizedSlugPlan(
        array $rows,
        string $resourceIdKey,
        string $sourceKey,
        SlugGenerator $generator,
    ): array {
        /** @var array<int, list<array<string, mixed>>> $rowsByLanguage */
        $rowsByLanguage = [];

        foreach ($rows as $row) {
            $languageId = (int) ($row['language_id'] ?? 0);
            if ($languageId > 0) {
                $rowsByLanguage[$languageId][] = $row;
            }
        }

        $changes = [];

        foreach ($rowsByLanguage as $languageRows) {
            $taken = [];
            foreach ($languageRows as $row) {
                $slug = trim((string) ($row['slug'] ?? ''));
                if ($slug !== '') {
                    $taken[$slug] = true;
                }
            }

            foreach ($languageRows as $row) {
                $translationId = (int) ($row['id'] ?? 0);
                $currentSlug   = trim((string) ($row['slug'] ?? ''));
                $sourceValue   = trim((string) ($row[$sourceKey] ?? ''));

                if ($translationId <= 0) {
                    continue;
                }

                if ($currentSlug !== '') {
                    unset($taken[$currentSlug]);
                }

                $baseSlug = $generator->slugify($sourceValue);
                if ($baseSlug === '') {
                    if ($currentSlug !== '') {
                        $taken[$currentSlug] = true;
                    }

                    continue;
                }

                $finalSlug = $generator->uniquify(
                    $baseSlug,
                    static fn (string $candidate): bool => ! isset($taken[$candidate])
                );
                $taken[$finalSlug] = true;

                if ($currentSlug !== $finalSlug) {
                    $changes[] = [
                        'id'       => $translationId,
                        'new_slug' => $finalSlug,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * @param list<array{id: int, new_slug: string}> $changes
     * @param BaseConnection<object, object> $database
     */
    private function applySlugChanges(
        BaseConnection $database,
        string $table,
        array $changes,
        bool $updateTimestamps,
    ): void {
        foreach ($changes as $change) {
            $payload = ['slug' => $change['new_slug']];
            if ($updateTimestamps) {
                $payload['updated_at'] = date('Y-m-d H:i:s');
            }

            $database->table($table)
                ->where('id', $change['id'])
                ->update($payload);
        }
    }
}
