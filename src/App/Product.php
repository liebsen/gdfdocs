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

class Product extends \Spot\Entity
{
    protected static $table = "products";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "type_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "code" => ["type" => "string", "length" => 50],
            "title" => ["type" => "string", "length" => 50],
            "hexcode" => ["type" => "string", "length" => 10],
            "description_html" => ["type" => "text"],
            "performance" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "colorant_unit" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
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
            'type' => $mapper->belongsTo($entity, 'App\ProductType', 'type_id')
            //'quotes' => $mapper->hasMany($entity, 'App\Quote', 'product_id')
        ];
    }

    public function transform(Product $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "type_id" => (integer) $entity->type_id ?: null,
            "code" => (string) $entity->code ?: "",
            "title" => (string) $entity->title ?: "",
            "description" => (string) (strlen(strip_tags($entity->description_html)) ? $entity->description_html : ""),
            "performance" => (float) $entity->performance ?: "",
            "colorant_unit" => (float) $entity->colorant_unit ?: "",
            "hexcode" => (string) $entity->hexcode ?: "",
            "pic1_url" => (string) $entity->pic1_url ?: "",
            "pic2_url" => (string) $entity->pic2_url ?: ""
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
