<?php

namespace App\Controller;

use App\Entity\ExceptionLog;
use App\Entity\Fichier;
use App\Entity\ReplayToken;
use App\Entity\Transfert;
use DateTime;
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

class EnvoiController extends AbstractController
{
    private static function sendMailConfirm(mixed $mail, mixed $destEmail)
    {
        //TODO faire mail à l'expéditeur avec token
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.example.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'user@example.com';                     //SMTP username
            $mail->Password   = 'secret';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('from@example.com', 'Mailer');
            $mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
            $mail->addAddress('ellen@example.com');               //Name is optional
            $mail->addReplyTo('info@example.com', 'Information');
            $mail->addCC('cc@example.com');
            $mail->addBCC('bcc@example.com');

            //Attachments
            $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Here is the subject';
            $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        $token = new ReplayToken();
        $token->setValue('test');
        $token->setValidUntil(new DateTime('now'));
    }

    /**
     * @Route("/", name="index")
     */
    public function index(ManagerRegistry $doctrine): Response
    {
        $data = array(
            "ecran" => "envoi"
        );
        return $this->render('home.twig', $data);
    }

    /**
     * @Route("/checkFormData", name="checkFormData")
     */
    public function checkFormData(Request $request, EntityManagerInterface $entityManager): JSONResponse
    {
        $data = array();

        $uploadedFile = $request->files->get('file');
        if ($uploadedFile && is_array($uploadedFile)) {
            foreach ($uploadedFile as $file) {
                $fichier = new Fichier();
                $fichier->setFichier($file);
                $fichier->setPath($file->getPathName());
                $fichier->setNom($file->getClientOriginalName());
                $fichier->setTaille($file->getSize());
                $fichier->setMimeType($file->getClientMimeType());
                $fichier->upload();
                $entityManager->persist($fichier);
                $entityManager->flush();
                $fichierId = $fichier->getId();
                $data[$file->getClientOriginalName()]['fichierId'] = $fichierId;
            }
        }

        if ($request->request->has('nom')) {
            $nom = $_POST['nom'];
        }
        if ($request->request->has('prenom')) {
            $prenom = $_POST['prenom'];
        }
        if ($request->request->has('mail')) {
            $mail = $_POST['mail'];
        }
        if ($request->request->has('mail')) {
            $destEmail = $_POST['mail'];
        }
        if ($request->request->has('obj')) {
            $obj = $_POST['obj'];
        }
        if ($request->request->has('msg')) {
            $msg = $_POST['msg'];
        }
        if ($request->request->has('fichiers')) {
            $fichiers = $_POST['fichiers'];
        }

        if (str_contains($mail, '@ca-cb.fr') || str_contains($destEmail, '@ca-cb.fr')) {
            if (!empty($nom) && !empty($prenom) && !empty($mail) && filter_var($mail, FILTER_VALIDATE_EMAIL) && !empty($destEmail) && !empty($obj) && !empty($msg) && !empty($fichiers)) {
                $tokenStr = md5("iqnuefiuzeqieoufn".time()."iusviuqsiuvnqsuinevopi");
                $token = new ReplayToken();
                $token->setValue($tokenStr);
                $now = new \DateTime();
                $datetime = $now->modify('+10 day');
                $token->setValidUntil($datetime);
                $entityManager->persist($token);
                $entityManager->flush();

                $transfert = new Transfert();
                $transfert->setExpediteur(ucfirst($prenom) . " " . strtoupper($nom));
                $transfert->setExpediteurMail($mail);
                $transfert->setDateCreation(new \DateTime());
                $transfert->setFichiers($fichiers);
                $transfert->setStatut('uploaded');
                $transfert->setDestinataire($destEmail);
                $transfert->setObjet($obj);
                $transfert->setMessage($msg);
                $transfert->setToken($tokenStr);
                $entityManager->persist($transfert);
                $entityManager->flush();

                $data['transfertId'] = $transfert->getId();

                self::sendMailConfirm($mail, $destEmail);
            }
        }

        return new JsonResponse($data);
    }
}
