<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Store;

use DateTimeInterface;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Codec\Codecs;
use Override;
use PDO;
use Psl\Type;
use Throwable;

final readonly class SqliteFactStore implements FactStore
{
    private const string SCHEMA = <<<'SQL'
        CREATE TABLE IF NOT EXISTS facts (
            id INTEGER PRIMARY KEY,
            subject_type TEXT NOT NULL,
            subject_id TEXT NOT NULL,
            value_type TEXT NOT NULL,
            value TEXT NOT NULL,
            source TEXT NOT NULL,
            first_seen TEXT NOT NULL,
            last_seen TEXT NOT NULL,
            times_seen INTEGER NOT NULL,
            UNIQUE (subject_type, subject_id, value_type, value, source)
        );
        CREATE INDEX IF NOT EXISTS facts_by_subject ON facts (subject_type, subject_id);
        CREATE INDEX IF NOT EXISTS facts_by_value_type ON facts (value_type);
        SQL;

    private const string RECORD = <<<'SQL'
        INSERT INTO facts (subject_type, subject_id, value_type, value, source, first_seen, last_seen, times_seen)
        VALUES (:subject_type, :subject_id, :value_type, :value, :source, :seen, :seen, 1)
        ON CONFLICT (subject_type, subject_id, value_type, value, source) DO UPDATE SET
            first_seen = min(first_seen, excluded.first_seen),
            last_seen = max(last_seen, excluded.last_seen),
            times_seen = times_seen + 1
        SQL;

    private const string ABOUT = <<<'SQL'
        SELECT subject_type, subject_id, value_type, value, source, first_seen, last_seen, times_seen
        FROM facts
        WHERE subject_type = :subject_type AND subject_id = :subject_id
        ORDER BY id
        SQL;

    private const string WITH_VALUE = <<<'SQL'
        SELECT subject_type, subject_id, value_type, value, source, first_seen, last_seen, times_seen
        FROM facts
        WHERE value_type = :value_type
        ORDER BY id
        SQL;

    private const string SUBJECTS = <<<'SQL'
        SELECT DISTINCT subject_id
        FROM facts
        WHERE subject_type = :subject_type
        ORDER BY subject_id
        SQL;

    private const string ABOUT_ALL = <<<'SQL'
        SELECT subject_type, subject_id, value_type, value, source, first_seen, last_seen, times_seen
        FROM facts
        WHERE subject_type = :subject_type
        ORDER BY id
        SQL;

    private const string LATEST = <<<'SQL'
        SELECT subject_type, subject_id, value_type, value, source, first_seen, last_seen, times_seen
        FROM facts
        ORDER BY first_seen DESC, id DESC
        LIMIT :limit
        SQL;

    private const string COUNT_BY_SOURCE = 'SELECT source, count(*) AS total FROM facts GROUP BY source';

    private SqliteQuery $query;

    public function __construct(
        private PDO $pdo,
        private Codecs $codecs,
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(self::SCHEMA);
        $this->query = new SqliteQuery($pdo, $codecs);
    }

    public static function open(string $path, Codecs $codecs): self
    {
        return new self(new PDO("sqlite:{$path}"), $codecs);
    }

    #[Override]
    public function record(array $observations): void
    {
        $statement = $this->query->statement(self::RECORD);

        $this->pdo->beginTransaction();

        try {
            foreach ($observations as $observation) {
                $fact = $observation->fact;
                $subject = $this->codecs->subjectFor($fact->subject);
                $value = $this->codecs->valueFor($fact->value);

                $statement->execute([
                    'subject_type' => $subject->key,
                    'subject_id' => $subject->encode($fact->subject),
                    'value_type' => $value->key(),
                    'value' => json_encode($value->encode($fact->value), JSON_THROW_ON_ERROR),
                    'source' => $observation->source->value,
                    'seen' => $observation->observedAt->format(DateTimeInterface::ATOM),
                ]);
            }
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        $this->pdo->commit();
    }

    #[Override]
    public function about(Subject $subject): array
    {
        $codec = $this->codecs->subjectFor($subject);

        return $this->query->facts(self::ABOUT, [
            'subject_type' => $codec->key,
            'subject_id' => $codec->encode($subject),
        ]);
    }

    #[Override]
    public function subjects(string $subjectClass): array
    {
        $codec = $this->codecs->subjectForClass($subjectClass);
        $statement = $this->query->statement(self::SUBJECTS);
        $statement->execute(['subject_type' => $codec->key]);

        $subjects = [];
        foreach (Type\vec(Type\string())->coerce($statement->fetchAll(PDO::FETCH_COLUMN)) as $id) {
            $subject = $codec->decode($id);
            if ($subject instanceof $subjectClass) {
                $subjects[] = $subject;
            }
        }

        return $subjects;
    }

    #[Override]
    public function aboutAll(string $subjectClass): array
    {
        return $this->query->facts(self::ABOUT_ALL, [
            'subject_type' => $this->codecs->subjectForClass($subjectClass)->key,
        ]);
    }

    #[Override]
    public function latest(int $limit): array
    {
        return $this->query->facts(self::LATEST, ['limit' => (string) $limit]);
    }

    #[Override]
    public function countBySource(): array
    {
        $statement = $this->query->statement(self::COUNT_BY_SOURCE);
        $statement->execute();

        $counts = [];
        foreach (Type\vec(Type\shape(['source' => Type\string(), 'total' => Type\int()]))
            ->coerce($statement->fetchAll(PDO::FETCH_ASSOC)) as $row) {
            $counts[$row['source']] = $row['total'];
        }

        return $counts;
    }

    #[Override]
    public function withValue(string $valueClass): array
    {
        $codec = $this->codecs->valueForClass($valueClass);

        return $this->query->facts(self::WITH_VALUE, ['value_type' => $codec->key()]);
    }
}
