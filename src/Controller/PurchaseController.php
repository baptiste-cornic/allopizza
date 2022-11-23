<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use App\Form\PurchaseType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PurchaseController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/purchase', name: 'purchase')]
    public function index(Request $request, ProductRepository $productRepo, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()){
            $this->addFlash('notice', 'Vous devez vous connecter pour aller plus loin.' );
            return $this->redirectToRoute('login');
        }

        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (empty($cart)){
            $this->addFlash('error', 'Erreur : Panier vide.' );
            return $this->redirectToRoute('product');
        }

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

        $purchase = new Purchase();
        $form = $this->createForm(PurchaseType::class ,$purchase);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){

            $purchase->setUser($this->getUser());
            $purchase->setPurchasetAt(new \DateTime());
            $purchase->setStatus('waiting');
            $purchase->setAmount($totalPrice);
            $em->persist($purchase);

            foreach ($fullCart as $item){

                $purchaseItem = new PurchaseItem();
                $purchaseItem->setProduct($item['product']);
                $purchaseItem->setProductName($item['product']->getName());
                $purchaseItem->setProductPrice($item['product']->getPrice());
                $purchaseItem->setPurchase($purchase);
                $purchaseItem->setQuantity($item['quantity']);

                $em->persist($purchaseItem);
            }

            $em->flush();
        }

        return $this->render('purchase/index.html.twig', [
            'cart' => $fullCart,
            'total_price' => $totalPrice,
            'form' => $form->createView(),
        ]);
    }
}
