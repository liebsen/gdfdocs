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

class Machine extends \Spot\Entity
{
    protected static $table = "machines";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "type_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "ounce_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "pulse_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "fraction_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "title" => ["type" => "string", "length" => 50],
            "description" => ["type" => "text"],
            "model" => ["type" => "string", "length" => 50],
            "fabricator" => ["type" => "string", "length" => 50],
            "years_of_service" => ["type" => "integer", "length" => 2, "value" => 0],
            "pic1_url" => ["type" => "string"],
            "pic2_url" => ["type" => "string"],
            "started_working"   => ["type" => "datetime", "value" => new \DateTime()],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'type' => $mapper->belongsTo($entity, 'App\MachineType', 'type_id'),
            'ounce' => $mapper->belongsTo($entity, 'App\MachineOunce', 'ounce_id'),
            'pulse' => $mapper->belongsTo($entity, 'App\MachinePulse', 'pulse_id'),
            'fraction' => $mapper->belongsTo($entity, 'App\MachineFraction', 'fraction_id')
        ];
    }

    public function transform(Machine $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "type" => (string) strtolower($entity->type->title) ?: "",
            "ounce" => (float) $entity->ounce->ml ?: "",
            "pulse" => (integer) $entity->pulse->quantity ?: "",
            "fraction" => (float) $entity->fraction->quantity ?: "",
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
