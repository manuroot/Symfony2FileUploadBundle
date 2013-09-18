My Implementation Jquery File uploader(https://github.com/blueimp/jQuery-File-Upload) to Symfony2 Framework

Installation: 
1) Put 'Ikantam' folder into your 'src' Symfony folder
2) Enable bundle (add line new Ikantam\FilesBundle\IkantamFilesBundle() into your app/AppKernel.php);

Usage: 
1) You have service named 'Uploader' - this is upload handler;
2) Get service by calling $uploadHandler = $this->get('i_uploader');
3) Get upload data:  $data = $uploadHandler->upload();
4) Show response result: return new JsonResponse($data, 200);
This is content of your action in your Upload controller;

You can see example in Controller/UploadController file.