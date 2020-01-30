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

class ProductType extends \Spot\Entity
{
    protected static $table = "products_types";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "title" => ["type" => "string", "length" => 50],
            "description" => ["type" => "text"],
            "pic1_url" => ["type" => "string"],
            "pic2_url" => ["type" => "string"],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            //'users' => $mapper->hasMany($entity, 'App\User', 'user_id')
            'pack' => $mapper->hasMany($entity, 'App\ProductPack', 'product_type_id')
        ];
    }

    public function transform(ProductType $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (integer) $entity->title ?: "",
            "description" => (integer) $entity->description ?: "",
            "timespan" => \human_timespan_short($entity->created->format('U'))
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
