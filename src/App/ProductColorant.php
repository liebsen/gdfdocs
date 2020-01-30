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

class ProductColorant extends \Spot\Entity
{
    protected static $table = "products_colorants";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "code" => ["type" => "string", "length" => 50],
            "density" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "hexcode" => ["type" => "string", "length" => 10],
            "description" => ["type" => "text"],
            "pic1_url" => ["type" => "string"],
            "enabled" => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            //'users' => $mapper->hasMany($entity, 'App\UserColorant', 'colorant_id'),
        ];
    }

    public function transform(ProductColorant $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "code" => (string) $entity->code ?: "",
            "hexcode" => (string) $entity->hexcode ?: "",
            "density" => (float) $entity->density ?: "",
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
            "id" => null,
            "title" => null
        ]);
    }
}
