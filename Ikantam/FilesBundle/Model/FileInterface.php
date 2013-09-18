<?php

namespace Ikantam\FilesBundle\Model;

/**
 * File Model
 */
interface FileInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set size
     *
     * @param integer $size
     * @return self
     */
    public function setSize($size);

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize();

    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set url
     *
     * @param string $url
     * @return self
     */
    public function setUrl($url);

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set path
     *
     * @param string $path
     * @return self
     */
    public function setPath($path);

    /**
     * Get path
     *
     * @return string
     */
    public function getPath();

    /**
     * Set deleteType
     *
     * @param string $deleteType
     * @return self
     */
    public function setDeleteType($deleteType);

    /**
     * Get deleteType
     *
     * @return string
     */
    public function getDeleteType();

    /**
     * Set deleteUrl
     *
     * @param string $deleteUrl
     * @return self
     */
    public function setDeleteUrl($deleteUrl);

    /**
     * Get deleteUrl
     *
     * @return string
     */
    public function getDeleteUrl();
}
