<?php

namespace App\Interfaces;


use App\Models\Product;

interface CartRepositoryInterface {
    public function add(Product $product, $quantity);

    public function items();

    public function checkout($items = []);

    public function remove(Product $product);
}
