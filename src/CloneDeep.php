<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityCloneDeep;

use PDO;
use Danilocgsilva\EntitiesDiscover\Entity;
use Danilocgsilva\EntitiesDiscover\LogInterface;
use Danilocgsilva\Database\Discover;

class CloneDeep
{
    private PDO $sourcePdo;

    private PDO $destinyPdo;

    private array $skipTables = [];

    private LogInterface $logMessages;

    private LogInterface $timeDebug;

    private array $tableAndItsFields = [];

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

    public function getIdsByRelatedField(string $idValue, string $tableName, array $tableIdsPairs = [], int $iterationCount = 0): array
    {
        // /** @var int $initialCountTableIdsPairs */
        // $initialCountTableIdsPairs = $this->countTableIdsPairs($tableIdsPairs);

        /** @var \Danilocgsilva\EntitiesDiscover\Entity $discoveringEntity */
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

        /** @var \Danilocgsilva\EntitiesDiscover\CountResults $fullResults */
        $fullResults = $discoveringEntity->discoverEntitiesOccurrencesByIdentitySync($tableName, $idValue);
        $nonEmptyResults = $fullResults->getSuccessesNotEmpty();

        foreach ($nonEmptyResults as $tableLoopName => $count) {

            $tableIdsPairs[$tableLoopName]['keyName'] = $keyName = $this->getTableId($tableLoopName);
            $tableIdsPairs[$tableLoopName]['values'] = $this->getIds(
                $keyName, 
                $tableLoopName, 
                $discoveringEntity->getTableIdFieldName(),
                $idValue
            );
        }

        // /** @var int $newCountIdPairs */
        // $newCountIdPairs = $this->countTableIdsPairs($tableIdsPairs);

        // print(sprintf("The initial count of table id pairs is %s. After, the count of table id pairs is %s.\n", $initialCountTableIdsPairs, $newCountIdPairs));
        // if ($initialCountTableIdsPairs != $newCountIdPairs) {
        //     print(
        //         sprintf(
        //             "Receiving the id value of %s and table name of %s. "
        //             . "The initial count is %s. Final count is %s . The initial and final count id is different. "
        //             . "The current level is %s. So fetching must continue.\n",
        //             $idValue,
        //             $tableName,
        //             $initialCountTableIdsPairs,
        //             $newCountIdPairs
        //         )
        //     );
        //     print("SHOULD ENTER IN A NEW LOOP!\n");
        //     $iterationCount++;
        //     foreach ($tableIdsPairs as $tableName => $ids) {
        //         foreach ($ids as $id) {
        //             $this->getIdsByRelatedField((string) $id, $tableName, $tableIdsPairs, $iterationCount);
        //         }
        //     }
        // }
        // else {
        //     print(sprintf("After receiving the id value of %s and table name of %s, the initial and final count id is equal. All relations has been fetched!.\n", $idValue, $tableName));
        // }

        return $tableIdsPairs;
    }

    private function getTableId(string $tableName): string
    {
        $databaseDiscover = new Discover($this->sourcePdo);

        $fieldsAlreadyFetched = array_key_exists($tableName, $this->tableAndItsFields) && count($this->tableAndItsFields[$tableName]) > 0;
        if (!$fieldsAlreadyFetched) {
            foreach ($databaseDiscover->getFieldsFromTable($tableName) as $fieldName) {
                $this->tableAndItsFields[$tableName][] = (string) $fieldName;
            }
        }

        return $this->tableAndItsFields[$tableName][0];
    }

    private function getIds(string $fieldIdName, string $tableName, string $parentTableFieldName, string $parentTableFieldValue)
    {
        $baseQuery = sprintf("SELECT %s FROM %s WHERE %s = :idvalue;", $fieldIdName, $tableName, $parentTableFieldName);
        $preResults = $this->sourcePdo->prepare($baseQuery);
        $preResults->execute([":idvalue" => $parentTableFieldValue]);
        $preResults->setFetchMode(PDO::FETCH_NUM);
        $ids = [];
        while ($row = $preResults->fetch()) {
            $ids[] = $row[0];
        }
        return $ids;
    }

    private function countTableIdsPairs($tableIdsPairs): int
    {
        $totalCount = 0;
        foreach ($tableIdsPairs as $ids) {
            $totalCount += count($ids);
        }
        return $totalCount;
    }

    private function fillTableIdsPairs($tableLoopName, $tableIdFieldName, $idValue): array
    {
        $ids = $this->getIds(
            $this->getTableId($tableLoopName), 
            $tableLoopName, 
            $tableIdFieldName,
            $idValue
        );
        return $ids;
    }
}
