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

class ProductBase extends \Spot\Entity
{
    protected static $table = "products_bases";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "code" => ["type" => "string", "length" => 50],
            "title" => ["type" => "string", "length" => 50],            
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
            //'users' => $mapper->hasMany($entity, 'App\User', 'user_id')
        ];
    }

    public function transform(ProductBase $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "code" => (string) $entity->code ?: "",
            "title" => (string) $entity->title ?: "",
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
