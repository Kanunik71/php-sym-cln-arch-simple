<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\Doctrine;

use PHPUnit\Framework\TestCase;

/**
 * Каскадные удаления в БД — только по белому списку.
 *
 * Каскад допустим, если удаление связи не меняет вычисляемое состояние другого
 * агрегата (статус группы, прогресс ассета) и Application не обязан на неё
 * реагировать. Всё, что меняет вычисляемое состояние, Application отвязывает
 * явно — значит, такой FK в схеме быть не должно.
 *
 * Правило: `api/src/Infrastructure/AGENTS.md` → «Каскадные удаления».
 */
final class CascadeDeleteRuleTest extends TestCase
{
    /**
     * Constraint → ON DELETE rule.
     *
     * FK_6737CBFABE94330B — task_group_task.task_group_id: удаляется сама группа.
     * FK_B61A1EED5DA1941 — asset_task.asset_id: удаляется сам ассет.
     * FK_50586597727ACA70 — tasks.parent_id: статус задачи из иерархии не считается.
     * FK_1483A5E945A576B2 — users.avatar_file_id: аватар — не вычисляемое поле.
     */
    private const ALLOWED_MIGRATION_RULES = [
        'FK_6737CBFABE94330B' => 'CASCADE',
        'FK_B61A1EED5DA1941' => 'CASCADE',
        'FK_50586597727ACA70' => 'SET NULL',
        'FK_1483A5E945A576B2' => 'SET NULL',
    ];

    /**
     * Entity file → column constant → ON DELETE rule.
     */
    private const ALLOWED_ENTITY_RULES = [
        'TaskGroupEntity.php' => ['JOIN_COLUMN_TASK_GROUP_ID' => 'CASCADE'],
        'AssetEntity.php' => ['JOIN_COLUMN_ASSET_ID' => 'CASCADE'],
        'TaskEntity.php' => ['COLUMN_PARENT_ID' => 'SET NULL'],
        'UserEntity.php' => ['COLUMN_AVATAR_FILE_ID' => 'SET NULL'],
    ];

    public function testMigrationDeclaresOnlyAllowedCascadeRules(): void
    {
        $violations = [];

        foreach ($this->migrationFiles() as $path) {
            $contents = (string) file_get_contents($path);

            $matched = preg_match_all(
                '/CONSTRAINT\s+(?<constraint>\w+)\s+FOREIGN KEY\s+\([^)]*\)\s+REFERENCES\s+\w+\s*\([^)]*\)(?:\s+ON DELETE\s+(?<rule>SET\s+NULL|\w+))?/s',
                $contents,
                $matches,
                PREG_SET_ORDER,
            );

            if ($matched === false) {
                continue;
            }

            foreach ($matches as $match) {
                $rule = $this->normalizeRule($match['rule'] ?? '');

                if ($rule === '') {
                    continue;
                }

                $constraint = $match['constraint'];
                $allowed = self::ALLOWED_MIGRATION_RULES[$constraint] ?? null;

                if ($allowed !== $rule) {
                    $violations[] = sprintf(
                        '%s: constraint %s has ON DELETE %s (allowed: %s)',
                        basename($path),
                        $constraint,
                        $rule,
                        $allowed ?? 'none',
                    );
                }
            }
        }

        self::assertSame([], $violations, "Unexpected ON DELETE rule:\n".implode("\n", $violations));
    }

    public function testEntitiesDeclareOnlyAllowedCascadeRules(): void
    {
        $violations = [];

        foreach ($this->entityFiles() as $path) {
            $contents = (string) file_get_contents($path);
            $file = basename($path);

            $matched = preg_match_all(
                '/#\[ORM\\\\(?<kind>Inverse)?JoinColumn\((?<args>[^)]*)\)\]/',
                $contents,
                $matches,
                PREG_SET_ORDER,
            );

            if ($matched === false) {
                continue;
            }

            foreach ($matches as $match) {
                $args = $match['args'];

                if (preg_match('/onDelete:\s*\'(?<rule>SET\s+NULL|\w+)\'/', $args, $ruleMatch) !== 1) {
                    continue;
                }

                $rule = $this->normalizeRule($ruleMatch['rule']);
                $column = $this->columnOf($args);

                if ($match['kind'] === 'Inverse') {
                    $violations[] = sprintf(
                        '%s: InverseJoinColumn (%s) must not declare ON DELETE %s',
                        $file,
                        $column,
                        $rule,
                    );

                    continue;
                }

                $allowed = self::ALLOWED_ENTITY_RULES[$file][$column] ?? null;

                if ($allowed !== $rule) {
                    $violations[] = sprintf(
                        '%s: column %s has ON DELETE %s (allowed: %s)',
                        $file,
                        $column,
                        $rule,
                        $allowed ?? 'none',
                    );
                }
            }
        }

        self::assertSame([], $violations, "Unexpected ON DELETE rule:\n".implode("\n", $violations));
    }

    private function normalizeRule(string $rule): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $rule));
    }

    private function columnOf(string $args): string
    {
        if (preg_match('/name:\s*(?:self::|self::COLUMN_)?(?<column>\w+)/', $args, $match) !== 1) {
            return 'unknown';
        }

        return $match['column'];
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        return $this->phpFiles(__DIR__.'/../../../../migrations');
    }

    /**
     * @return list<string>
     */
    private function entityFiles(): array
    {
        return $this->phpFiles(__DIR__.'/../../../../src/Infrastructure/Persistence/Doctrine/Entity');
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        $paths = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                $paths = [...$paths, ...$this->phpFiles($path)];

                continue;
            }

            if (str_ends_with($entry, '.php')) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
