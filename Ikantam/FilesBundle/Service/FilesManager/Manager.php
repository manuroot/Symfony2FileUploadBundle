<?php
namespace Ikantam\FilesBundle\Service\FilesManager;

use Ikantam\FilesBundle\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Manager
{

    private $container;
    private $em;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')
            ->getManager();
    }

    public function saveUploadedFiles($data)
    {
        if (isset($data['files'])) {
            foreach ($data['files'] as $_id => $_file) {

                $file = new File();
                $file->setName($_file->name);
                $file->setSize($_file->size);
                $file->setType($_file->type);
                $file->setPath($_file->path);
                $file->setUrl($_file->url);
                $file->setDeleteType($_file->deleteType);
                $file->setDeleteUrl($_file->deleteUrl);
                $this->em->persist($file);
                $this->em->flush();

                //now we need to move file from temporary directory
                $savePath = $this->container->getParameter('upload_handler.upload_directory').$file->getId();
                $destinationDir = dirname($_SERVER['SCRIPT_FILENAME']).$savePath.'/';
                $destination = $destinationDir.$file->getName();

                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0775, true);
                }

                if (!copy($file->getPath(), $destination)) {
                    $this->em->remove($file); //cant move file - remove it from database
                    $this->em->flush();
                    unset($data['files'][$_id]);
                } else {
                    unlink($file->getPath());
                    //update file path
                    $file->setPath($destination);
                    $this->em->persist($file);
                    $this->em->flush();
                    $data['files'][$_id]->path = $file->getPath();
                }
            }
        }
        return $data;
    }
}
