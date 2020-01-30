<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

namespace App;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class MachineUnit extends \Spot\Entity
{
    protected static $table = "machines_units";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "type_id" => ["type" => "integer", "unsigned" => true, 'index' => true, 'value' => 1],
            "code" => ["type" => "string", "length" => 50],
            "description" => ["type" => "text"],
            "enabled" => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'type' => $mapper->belongsTo($entity, 'App\MachineType', 'type_id')
        ];
    }

    public function transform(MachineUnit $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "code" => (string) $entity->code ?: "",
            "description" => (string) $entity->description ?: ""
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "title" => null,
            "description" => null
        ]);
    }
}
