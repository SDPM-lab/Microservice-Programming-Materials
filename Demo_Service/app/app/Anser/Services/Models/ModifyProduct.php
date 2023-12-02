<?php

namespace App\Anser\Services\Models;

class ModifyProduct
{
    public int $p_key;
    public ?string $name = null;
    public ?string $description = null;
    public ?int $price = null;
    
    /**
     * OrderProductDetail constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->p_key = (int)$data['p_key'];
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->price = $data['price'] ?? null;
    }
    
    public function toArray(): array
    {
        $returnArray = [
            "p_key" => $this->p_key,
        ];
        if(!is_null($this->name)){
            $returnArray['name'] = $this->name;
        }
        if(!is_null($this->description)){
            $returnArray['description'] = $this->description;
        }
        if(!is_null($this->price)){
            $returnArray['price'] = $this->price;
        }
        return $returnArray;
    }
}