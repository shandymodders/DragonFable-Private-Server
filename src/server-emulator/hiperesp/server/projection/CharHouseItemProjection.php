<?php declare(strict_types=1);
namespace hiperesp\server\projection;

use hiperesp\server\vo\CharHouseItemVO;

class CharHouseItemProjection extends Projection {

    public function bought(CharHouseItemVO $charHouseItem): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<buyMech/>');
        $xml->addChild('CharHouseItemID', (string)$charHouseItem->id);
        return $xml;
    }

    public function sold(): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<sellHouseItem/>');
        $xml->addChild('status', 'SUCCESS');
        return $xml;
    }

    public function equipped(): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<equiphouseitem/>');
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><equiphouseitem xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
        $xml->addChild('status', 'SUCCESS');
        return $xml;
    }

    public function unequipped(): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><removehouseitem xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
        $xml->addChild('status', 'SUCCESS');
        return $xml;
    }
}