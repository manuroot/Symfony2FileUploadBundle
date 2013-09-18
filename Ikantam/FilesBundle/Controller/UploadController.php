<?php

namespace Ikantam\FilesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ikantam\FilesBundle\Entity\File;

/**
 * Class UploadController
 * @package Ikantam\FilesBundle\Controller
 */
class UploadController extends Controller
{
    /**
     * Handle file uploading
     * Move file to server
     * Add new File to database
     *
     * @access public
     * @return JsonResponse
     */
    public function indexAction()
    {
        $uploadHandler = $this->get('i_uploader');
        $data = $uploadHandler->upload();

        return new JsonResponse($data, 200);
    }

    /**
     * Load 'basic' page of blueimp library
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testAction()
    {
        return $this->render('IkantamFilesBundle:Upload:test.html.twig');
    }

    /**
     * Load 'basic+' page of blueimp library
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testPlusAction()
    {
        return $this->render('IkantamFilesBundle:Upload:test_plus.html.twig');
    }

    /**
     * Load 'basic+UI' page of blueimp Library
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testPlusUiAction()
    {
        return $this->render('IkantamFilesBundle:Upload:test_plus_ui.html.twig');
    }
}
