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

class Invoice extends \Spot\Entity
{
    protected static $table = "invoices";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => NULL, 'index' => true],
            "uuid" => ["type" => "string", "length" => 50],
            "discount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00, "default" => 0.00 ],
            "subtotal" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "subtotal_discount" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "total" => ["type" => "decimal", "precision" => 10, "scale" => 2, "value" => 0.00 ],
            "quantity" => ["type" => "integer", "value" => 0],
            "consumer" => ["type" => "string", "length" => 250],
            "customer" => ["type" => "string", "length" => 250],
            "first_name" => ["type" => "string", "length" => 250],
            "last_name" => ["type" => "string", "length" => 250],
            "company" => ["type" => "string", "length" => 250],
            "address" => ["type" => "string", "length" => 250],
            "phone" => ["type" => "string", "length" => 250],
            "email" => ["type" => "string", "length" => 250],
            "comments" => ["type" => "text"],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'items' => $mapper->hasMany($entity, 'App\InvoiceItem', 'invoice_id')
                ->order(['created' => 'DESC'])
        ];
    }

    public function transform(Invoice $entity)
    {

        $items = [];

        foreach($entity->items as $item){
            $items[] = [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'title' => $item->title,
                'amount' => $item->amount,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'product' => (object) [
                    'id' => $item->product_id,
                    'pic' => $item->product->pic1_url,
                    'title' => $item->product->title,
                    'code' => $item->product->code,
                    'hexcode' => $item->product->hexcode
                ],
                'pack' => (object) [
                    'id' => $item->pack_id,
                    'title' => $item->pack->title
                ],
                'base' => (object) [
                    'id' => $item->base_id,
                    'title' => $item->base->title
                ],
                'color' => (object) [
                    'id' => $item->color_id,
                    'title' => $item->color->title
                ]
            ];
        }

        return [
            "id" => (integer) $entity->id ?: null,
            "uuid" => (string) $entity->uuid ?: null,
            "customer" => (string) $entity->customer ?: "",
            "first_name" => (string) $entity->first_name ?: "",
            "last_name" => (string) $entity->last_name ?: "",
            "email" => (string) $entity->email ?: "",
            "phone" => (string) $entity->phone ?: "",
            "consumer" => (string) $entity->consumer ?: "",
            "quantity" => (integer) $entity->quantity ?: "",
            "discount" => (float) $entity->discount ?: "",
            "subtotal_discount" => (string) number_format($entity->subtotal_discount,2,',','.'),
            "subtotal" => (string) number_format($entity->subtotal,2,',','.'),
            "total" => (string) number_format($entity->total,2,',','.') ?: "",
            "comments" => (string) $entity->comments ?: "",
            "created" => (string) date('j/n/y H:i',$entity->timestamp()),
            "items" => $items
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "id" => null
        ]);
    }
}
