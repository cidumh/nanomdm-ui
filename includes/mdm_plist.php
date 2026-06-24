<?php
/**
 * MDM plist XML 解析（Authenticate 等 checkin 载荷）
 */

class MdmPlist
{
    public static function decodeRawPayload(?string $base64): array
    {
        if ($base64 === null || trim($base64) === '') {
            return [];
        }

        $xml = base64_decode($base64, true);
        if ($xml === false || trim($xml) === '') {
            return [];
        }

        return self::parseDict($xml);
    }

    public static function decodeRawPayloadXml(?string $base64): string
    {
        if ($base64 === null || trim($base64) === '') {
            return '';
        }
        $xml = base64_decode($base64, true);
        if ($xml === false || trim($xml) === '') {
            return '';
        }
        return $xml;
    }

    public static function hasQueryResponses(string $xml): bool
    {
        return stripos($xml, '<key>QueryResponses</key>') !== false;
    }

    public static function hasProfileList(string $xml): bool
    {
        return stripos($xml, '<key>ProfileList</key>') !== false;
    }

    public static function parseProfileList(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false || !isset($doc->dict)) {
            return [];
        }

        $nodes = [];
        foreach ($doc->dict->children() as $node) {
            $nodes[] = $node;
        }

        for ($i = 0, $count = count($nodes); $i < $count; $i++) {
            if ($nodes[$i]->getName() !== 'key') {
                continue;
            }
            if (trim((string) $nodes[$i]) !== 'ProfileList') {
                continue;
            }
            $i++;
            if ($i < $count && $nodes[$i]->getName() === 'array') {
                return self::parseArrayElement($nodes[$i]);
            }
            break;
        }

        return [];
    }

    public static function parseDict(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false || !isset($doc->dict)) {
            return [];
        }

        return self::parseDictElement($doc->dict);
    }

    public static function parseQueryResponses(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false || !isset($doc->dict)) {
            return [];
        }

        $nodes = [];
        foreach ($doc->dict->children() as $node) {
            $nodes[] = $node;
        }

        for ($i = 0, $count = count($nodes); $i < $count; $i++) {
            if ($nodes[$i]->getName() !== 'key') {
                continue;
            }
            if (trim((string) $nodes[$i]) !== 'QueryResponses') {
                continue;
            }
            $i++;
            if ($i < $count && $nodes[$i]->getName() === 'dict') {
                return self::parseDictElement($nodes[$i]);
            }
            break;
        }

        return [];
    }

    private static function parseDictElement(SimpleXMLElement $dict): array
    {
        $result = [];
        $nodes = [];
        foreach ($dict->children() as $node) {
            $nodes[] = $node;
        }

        for ($i = 0, $count = count($nodes); $i < $count; $i++) {
            if ($nodes[$i]->getName() !== 'key') {
                continue;
            }
            $key = trim((string) $nodes[$i]);
            $i++;
            if ($i >= $count) {
                break;
            }
            $valueNode = $nodes[$i];
            $name = $valueNode->getName();
            if ($name === 'string') {
                $result[$key] = (string) $valueNode;
            } elseif ($name === 'data') {
                $result[$key] = preg_replace('/\s+/', '', (string) $valueNode);
            } elseif ($name === 'integer') {
                $result[$key] = (string) $valueNode;
            } elseif ($name === 'real') {
                $result[$key] = (string) $valueNode;
            } elseif ($name === 'true') {
                $result[$key] = 'true';
            } elseif ($name === 'false') {
                $result[$key] = 'false';
            } elseif ($name === 'dict') {
                $result[$key] = self::parseDictElement($valueNode);
            } elseif ($name === 'array') {
                $result[$key] = self::parseArrayElement($valueNode);
            }
        }

        return $result;
    }

    private static function parseArrayElement(SimpleXMLElement $array): array
    {
        $items = [];
        foreach ($array->children() as $child) {
            $childName = $child->getName();
            if ($childName === 'dict') {
                $items[] = self::parseDictElement($child);
            } elseif ($childName === 'string') {
                $items[] = (string) $child;
            } elseif ($childName === 'integer') {
                $items[] = (string) $child;
            } elseif ($childName === 'real') {
                $items[] = (string) $child;
            } elseif ($childName === 'true') {
                $items[] = 'true';
            } elseif ($childName === 'false') {
                $items[] = 'false';
            }
        }

        return $items;
    }

    public static function parseCreatedAt(?string $iso): string
    {
        if ($iso === null || trim($iso) === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            $dt = new DateTime($iso);
            $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }
}
