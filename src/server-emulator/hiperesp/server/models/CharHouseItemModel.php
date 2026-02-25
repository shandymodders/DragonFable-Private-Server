<?php declare(strict_types=1);
namespace hiperesp\server\models;

use hiperesp\server\exceptions\DFException;
use hiperesp\server\vo\CharHouseItemVO;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\HouseItemVO;

class CharHouseItemModel extends Model {

    const COLLECTION = 'char_houseItem';

    /** @return array<CharHouseItemVO> */
    public function getByChar(CharacterVO $char): array {
        $charHouseItems = $this->storage->select(self::COLLECTION, ['charId' => $char->id], null);
        return \array_map(fn($charHouseItem) => new CharHouseItemVO($charHouseItem), $charHouseItems);
    }

    public function getByCharAndId(CharacterVO $char, int $id): CharHouseItemVO {
        $charHouseItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'id' => $id]);
        if(isset($charHouseItem[0]) && $charHouseItem = $charHouseItem[0]) {
            return new CharHouseItemVO($charHouseItem);
        }
        throw new DFException(DFException::CHARACTER_ITEM_NOT_FOUND);
    }

    public function addHouseItemToChar(CharacterVO $char, HouseItemVO $houseItem): CharHouseItemVO {
        if($houseItem->maxStackSize > 1) {
            $charHouseItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'houseItemId' => $houseItem->id]);
            if(isset($charHouseItem[0]) && $charHouseItem = $charHouseItem[0]) {
                if($charHouseItem['count'] < $houseItem->maxStackSize) {
                    $charHouseItem['count']++;
                    $this->storage->update(self::COLLECTION, $charHouseItem);
                    return new CharHouseItemVO($charHouseItem);
                }
                throw new DFException(DFException::CHARACTER_ITEM_MAX_STACK_SIZE);
            }
        }

        $data = [];
        $data['charId'] = $char->id;
        $data['houseItemId'] = $houseItem->id;
        $data['count'] = 1;
        $data['equipSlotPos'] = 0;
        $newData = $this->storage->insert(self::COLLECTION, $data);
        return new CharHouseItemVO($newData);
    }

    public function destroy(CharHouseItemVO $charHouseItem): void {
        $this->storage->delete(self::COLLECTION, ['id' => $charHouseItem->id]);
    }

    public function getByCharAndSlot(CharacterVO $char, int $slot): ?CharHouseItemVO {
        $result = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'equipSlotPos' => $slot]);
        if(isset($result[0])) {
            return new CharHouseItemVO($result[0]);
        }
        return null;
    }

    public function setEquipSlotPos(CharHouseItemVO $charHouseItem, int $slot): void {
        $this->storage->update(self::COLLECTION, [
            'id'           => $charHouseItem->id,
            'equipSlotPos' => $slot,
        ]);
    }

    public function unequipAll(CharacterVO $char): void {
        $charHouseItems = $this->getByChar($char);
        foreach($charHouseItems as $charHouseItem) {
            if($charHouseItem->equipSlotPos !== 0) {
                $this->storage->update(self::COLLECTION, [
                    'id'           => $charHouseItem->id,
                    'equipSlotPos' => 0,
                ]);
            }
        }
    }
}