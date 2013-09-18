My Implementation Jquery File uploader(https://github.com/blueimp/jQuery-File-Upload) to Symfony2 Framework

<h1>Installation:</h1>  <br>
1) Put 'Ikantam' folder into your 'src' Symfony folder <br>
2) Enable bundle (add line new Ikantam\FilesBundle\IkantamFilesBundle() into your app/AppKernel.php); <br>

 <br> <br>
<h1>Usage: </h1>
1) You have service named 'Uploader' - this is upload handler; <br>
2) Get service by calling $uploadHandler = $this->get('i_uploader'); <br>
3) Get upload data:  $data = $uploadHandler->upload(); <br>
4) Show response result: return new JsonResponse($data, 200); <br>
This is content of your action in your Upload controller; <br> <br>

You can see example in Controller/UploadController file.