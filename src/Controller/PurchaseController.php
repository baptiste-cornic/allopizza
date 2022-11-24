<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use App\Form\PurchaseType;
use App\Repository\ProductRepository;
use App\Repository\PurchaseItemRepository;
use App\Repository\PurchaseRepository;
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

            $session->set('purchase',$purchase );

            return $this->redirectToRoute('purchase_confirmation');
        }

        return $this->render('purchase/index.html.twig', [
            'cart' => $fullCart,
            'total_price' => $totalPrice,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/purchase_confirmation', name: 'purchase_confirmation')]
    public function purchaseConfirmation(PurchaseItemRepository $purchaseItemRepo): Response
    {
        $session = $this->requestStack->getSession();

        /** @var PurchaseItem $purchase */
        $purchase = $session->get('purchase');

        if (!$purchase){
            $this->addFlash('notice', 'Vous devez faire une commande' );
            return $this->redirectToRoute('purchase');
        }

        $purchaseItems = $purchaseItemRepo->findBy(['purchase' =>$purchase ]);

        \Stripe\Stripe::setApiKey('sk_test_51M7bsdCZpiMH9jLxOZdLAeHz0mJNNMwqMnCPYTBn0qMWRULF9J34YXgaijklermh3CTWRFr3jbFm5W5uO1USPamr00jwUkIY7Z');

        // Create a PaymentIntent with amount and currency
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $purchase->getAmount() * 100,
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return $this->render('purchase/purchase_confirmation.html.twig', [
            'purchase' => $purchase,
            'purchaseItems' => $purchaseItems,
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    #[Route('/paymentSuccess/{id}', name: 'payment_success')]
    public function paymentSucess(int $id, PurchaseRepository $purchaseRepo, EntityManagerInterface $em): Response
    {
        $purchase = $purchaseRepo->find($id);
        if (!$purchase){
            $this->addFlash('notice', 'Commande invalide' );
            return $this->redirectToRoute('product');
        }

        $purchase->setStatus('done');
        $em->flush();

        $session = $this->requestStack->getSession();
        $session->remove('cart');
        $session->remove('purchase');

        $this->addFlash('success', 'Bravo, tu viens de raquer.');
        return $this->redirectToRoute('home');
    }
}
