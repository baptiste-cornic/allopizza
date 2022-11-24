<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class PurchaseService
{
    private ProductRepository $productRepo;
    private RequestStack $requestStack;

    public function __construct(ProductRepository $productRepo, RequestStack $requestStack,){
        $this->productRepo = $productRepo;
        $this->requestStack = $requestStack;
    }

    public function getFullCart(){
        $sessionCart = $this->requestStack->getSession()->get('cart');
        $cart = [];
        $totalPrice = 0;

        foreach ($sessionCart as $id => $quantity){
            $product = $this->productRepo->find($id);
            if($product){
                $cart[$id]=[
                    'quantity' => $quantity,
                    'product' => $product
                ];
                $totalPrice = $totalPrice + $product->getPrice() * $quantity;
            }
        }

        return ['cart' => $cart, 'totalPrice'=>$totalPrice];
    }
}