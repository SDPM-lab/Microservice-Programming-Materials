<?php

namespace App\Anser\Services\Models;

class OrderProductDetail
{
    public int $p_key;
    public int $price;
    public int $amount;
    
    /**
     * OrderProductDetail constructor.
     *
     * @param integer $p_key
     * @param integer $price
     * @param integer $amount
     */
    public function __construct(int $p_key, int $price, int $amount)
    {
        $this->p_key = $p_key;
        $this->price = $price;
        $this->amount = $amount;
    }

    public function toArray(): array
    {
        return [
            "p_key" => $this->p_key,
            "price" => $this->price,
            "amount" => $this->amount
        ];
    }
}