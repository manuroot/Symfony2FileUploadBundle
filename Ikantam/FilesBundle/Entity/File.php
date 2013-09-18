<?php

namespace Ikantam\FilesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ikantam\FilesBundle\Model\FileInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * File
 *
 * @ORM\MappedSuperclass
 * @ORM\Table(name="files")
 */
class File implements FileInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Name should not be empty")
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer", length=11)
     * @Assert\NotBlank(message="Size should not be empty")
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\NotBlank(message="Type should not be empty")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     * @Assert\NotBlank(message="Url should not be empty")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     * @Assert\NotBlank(message="Path should not be empty")
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="delete_type", type="string", length=255)
     * @Assert\NotBlank(message="Delete Type should not be empty")
     */
    private $deleteType;

    /**
     * @var string
     *
     * @ORM\Column(name="delete_url", type="string", length=255)
     * @Assert\NotBlank(message="Delete Url should not be empty")
     */
    private $deleteUrl;

    public function __construct()
    {
        //for feature changes
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return File
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return File
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return File
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return File
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return File
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set deleteType
     *
     * @param string $deleteType
     * @return File
     */
    public function setDeleteType($deleteType)
    {
        $this->deleteType = $deleteType;

        return $this;
    }

    /**
     * Get deleteType
     *
     * @return string
     */
    public function getDeleteType()
    {
        return $this->deleteType;
    }

    /**
     * Set deleteUrl
     *
     * @param string $deleteUrl
     * @return File
     */
    public function setDeleteUrl($deleteUrl)
    {
        $this->deleteUrl = $deleteUrl;

        return $this;
    }

    /**
     * Get deleteUrl
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->deleteUrl;
    }
}
