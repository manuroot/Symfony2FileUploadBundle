<?php
/*
 * jQuery File Upload Plugin PHP Class 6.9.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

namespace Ikantam\FilesBundle\Service\Uploader;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadHandler
{

    /**
     * Uploader options container
     * Loaded from config files
     *
     * @var array
     */
    protected $options;

    /**
     * PHP File Upload error message codes:
     * http://php.net/manual/en/features.file-upload.errors.php
     *
     * @var array
     */
    protected $error_messages;

    /**
     * Container object stored here
     *
     * @var
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->error_messages = $this->container->getParameter('upload_handler.error_messages');
        $this->options = $this->container->getParameter('upload_handler.options');

        $tempDirectory = $this->container->getParameter('upload_handler.temp_directory');
        $savePath = $this->container->getParameter('upload_handler.upload_directory').$tempDirectory;

        $router = $this->container->get('router');

        $this->options['upload_dir'] = dirname($_SERVER['SCRIPT_FILENAME']).$savePath;
        $this->options['upload_url'] = $this->getFullUrl().$savePath;
        $this->options['script_url'] = $router->generate('i_uploader_upload');


        $isInitialize = $this->container->getParameter('upload_handler.initialize');

        if ($isInitialize) {
            $this->initialize();
        }
    }

    /**
     * Check method and do upload
     *
     * @access public
     */
    public function upload()
    {
        switch ($this->getServerVar('REQUEST_METHOD')) {
            case 'OPTIONS':
                break;
            case 'HEAD':
            case 'GET':
                if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
                    $this->delete();
                } else {
                    $data = $this->get(false);
                }
                break;
            case 'DELETE':
                $this->delete();
                break;
            case 'POST':
                if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
                    $data = $this->delete(false);
                } else {
                    //upload new file to server(in special dir)
                    $temporaryUploadedData = $this->post(false);
                    $uploadManager = $this->container->get('i_uploaded_files_manager');
                    $data = $uploadManager->saveUploadedFiles($temporaryUploadedData);
                }
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }

        return $data;
    }

    protected function initialize()
    {
        switch ($this->getServerVar('REQUEST_METHOD')) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get();
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post();
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                $this->header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function getFullUrl()
    {
        $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
        return
            ($https ? 'https://' : 'http://').
            (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
            ($https && $_SERVER['SERVER_PORT'] === 443 ||
            $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
            substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }

    protected function getUserId()
    {
        @session_start();
        return session_id();
    }

    protected function getUserPath()
    {
        if ($this->options['user_dirs']) {
            return $this->getUserId().'/';
        }
        return '';
    }

    protected function getUploadPath($file_name = null, $version = null)
    {
        $file_name = $file_name ? $file_name : '';
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_dir = @$this->options['image_versions'][$version]['upload_dir'];
            if ($version_dir) {
                return $version_dir.$this->getUserPath().$file_name;
            }
            $version_path = $version.'/';
        }
        return $this->options['upload_dir'].$this->getUserPath()
            .$version_path.$file_name;
    }

    protected function getQuerySeparator($url)
    {
        return strpos($url, '?') === false ? '?' : '&';
    }

    protected function getDownloadUrl($file_name, $version = null, $direct = false)
    {
        if (!$direct && $this->options['download_via_php']) {
            $url = $this->options['script_url']
                .$this->getQuerySeparator($this->options['script_url'])
                .'file='.rawurlencode($file_name);
            if ($version) {
                $url .= '&version='.rawurlencode($version);
            }
            return $url.'&download=1';
        }
        if (empty($version)) {
            $version_path = '';
        } else {
            $version_url = @$this->options['image_versions'][$version]['upload_url'];
            if ($version_url) {
                return $version_url.$this->getUserPath().rawurlencode($file_name);
            }
            $version_path = rawurlencode($version).'/';
        }
        return $this->options['upload_url'].$this->getUserPath()
            .$version_path.rawurlencode($file_name);
    }

    protected function setAdditionalFileProperties($file)
    {
        $file->deleteUrl = $this->options['script_url']
            .$this->getQuerySeparator($this->options['script_url'])
            .$this->getSingularParamName()
            .'='.rawurlencode($file->name);
       // echo $this->options['script_url'];
        $file->deleteType = $this->options['delete_type'];
        if ($file->deleteType !== 'DELETE') {
            $file->deleteUrl .= '&_method=DELETE';
        }
        if ($this->options['access_control_allow_credentials']) {
            $file->deleteWithCredentials = true;
        }
    }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fixIntegerOverflow($size)
    {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    protected function getFileSize($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            clearstatcache(true, $file_path);
        }
        return $this->fixIntegerOverflow(filesize($file_path));

    }

    protected function isValidFileObject($file_name)
    {
        $file_path = $this->getUploadPath($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        }
        return false;
    }

    protected function getFileObject($file_name)
    {
        if ($this->isValidFileObject($file_name)) {
            $file = new \stdClass();
            $file->name = $file_name;
            $file->size = $this->getFileSize(
                $this->getUploadPath($file_name)
            );
            $file->url = $this->getDownloadUrl($file->name);
            foreach($this->options['image_versions'] as $version => $options) {
                if (!empty($version)) {
                    if (is_file($this->getUploadPath($file_name, $version))) {
                        $file->{$version.'Url'} = $this->getDownloadUrl(
                            $file->name,
                            $version
                        );
                    }
                }
            }
            $this->setAdditionalFileProperties($file);
            return $file;
        }
        return null;
    }

    protected function getFileObjects($iteration_method = 'getFileObject')
    {
        $upload_dir = $this->getUploadPath();
        if (!is_dir($upload_dir)) {
            return array();
        }
        return array_values(array_filter(array_map(
            array($this, $iteration_method),
            scandir($upload_dir)
        )));
    }

    protected function countFileObjects()
    {
        return count($this->getFileObjects('is_valid_file_object'));
    }

    protected function createScaledImage($file_name, $version, $options)
    {
        $file_path = $this->getUploadPath($file_name);
        if (!empty($version)) {
            $version_dir = $this->getUploadPath(null, $version);
            if (!is_dir($version_dir)) {
                mkdir($version_dir, $this->options['mkdir_mode'], true);
            }
            $new_file_path = $version_dir.'/'.$file_name;
        } else {
            $new_file_path = $file_path;
        }
        if (!function_exists('getimagesize')) {
            error_log('Function not found: getimagesize');
            return false;
        }
        list($img_width, $img_height) = @getimagesize($file_path);
        if (!$img_width || !$img_height) {
            return false;
        }
        $max_width = $options['max_width'];
        $max_height = $options['max_height'];
        $scale = min(
            $max_width / $img_width,
            $max_height / $img_height
        );
        if ($scale >= 1) {
            if ($file_path !== $new_file_path) {
                return copy($file_path, $new_file_path);
            }
            return true;
        }
        if (!function_exists('imagecreatetruecolor')) {
            error_log('Function not found: imagecreatetruecolor');
            return false;
        }
        if (empty($options['crop'])) {
            $new_width = $img_width * $scale;
            $new_height = $img_height * $scale;
            $dst_x = 0;
            $dst_y = 0;
            $new_img = imagecreatetruecolor($new_width, $new_height);
        } else {
            if (($img_width / $img_height) >= ($max_width / $max_height)) {
                $new_width = $img_width / ($img_height / $max_height);
                $new_height = $max_height;
            } else {
                $new_width = $max_width;
                $new_height = $img_height / ($img_width / $max_width);
            }
            $dst_x = 0 - ($new_width - $max_width) / 2;
            $dst_y = 0 - ($new_height - $max_height) / 2;
            $new_img = imagecreatetruecolor($max_width, $max_height);
        }
        switch (strtolower(substr(strrchr($file_name, '.'), 1))) {
            case 'jpg':
            case 'jpeg':
                $src_img = imagecreatefromjpeg($file_path);
                $write_image = 'imagejpeg';
                $image_quality = isset($options['jpeg_quality']) ?
                    $options['jpeg_quality'] : 75;
                break;
            case 'gif':
                imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
                $src_img = imagecreatefromgif($file_path);
                $write_image = 'imagegif';
                $image_quality = null;
                break;
            case 'png':
                imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
                imagealphablending($new_img, false);
                imagesavealpha($new_img, true);
                $src_img = imagecreatefrompng($file_path);
                $write_image = 'imagepng';
                $image_quality = isset($options['png_quality']) ?
                    $options['png_quality'] : 9;
                break;
            default:
                imagedestroy($new_img);
                return false;
        }
        $success = imagecopyresampled(
            $new_img,
            $src_img,
            $dst_x,
            $dst_y,
            0,
            0,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        ) && $write_image($new_img, $new_file_path, $image_quality);
        // Free up memory (imagedestroy does not delete files):
        imagedestroy($src_img);
        imagedestroy($new_img);
        return $success;
    }

    protected function getErrorMessage($error)
    {
        return array_key_exists($error, $this->error_messages) ?
            $this->error_messages[$error] : $error;
    }

    function getConfigBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $this->fixIntegerOverflow($val);
    }

    protected function validate($uploaded_file, $file, $error, $index)
    {
        if ($error) {
            $file->error = $this->getErrorMessage($error);
            return false;
        }
        $content_length = $this->fixIntegerOverflow(intval(
            $this->getServerVar('CONTENT_LENGTH')
        ));
        $post_max_size = $this->getConfigBytes(ini_get('post_max_size'));
        if ($post_max_size && ($content_length > $post_max_size)) {
            $file->error = $this->getErrorMessage('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            $file->error = $this->getErrorMessage('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->getFileSize($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
            $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
        ) {
            $file->error = $this->getErrorMessage('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file->error = $this->getErrorMessage('min_file_size');
            return false;
        }
        if (is_int($this->options['max_number_of_files']) && (
            $this->countFileObjects() >= $this->options['max_number_of_files'])
        ) {
            $file->error = $this->getErrorMessage('max_number_of_files');
            return false;
        }
        list($img_width, $img_height) = @getimagesize($uploaded_file);
        if (is_int($img_width)) {
            if ($this->options['max_width'] && $img_width > $this->options['max_width']) {
                $file->error = $this->getErrorMessage('max_width');
                return false;
            }
            if ($this->options['max_height'] && $img_height > $this->options['max_height']) {
                $file->error = $this->getErrorMessage('max_height');
                return false;
            }
            if ($this->options['min_width'] && $img_width < $this->options['min_width']) {
                $file->error = $this->getErrorMessage('min_width');
                return false;
            }
            if ($this->options['min_height'] && $img_height < $this->options['min_height']) {
                $file->error = $this->getErrorMessage('min_height');
                return false;
            }
        }
        return true;
    }

    protected function upcountNameCallback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' ('.$index.')'.$ext;
    }

    protected function upcountName($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcountNameCallback'),
            $name,
            1
        );
    }

    protected function getUniqueFilename(
        $name,
        $type = null,
        $index = null,
        $content_range = null
    ) {
        while (is_dir($this->getUploadPath($name))) {
            $name = $this->upcountName($name);
        }
        // Keep an existing filename if this is part of a chunked upload:
        $uploaded_bytes = $this->fixIntegerOverflow(intval($content_range[1]));
        while (is_file($this->getUploadPath($name))) {
            if ($uploaded_bytes === $this->getFileSize($this->getUploadPath($name))) {
                break;
            }
            $name = $this->upcountName($name);
        }
        return $name;
    }

    protected function trimFileName(
        $name,
        $type = null,
        $index = null,
        $content_range = null
    ) {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Use a timestamp for empty filenames:
        if (!$name) {
            $name = str_replace('.', '-', microtime(true));
        }
        // Add missing file extension for known image types:
        if (strpos($name, '.') === false &&
            preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $name .= '.'.$matches[1];
        }
        return $name;
    }

    protected function getFileName(
        $name,
        $type = null,
        $index = null,
        $content_range = null
    ) {
        return $this->getUniqueFilename(
            $this->trimFileName($name, $type, $index, $content_range),
            $type,
            $index,
            $content_range
        );
    }

    protected function handleFormData($file, $index)
    {
        // Handle form data, e.g. $_REQUEST['description'][$index]
    }

    protected function imageflip($image, $mode)
    {
        if (function_exists('imageflip')) {
            return imageflip($image, $mode);
        }
        $new_width = $src_width = imagesx($image);
        $new_height = $src_height = imagesy($image);
        $new_img = imagecreatetruecolor($new_width, $new_height);
        $src_x = 0;
        $src_y = 0;
        switch ($mode) {
            case '1': // flip on the horizontal axis
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                break;
            case '2': // flip on the vertical axis
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;
            case '3': // flip on both axes
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;
            default:
                return $image;
        }
        imagecopyresampled(
            $new_img,
            $image,
            0,
            0,
            $src_x,
            $src_y,
            $new_width,
            $new_height,
            $src_width,
            $src_height
        );
        // Free up memory (imagedestroy does not delete files):
        imagedestroy($image);
        return $new_img;
    }

    protected function orientImage($file_path)
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        $exif = @exif_read_data($file_path);
        if ($exif === false) {
            return false;
        }
        $orientation = intval(@$exif['Orientation']);
        if ($orientation < 2 || $orientation > 8) {
            return false;
        }
        $image = imagecreatefromjpeg($file_path);
        switch ($orientation) {
            case 2:
                $image = $this->imageflip(
                    $image,
                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                );
                break;
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 4:
                $image = $this->imageflip(
                    $image,
                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                );
                break;
            case 5:
                $image = $this->imageflip(
                    $image,
                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                );
                $image = imagerotate($image, 270, 0);
                break;
            case 6:
                $image = imagerotate($image, 270, 0);
                break;
            case 7:
                $image = $this->imageflip(
                    $image,
                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                );
                $image = imagerotate($image, 270, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
            default:
                return false;
        }
        $success = imagejpeg($image, $file_path);
        // Free up memory (imagedestroy does not delete files):
        imagedestroy($image);
        return $success;
    }

    protected function handleImageFile($file_path, $file)
    {
        if ($this->options['orient_image']) {
            $this->orientImage($file_path);
        }
        $failed_versions = array();
        foreach ($this->options['image_versions'] as $version => $options) {
            if ($this->createScaledImage($file->name, $version, $options)) {
                if (!empty($version)) {
                    $file->{$version.'Url'} = $this->getDownloadUrl(
                        $file->name,
                        $version
                    );
                } else {
                    $file->size = $this->getFileSize($file_path, true);
                }
            } else {
                $failed_versions[] = $version;
            }
        }
        switch (count($failed_versions)) {
            case 0:
                break;
            case 1:
                $file->error = 'Failed to create scaled version: '
                    .$failed_versions[0];
                break;
            default:
                $file->error = 'Failed to create scaled versions: ' . implode($failed_versions, ', ');

        }
    }

    protected function handleFileUpload(
        $uploaded_file,
        $name,
        $size,
        $type,
        $error,
        $index = null,
        $content_range = null
    ) {
        $file = new \stdClass();
        $file->name = $this->getFileName($name, $type, $index, $content_range);
        $file->size = $this->fixIntegerOverflow(intval($size));
        $file->type = $type;
        $file->path = $this->getUploadPath($file->name);
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handleFormData($file, $index);
            $upload_dir = $this->getUploadPath();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->getUploadPath($file->name);
            $append_file = $content_range && is_file($file_path) &&
                $file->size > $this->getFileSize($file_path);
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = $this->getFileSize($file_path, $append_file);
            if ($file_size === $file->size) {
                $file->url = $this->getDownloadUrl($file->name);
                list($img_width, $img_height) = @getimagesize($file_path);
                if (is_int($img_width) &&
                    preg_match($this->options['inline_file_types'], $file->name)) {
                    $this->handleImageFile($file_path, $file);
                }
            } else {
                $file->size = $file_size;
                if (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = 'abort';
                }
            }
            $this->setAdditionalFileProperties($file);
        }
        return $file;
    }

    protected function readfile($file_path)
    {
        $file_size = $this->getFileSize($file_path);
        $chunk_size = $this->options['readfile_chunk_size'];
        if ($chunk_size && $file_size > $chunk_size) {
            $handle = fopen($file_path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, $chunk_size);
                ob_flush();
                flush();
            }
            fclose($handle);
            return $file_size;
        }
        return readfile($file_path);
    }

    protected function body($str)
    {
        echo $str;
    }

    protected function header($str)
    {
        header($str);
    }

    protected function getServerVar($id)
    {
        return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
    }

    protected function generateResponse($content, $print_response = true)
    {
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ?
                stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                $this->header('Location: '.sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if ($this->getServerVar('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->header('Range: 0-'.($this->fixIntegerOverflow(intval($files[0]->size)) - 1));
                }
            }
            $this->body($json);
        }
        return $content;
    }

    protected function getVersionParam()
    {
        return isset($_GET['version']) ? basename(stripslashes($_GET['version'])) : null;
    }

    protected function getSingularParamName()
    {
        return substr($this->options['param_name'], 0, -1);
    }

    protected function getFileNameParam()
    {
        $name = $this->getSingularParamName();
        return isset($_GET[$name]) ? basename(stripslashes($_GET[$name])) : null;
    }

    protected function getFileNamesParams()
    {
        $params = isset($_GET[$this->options['param_name']]) ?
            $_GET[$this->options['param_name']] : array();
        foreach ($params as $key => $value) {
            $params[$key] = basename(stripslashes($value));
        }
        return $params;
    }

    protected function getFileType($file_path)
    {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
    }

    protected function download()
    {
        switch ($this->options['download_via_php']) {
            case 1:
                $redirect_header = null;
                break;
            case 2:
                $redirect_header = 'X-Sendfile';
                break;
            case 3:
                $redirect_header = 'X-Accel-Redirect';
                break;
            default:
                return $this->header('HTTP/1.1 403 Forbidden');
        }
        $file_name = $this->getFileNameParam();

        if (!$this->isValidFileObject($file_name)) {
            return $this->header('HTTP/1.1 404 Not Found');
        }

        if ($redirect_header) {

            return $this->header($redirect_header.': '.$this->getDownloadUrl(
                    $file_name,
                    $this->get_version_param(),
                    true
                )
            );
        }

        $file_path = $this->getUploadPath($file_name, $this->getVersionParam());
        // Prevent browsers from MIME-sniffing the content-type:
        $this->header('X-Content-Type-Options: nosniff');
        if (!preg_match($this->options['inline_file_types'], $file_name)) {
            $this->header('Content-Type: application/octet-stream');
            $this->header('Content-Disposition: attachment; filename="'.$file_name.'"');
        } else {
            $this->header('Content-Type: '.$this->getFileType($file_path));
            $this->header('Content-Disposition: inline; filename="'.$file_name.'"');
        }
        $this->header('Content-Length: '.$this->getFileSize($file_path));
        $this->header('Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($file_path)));
        $this->readfile($file_path);
    }

    protected function sendContentTypeHeader()
    {
        $this->header('Vary: Accept');
        if (strpos($this->getServerVar('HTTP_ACCEPT'), 'application/json') !== false) {
            $this->header('Content-type: application/json');
        } else {
            $this->header('Content-type: text/plain');
        }
    }

    protected function sendAccessControlHeaders()
    {
        $this->header('Access-Control-Allow-Origin: '.$this->options['access_control_allow_origin']);
        $this->header(
            'Access-Control-Allow-Credentials: '
            .($this->options['access_control_allow_credentials'] ? 'true' : 'false')
        );
        $this->header(
            'Access-Control-Allow-Methods: '
            .implode(', ', $this->options['access_control_allow_methods'])
        );
        $this->header(
            'Access-Control-Allow-Headers: '
            .implode(', ', $this->options['access_control_allow_headers'])
        );
    }

    public function head()
    {
        $this->header('Pragma: no-cache');
        $this->header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        $this->header('X-Content-Type-Options: nosniff');
        if ($this->options['access_control_allow_origin']) {
            $this->sendAccessControlHeaders();
        }
        $this->sendContentTypeHeader();
    }

    public function get($print_response = true)
    {
        if ($print_response && isset($_GET['download'])) {
            return $this->download();
        }
        $file_name = $this->getFileNameParam();
        if ($file_name) {
            $response = array(
                $this->getSingularParamName() => $this->getFileObject($file_name)
            );
        } else {
            $response = array(
                $this->options['param_name'] => $this->getFileObjects()
            );
        }
        return $this->generateResponse($response, $print_response);
    }

    public function post($print_response = true)
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }
        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : null;
        // Parse the Content-Disposition header, if available:
        $file_name = $this->getServerVar('HTTP_CONTENT_DISPOSITION') ?
            rawurldecode(
                preg_replace(
                    '/(^[^"]+")|("$)/',
                    '',
                    $this->getServerVar('HTTP_CONTENT_DISPOSITION')
                )
            ) : null;

        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = $this->getServerVar('HTTP_CONTENT_RANGE') ?
            preg_split('/[^0-9]+/', $this->getServerVar('HTTP_CONTENT_RANGE')) : null;
        $size =  $content_range ? $content_range[3] : null;
        $files = array();
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $files[] = $this->handleFileUpload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->handleFileUpload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ?
                    $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ?
                    $upload['size'] : $this->getServerVar('CONTENT_LENGTH')),
                isset($upload['type']) ?
                $upload['type'] : $this->getServerVar('CONTENT_TYPE'),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }
        return $this->generateResponse(
            array($this->options['param_name'] => $files),
            $print_response
        );
    }

    public function delete($print_response = true)
    {
        $file_names = $this->getFileNamesParams();
        if (empty($file_names)) {
            $file_names = array($this->getFileNameParam());
        }
        $response = array();
        foreach ($file_names as $file_name) {
            $file_path = $this->getUploadPath($file_name);
            $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
            if ($success) {
                foreach ($this->options['image_versions'] as $version => $options) {
                    if (!empty($version)) {
                        $file = $this->getUploadPath($file_name, $version);
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                }
            }
            $response[$file_name] = $success;
        }
        return $this->generateResponse($response, $print_response);
    }

}
