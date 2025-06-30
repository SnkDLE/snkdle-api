<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-exception', name: 'test_exception')]
    public function testException()
    {
        // Lance volontairement une exception
        throw new \Exception("Exception test pour vérifier le listener");
    }
}
