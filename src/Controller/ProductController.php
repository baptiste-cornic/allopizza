<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/purchase', name: 'purchase')]
    public function index(ProductRepository $productRepo): Response
    {
        $products = $productRepo->findAll();
        
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $fullCart = [];
        $totalPrice = 0;
        foreach ($cart as $id => $quantity) {
            $product = $productRepo->find($id);
            if($product){
                $fullCart[$id]=[
                    'quantity' => $quantity,
                    'product' => $product
                ];
                $totalPrice = $totalPrice + $product->getPrice() * $quantity;
            }
        }

        return $this->render('purchase/index.html.twig', [
            'products' => $products,
            'cart' => $fullCart,
            'total_price' => $totalPrice,
        ]);
    }

    #[Route('/add_cart/{id}', name: 'add_cart')]
    public function addCart(ProductRepository $productRepo, $id = null): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('purchase');
        }

        $session = $this->requestStack->getSession();
        //$session->remove('cart');
        $cart = $session->get('cart', []);
        if (!empty($cart[$id])){
            $cart[$id]++;
        }else{
            $cart[$id] = 1;
        }

        $session->set('cart', $cart);

/*
dump($pizza);
        dump($cart[0] == $pizza);
        dump($cart[0]);
        dd($cart);

        if (in_array($pizza, $cart)){
            dump($cart);
        }
        else{
            $pizza->quantity = 1;
            array_push($cart, $pizza);
            $session->set('cart', $cart);

        }

        dd($session->get('cart'));
*/
/*
        $session = $this->requestStack->getSession();
        $order = $session->get('cart', []);
        $session->get('cart', []);
        array_push($order, ['name' => $pizza, 'quantity' => 1]);

        $session->set('order', $order);
*/
        return $this->redirectToRoute('purchase');
    }

    #[Route('/remove_cart/{id}', name: 'remove_cart')]
    public function removeCart(ProductRepository $productRepo, $id = null): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('purchase');
        }

        $session = $this->requestStack->getSession();

        $cart = $session->get('cart', []);

        if (!empty($cart[$id])){
            $cart[$id] = $cart[$id] -1;
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('purchase');
    }

    #[Route('/clear_cart', name: 'clear_cart')]
    public function clearCart(): Response
    {
        $session = $this->requestStack->getSession();
        $session->remove('cart');

        return $this->redirectToRoute('purchase');
    }

    #[Route('/delete_cart/{id}', name: 'delete_cart')]
    public function deleteCart(ProductRepository $productRepo, $id = null): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('purchase');
        }

        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);


        if($cart[$id]) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('purchase');
    }




}
