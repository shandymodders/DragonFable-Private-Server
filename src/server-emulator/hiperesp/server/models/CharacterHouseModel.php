<?php declare(strict_types=1);
namespace hiperesp\server\models;

use hiperesp\server\exceptions\DFException;
use hiperesp\server\vo\CharacterHouseVO;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\HouseVO;

class CharacterHouseModel extends Model {

    const COLLECTION = 'char_house';

    /** @return array<CharacterHouseVO> */
    public function getByChar(CharacterVO $char): array {
        $results = $this->storage->select(self::COLLECTION, ['charId' => $char->id], null);
        return \array_map(fn($result) => new CharacterHouseVO($result), $results);
    }

    public function addHouseToChar(CharacterVO $char, HouseVO $house): CharacterHouseVO {
        $owned = $this->countByChar($char);
        $data = [
            'charId'   => $char->id,
            'houseId'  => $house->id,
            'equipped' => $owned === 0 ? 1 : 0,
        ];
        $newData = $this->storage->insert(self::COLLECTION, $data);
        return new CharacterHouseVO($newData);
    }

    public function getByCharAndId(CharacterVO $char, int $charHouseId): CharacterHouseVO {
        $result = $this->storage->select(self::COLLECTION, [
            'id'     => $charHouseId,
            'charId' => $char->id,
        ]);
        if (!isset($result[0])) {
            throw new DFException(DFException::INVALID_REFERENCE);
        }
        return new CharacterHouseVO($result[0]);
    }

    public function getEquippedByChar(CharacterVO $char): ?CharacterHouseVO {
        $result = $this->storage->select(self::COLLECTION, [
            'charId'   => $char->id,
            'equipped' => 1,
        ]);
        if (!isset($result[0])) {
            return null;
        }
        return new CharacterHouseVO($result[0]);
    }

    public function equip(CharacterVO $char, CharacterHouseVO $charHouse): void {
        $results = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'equipped' => 1], null);
        foreach($results as $result) {
            $this->storage->update(self::COLLECTION, ['id' => $result['id'], 'equipped' => 0]);
        }
        $this->storage->update(self::COLLECTION, ['id' => $charHouse->id, 'equipped' => 1]);
    }

    public function countByChar(CharacterVO $char): int {
        return count($this->storage->select(self::COLLECTION, ['charId' => $char->id]));
    }

    public function destroy(CharacterHouseVO $charHouse): void {
        $this->storage->delete(self::COLLECTION, ['id' => $charHouse->id]);
    }

}