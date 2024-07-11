<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityCloneDeep;

use PDO;
use Danilocgsilva\EntitiesDiscover\Entity;
use Danilocgsilva\EntitiesDiscover\LogInterface;

class CloneDeep
{
    private PDO $sourcePdo;

    private PDO $destinyPdo;

    private array $skipTables = [];

    private LogInterface $logMessages;

    private LogInterface $timeDebug;

    public function setSourcePdo(PDO $sourcePdo): self
    {
        $this->sourcePdo = $sourcePdo;
        return $this;
    }

    public function setTimeDebug(LogInterface $timeDebug): self
    {
        $this->timeDebug = $timeDebug;
        return $this;
    }

    public function setLogMessages(LogInterface $logMessages): self
    {
        $this->logMessages = $logMessages;
        return $this;
    }

    public function setDestinyPdo(PDO $destinyPdo): self
    {
        $this->destinyPdo = $destinyPdo;
        return $this;
    }

    public function setSkipTables(array $skipTables): self
    {
        $this->skipTables = $skipTables;
        return $this;
    }

    public function getIdsByRelatedField(string $idValue, string $tableName): array
    {
        $discoveringEntity = (new Entity())
        ->setPdo($this->sourcePdo)
        ->setTable($tableName)
        ->setPdo($this->sourcePdo)
        ->setSkipTables($this->skipTables);

        if (isset($this->timeDebug)) {
            $discoveringEntity->setTimeDebug($this->timeDebug);
        }

        if (isset($this->logMessages)) {
            $discoveringEntity->setDebugMessages($this->logMessages);
        }

        /**
         * @var \Danilocgsilva\EntitiesDiscover\CountResults
         */
        $fullResults = $discoveringEntity->discoverEntitiesOccurrencesByIdentitySync($tableName, $idValue);
        $nonEmptyResults = $fullResults->getSuccessesNotEmpty();

        return $nonEmptyResults;
    }
}
