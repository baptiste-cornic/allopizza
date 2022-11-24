<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\PurchaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/product', name: 'product')]
    public function index(ProductRepository $productRepo, PurchaseService $purchaseService): Response
    {
        $products = $productRepo->findAll();
        
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $fullCart = $purchaseService->getFullCart();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'cart' => $fullCart['cart'],
            'total_price' => $fullCart['totalPrice'],
        ]);
    }

    #[Route('/add_cart/{id}', name: 'add_cart')]
    public function addCart(ProductRepository $productRepo, $id = null, Request $request): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('product');
        }

        $referer = $request->headers->get('referer');

        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id]))
            $cart[$id]++;
        else
            $cart[$id] = 1;

        $session->set('cart', $cart);

        return $this->redirect($referer);
    }

    #[Route('/remove_cart/{id}', name: 'remove_cart')]
    public function removeCart(ProductRepository $productRepo, $id = null, Request $request): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('product');
        }

        $referer = $request->headers->get('referer');

        $session = $this->requestStack->getSession();

        $cart = $session->get('cart', []);

        if($cart[$id] < 1) {
            unset($cart[$id]);
        }

        if (!empty($cart[$id])){
            $cart[$id] = $cart[$id] -1;
        }

        $session->set('cart', $cart);

        return $this->redirect($referer);
    }

    #[Route('/clear_cart', name: 'clear_cart')]
    public function clearCart(): Response
    {
        $session = $this->requestStack->getSession();
        $session->remove('cart');

        return $this->redirectToRoute('product');
    }

    #[Route('/delete_cart/{id}', name: 'delete_cart')]
    public function deleteCart( $id = null, Request $request): Response
    {
        if (!$id){
            $this->addFlash('error', 'Erreur' );
            return $this->redirectToRoute('product');
        }

        $referer = $request->headers->get('referer');

        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);


        if($cart[$id]) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);

        return $this->redirect($referer);
    }
}
