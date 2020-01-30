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

class ProductPack extends \Spot\Entity
{
    protected static $table = "products_packs";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "product_type_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "title" => ["type" => "string", "length" => 50],
            "kg" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "lt" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "description" => ["type" => "text"],
            "pic1_url" => ["type" => "string"],
            "pic2_url" => ["type" => "string"],
            "enabled" => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'product_type' => $mapper->belongsTo($entity, 'App\ProductType', 'product_type_id')
        ];
    }

    public function transform(ProductPack $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (string) $entity->title ?: "",
            "fulltitle" => implode(' ',[(string) $entity->title ?: "",(string) $entity->texture_type->title ?: ""]),
            "kg" => (float) $entity->kg ?: "",
            "description" => (integer) $entity->description ?: "",
            "pic1_url" => (string) $entity->pic1_url ?: "",
            "timespan" => \human_timespan_short($entity->created->format('U')),
            "type" => (object) [
                'id' => (integer) $entity->product_type_id ?: "",
                'title' => (string) $entity->product_type->title ?: ""
            ]
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
