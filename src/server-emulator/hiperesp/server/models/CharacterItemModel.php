<?php declare(strict_types=1);
namespace hiperesp\server\models;

use hiperesp\server\exceptions\DFException;
use hiperesp\server\vo\CharacterItemVO;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\ItemVO;

class CharacterItemModel extends Model {

    const COLLECTION = 'char_item';
    const MAX_PROGRESS_LEVEL = 30;

    private static bool $progressFieldsEnsured = false;

    private function ensureProgressFields(): void {
        if(self::$progressFieldsEnsured) {
            return;
        }
        $this->storage->ensureCollectionFields(self::COLLECTION, ['level', 'experience']);
        self::$progressFieldsEnsured = true;
    }

    /** @return array<CharacterItemVO> */
    public function getByChar(CharacterVO $char): array {
        $this->ensureProgressFields();
        $charItems = $this->storage->select(self::COLLECTION, ['charId' => $char->id], null);

        if(\count($charItems) > 1) {
            $itemIds = \array_map(
                fn(array $charItem): int => (int)$charItem['itemId'],
                $charItems
            );

            $items = $this->storage->select('item', ['id' => $itemIds], null);
            $itemsById = [];
            foreach($items as $item) {
                $itemsById[(int)$item['id']] = $item;
            }

            \usort($charItems, function(array $left, array $right) use ($itemsById): int {
                $leftItem = $itemsById[(int)$left['itemId']] ?? null;
                $rightItem = $itemsById[(int)$right['itemId']] ?? null;

                $leftCategoryId = (int)($leftItem['categoryId']);
                $rightCategoryId = (int)($rightItem['categoryId']);
                if($leftCategoryId !== $rightCategoryId) {
                    return $leftCategoryId <=> $rightCategoryId;
                }

                $leftEquipSpot = (string)($leftItem['equipSpot']);
                $rightEquipSpot = (string)($rightItem['equipSpot']);
                if($leftEquipSpot !== $rightEquipSpot) {
                    return $leftEquipSpot <=> $rightEquipSpot;
                }

                $leftLevel = (int)($leftItem['level']);
                $rightLevel = (int)($rightItem['level']);
                if($leftLevel !== $rightLevel) {
                    return $rightLevel <=> $leftLevel;
                }

                $leftItemId = (int)($leftItem['id']);
                $rightItemId = (int)($rightItem['id']);
                if($leftItemId !== $rightItemId) {
                    return $leftItemId <=> $rightItemId;
                }

                return ((int)$left['id']) <=> ((int)$right['id']);
            });
        }

        return \array_map(fn($charItem) => new CharacterItemVO($charItem), $charItems);
    }

    public function addItemToChar(CharacterVO $char, ItemVO $item): CharacterItemVO {
        $this->ensureProgressFields();
        if($item->maxStackSize > 1) {
            $charItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'itemId' => $item->id]);
            if(isset($charItem[0]) && $charItem = $charItem[0]) {
                if($charItem['count'] < $item->maxStackSize) {
                    $charItem['count']++;
                    $this->storage->update(self::COLLECTION, $charItem);
                    return new CharacterItemVO($charItem);
                }
                throw new DFException(DFException::CHARACTER_ITEM_MAX_STACK_SIZE);
            }
        }

        $data = [];
        $data['charId'] = $char->id;
        $data['itemId'] = $item->id;
        $data['count'] = 1;
        $newData = $this->storage->insert(self::COLLECTION, $data);
        return new CharacterItemVO($newData);
    }

    public function removeItemFromChar(CharacterVO $char, ItemVO $item, int $amount): CharacterItemVO {
        $this->ensureProgressFields();
        $charItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'itemId' => $item->id]);
        if(!isset($charItem[0]) || !$charItem = $charItem[0]) {
            throw new DFException(DFException::CHARACTER_ITEM_NOT_FOUND);
        }

        if($charItem['count'] < $amount) {
            throw new DFException(DFException::ITEM_NOT_ENOUGH);
        }

        $charItem['count'] -= $amount;
        if($charItem['count'] > 0) {
            $this->storage->update(self::COLLECTION, $charItem);
        } else {
            $this->storage->delete(self::COLLECTION, ['id' => $charItem['id']]);
        }

        return new CharacterItemVO($charItem);
    }

    public function getByCharAndId(CharacterVO $char, int $id): CharacterItemVO {
        $this->ensureProgressFields();
        $charItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'id' => $id]);
        if(isset($charItem[0]) && $charItem = $charItem[0]) {
            return new CharacterItemVO($charItem);
        }
        throw new DFException(DFException::CHARACTER_ITEM_NOT_FOUND);
    }

    public function getByCharAndItemId(CharacterVO $char, int $itemId): CharacterItemVO {
        $this->ensureProgressFields();
        $charItem = $this->storage->select(self::COLLECTION, ['charId' => $char->id, 'itemId' => $itemId]);
        if(isset($charItem[0]) && $charItem = $charItem[0]) {
            return new CharacterItemVO($charItem);
        }
        throw new DFException(DFException::CHARACTER_ITEM_NOT_FOUND);
    }

    public function addExperience(CharacterVO $char, int $id, int $experience): CharacterItemVO {
        if($experience <= 0 || $experience > 1000000) {
            throw new DFException(DFException::BAD_REQUEST);
        }

        $update = function() use ($char, $id, $experience): CharacterItemVO {
            $charItem = $this->getByCharAndId($char, $id);
            $level = \max(1, \min(self::MAX_PROGRESS_LEVEL, $charItem->level));
            $newExperience = \max(0, $charItem->experience) + $experience;

            while($level < self::MAX_PROGRESS_LEVEL) {
                $required = CharacterItemVO::experienceRequiredForLevel($level);
                if($newExperience < $required) {
                    break;
                }
                $newExperience -= $required;
                $level++;
            }

            if($level >= self::MAX_PROGRESS_LEVEL) {
                $newExperience = \min(
                    $newExperience,
                    CharacterItemVO::experienceRequiredForLevel(self::MAX_PROGRESS_LEVEL)
                );
            }

            $this->storage->update(self::COLLECTION, [
                'id' => $charItem->id,
                'level' => $level,
                'experience' => $newExperience
            ]);

            return $this->getByCharAndId($char, $id);
        };

        // Multiple catches can complete close together. Keep the read/level-up/
        // write cycle atomic so no awarded fishing experience is lost.
        $lockPath = \sys_get_temp_dir().'/dfps-item-exp-'.\sha1("{$char->id}:{$id}").'.lock';
        $lock = @\fopen($lockPath, 'c');
        if($lock === false) {
            return $update();
        }

        $locked = false;
        try {
            $locked = \flock($lock, LOCK_EX);
            return $update();
        } finally {
            if($locked) {
                \flock($lock, LOCK_UN);
            }
            \fclose($lock);
        }
    }

    public function destroy(CharacterItemVO $charItem): void {
        $this->storage->delete(self::COLLECTION, ['id' => $charItem->id]);
    }

    public function saveWeaponConfig(CharacterVO $char, array $equippedItemIds): void {
        $charItems = $this->getByChar($char);

        foreach ($charItems as $charItem) {
            $isEquipped = in_array($charItem->id, $equippedItemIds);
            $item = $charItem->getItem();
            if ($item->equipSpot === 'Armor') {
                $isEquipped = false;
            }

            $this->storage->update(self::COLLECTION, [
                'id' => $charItem->id,
                'equipped' => $isEquipped ? 1 : 0
            ]);
        }
    }

    public function bankToChar(CharacterVO $char, int $itemId): void {
        $count = 0;
        foreach($char->getBag() as $characterItem) {
            if ($characterItem->banked) {
                continue;
            }
            $count++;
            if ($count >= $char->bagSlots) {
                throw new DFException(DFException::BAD_REQUEST);
            }
        }
        $this->storage->update(self::COLLECTION, [
            'id' => $itemId,
            'banked' => 0
        ]);
    }

    public function charToBank(CharacterVO $char, int $itemId): void {
        $bankedCount = 0;
        foreach($char->getBag() as $characterItem) {
            if (!$characterItem->banked) {
                continue;
            }
            $item = $characterItem->getItem();
            if(!$item->dragonAmulet){
                $bankedCount++;
            }
            if($bankedCount >= ($char->bankSlots + 5)) {
                throw new DFException(DFException::BAD_REQUEST);
            }
        }

        $this->storage->update(self::COLLECTION, [
            'id' => $itemId,
            'banked' => 1,
            'equipped' => 0
        ]);
    }

}
