<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Store;

use DateTimeImmutable;
use DateTimeInterface;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Codec\Codecs;
use Lorekeeper\Persistence\Observation\Source;
use Override;
use PDO;
use PDOStatement;
use Psl\Type;
use RuntimeException;
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

    public function __construct(
        private PDO $pdo,
        private Codecs $codecs,
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(self::SCHEMA);
    }

    public static function open(string $path, Codecs $codecs): self
    {
        return new self(new PDO("sqlite:{$path}"), $codecs);
    }

    #[Override]
    public function record(array $observations): void
    {
        $statement = $this->prepare(self::RECORD);

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

        return $this->fetch(self::ABOUT, ['subject_type' => $codec->key, 'subject_id' => $codec->encode($subject)]);
    }

    #[Override]
    public function withValue(string $valueClass): array
    {
        $codec = $this->codecs->valueForClass($valueClass);

        return $this->fetch(self::WITH_VALUE, ['value_type' => $codec->key()]);
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return list<StoredFact>
     */
    private function fetch(string $sql, array $parameters): array
    {
        $statement = $this->prepare($sql);
        $statement->execute($parameters);

        $rows = Type\vec(Type\shape([
            'subject_type' => Type\string(),
            'subject_id' => Type\string(),
            'value_type' => Type\string(),
            'value' => Type\string(),
            'source' => Type\string(),
            'first_seen' => Type\string(),
            'last_seen' => Type\string(),
            'times_seen' => Type\positive_int(),
        ]))
            ->coerce($statement->fetchAll(PDO::FETCH_ASSOC));

        return array_map(
            fn(array $row): StoredFact => new StoredFact(
                new Fact(
                    $this->codecs->subject($row['subject_type'])->decode($row['subject_id']),
                    $this->codecs
                        ->value($row['value_type'])
                        ->decode(json_decode($row['value'], associative: true, flags: JSON_THROW_ON_ERROR)),
                ),
                Source::from($row['source']),
                new DateTimeImmutable($row['first_seen']),
                new DateTimeImmutable($row['last_seen']),
                $row['times_seen'],
            ),
            $rows,
        );
    }

    private function prepare(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        if ($statement === false) {
            throw new RuntimeException('Could not prepare statement.');
        }

        return $statement;
    }
}
