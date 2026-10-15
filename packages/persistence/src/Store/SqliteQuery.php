<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Store;

use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Persistence\Codec\Codecs;
use Lorekeeper\Persistence\Observation\Source;
use PDO;
use PDOStatement;
use Psl\Type;
use RuntimeException;

final readonly class SqliteQuery
{
    public function __construct(
        private PDO $pdo,
        private Codecs $codecs,
    ) {}

    /**
     * @param array<string, string> $parameters
     *
     * @return list<StoredFact>
     */
    public function facts(string $sql, array $parameters): array
    {
        $statement = $this->statement($sql);
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

    public function statement(string $sql): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        if ($statement === false) {
            throw new RuntimeException('Could not prepare statement.');
        }

        return $statement;
    }
}
