<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Exception\UnknownTypeException;

final readonly class Codecs
{
    /** @var array<class-string, SubjectCodec> */
    private array $subjectsByClass;

    /** @var array<string, SubjectCodec> */
    private array $subjectsByKey;

    /** @var array<class-string, ValueCodec> */
    private array $valuesByClass;

    /** @var array<string, ValueCodec> */
    private array $valuesByKey;

    /**
     * @param list<SubjectCodec> $subjects
     * @param list<ValueCodec> $values
     */
    public function __construct(array $subjects, array $values)
    {
        $subjectsByClass = [];
        $subjectsByKey = [];
        foreach ($subjects as $codec) {
            $subjectsByClass[$codec->class] = $codec;
            $subjectsByKey[$codec->key] = $codec;
        }

        $valuesByClass = [];
        $valuesByKey = [];
        foreach ($values as $codec) {
            $valuesByClass[$codec->class()] = $codec;
            $valuesByKey[$codec->key()] = $codec;
        }

        $this->subjectsByClass = $subjectsByClass;
        $this->subjectsByKey = $subjectsByKey;
        $this->valuesByClass = $valuesByClass;
        $this->valuesByKey = $valuesByKey;
    }

    /** @throws UnknownTypeException */
    public function subjectFor(Subject $subject): SubjectCodec
    {
        return (
            $this->subjectsByClass[$subject::class] ?? throw new UnknownTypeException('No codec for subject '
            . $subject::class)
        );
    }

    /**
     * @param class-string<Subject> $class
     *
     * @throws UnknownTypeException
     */
    public function subjectForClass(string $class): SubjectCodec
    {
        return $this->subjectsByClass[$class] ?? throw new UnknownTypeException("No codec for subject {$class}");
    }

    /** @throws UnknownTypeException */
    public function subject(string $key): SubjectCodec
    {
        return $this->subjectsByKey[$key] ?? throw new UnknownTypeException("No subject codec for key '{$key}'");
    }

    /** @throws UnknownTypeException */
    public function valueFor(object $value): ValueCodec
    {
        return (
            $this->valuesByClass[$value::class] ?? throw new UnknownTypeException('No codec for value ' . $value::class)
        );
    }

    /**
     * @param class-string $class
     *
     * @throws UnknownTypeException
     */
    public function valueForClass(string $class): ValueCodec
    {
        return $this->valuesByClass[$class] ?? throw new UnknownTypeException("No codec for value {$class}");
    }

    /** @throws UnknownTypeException */
    public function value(string $key): ValueCodec
    {
        return $this->valuesByKey[$key] ?? throw new UnknownTypeException("No value codec for key '{$key}'");
    }
}
