<?php

namespace App\Controller;

use App\Entity\ExceptionLog;
use App\Entity\Fichier;
use App\Entity\ReplayToken;
use App\Entity\Transfert;
use App\Repository\TransfertRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class DownloadController extends AbstractController
{

    /**
     * @Route("/downloadFichiers/{token}/", name="downloadFichiers")
     */
    public function downloadFichiers(Request $request, EntityManagerInterface $entityManager): Response
    {


        $tokenVal = $request->get('token');

        $token = $entityManager->getRepository(ReplayToken::class)->findOneBy(['value' => $tokenVal]);
        $transfert = $entityManager->getRepository(Transfert::class)->findOneBy(['token' => $tokenVal]);

        if ($token && $transfert) {
            if (date_diff(new DateTimeImmutable(), $token->getValidUntil()) > 0) {
                $transfertData = array(
                    'exp' => $transfert->getExpediteur(),
                    'expMail' => $transfert->getExpediteurMail(),
                    'obj' => $transfert->getObjet(),
                    'msg' => $transfert->getMessage()
                );
                $fichiersData = array();

                $fichiersIds = $transfert->getFichiers();
                $fichiersIdsTab = explode(',', $fichiersIds);

                foreach ($fichiersIdsTab as &$fichierId) {
                    $fichier = $entityManager->getRepository(Fichier::class)->find($fichierId);
                    if ($fichier) {
                        array_push($fichiersData, array(
                            "nom" => $fichier->getNom(),
                            "taille" => $fichier->getTaille(),
                            "path" => $fichier->getPath()
                        ));
                    }
                }

                $data = array(
                    "ecran" => "download",
                    "transfertData" => $transfertData,
                    "fichiersData" => $fichiersData
                );
            } else {
                $data = array(
                    "ecran" => "download",
                    "error" => "Ce transfert a expiré.<br/><br/>Expéditeur des fichiers : <strong>".$transfert->getExpediteur()."</strong> (<a href=\"mailto:" . $transfert->getExpediteurMail() . ">" . $transfert->getExpediteurMail() . "</a>)"
                );
            }
        } else {
            $data = array(
                "ecran" => "download",
                "error" => "Lien non valide."
            );
        }
        return $this->render('home.twig', $data);
    }
}
