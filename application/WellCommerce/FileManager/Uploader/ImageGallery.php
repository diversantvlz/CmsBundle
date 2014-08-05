<?php
/*
 * WellCommerce Open-Source E-Commerce Platform
 *
 * This file is part of the WellCommerce package.
 *
 * (c) Adam Piotrowski <adam@wellcommerce.org>
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */
namespace WellCommerce\FileManager\Uploader;

use Intervention\Image\Image;
use WellCommerce\Core\AbstractComponent;

/**
 * Class ImageGallery
 *
 * @package WellCommerce\File\Uploader
 * @author  Adam Piotrowski <adam@wellcommerce.org>
 */
class ImageGallery extends AbstractComponent
{
    protected $files;

    protected $canvasBackgroundColour = 'ffffff';

    protected $cachePath;

    protected $originalPath;

    protected $host;

    protected $cacheUrl;

    protected $originalUrl;

    protected $rootpath;

    /**
     * Sets base paths for original and cached images
     *
     * @param array $paths
     *
     * @throws \InvalidArgumentException
     */
    public function setPaths(array $paths)
    {
        if (!isset($paths['original'])) {
            throw new \InvalidArgumentException('You must provide path for original images.');
        }
        if (!isset($paths['cache'])) {
            throw new \InvalidArgumentException('You must provide path for cached images.');
        }

        $this->rootpath     = $this->container->getParameter('application.root_path');
        $this->cachePath    = sprintf('%s/%s', $this->rootpath, $paths['cache']);
        $this->originalPath = sprintf('%s/%s', $this->rootpath, $paths['original']);
        $this->host         = $this->getRequest()->getSchemeAndHttpHost();
        $this->cacheUrl     = sprintf('%s/%s', $this->host, $paths['cache']);
        $this->originalUrl  = sprintf('%s/%s', $this->host, $paths['original']);

    }

    /**
     * Fetches all files from repository or cache
     */
    public function setFiles()
    {
        if ($this->getCache()->hasItem('files')) {
            $this->files = $this->getCache()->getItem('files');
        } else {
            $files = $this->get('file.repository')->all()->toArray();
            foreach ($files as $file) {
                $this->files[$file['id']] = $file;
            }
            $this->getCache()->addItem('files', $this->files);
        }
    }

    /**
     * Returns original image path
     *
     * @param $fileName
     *
     * @return string
     */
    private function getImageOriginalPath($fileName)
    {
        return sprintf('%s/%s', $this->originalPath, $fileName);
    }

    /**
     * Returns original image url
     *
     * @param $fileName
     *
     * @return string
     */
    private function getImageOriginalUrl($fileName)
    {
        return sprintf('%s/%s', $this->originalUrl, $fileName);
    }

    /**
     * Returns cached image path
     *
     * @param $fileName
     * @param $width
     * @param $height
     *
     * @return string
     */
    private function getImageCachePath($fileName, $width, $height)
    {
        return sprintf('%s/%s_%s/%s', $this->cachePath, $width, $height, $fileName);
    }

    /**
     * Returns cached image url
     *
     * @param $fileName
     *
     * @return string
     */
    private function getImageCacheUrl($fileName, $width, $height)
    {
        return sprintf('%s/%s_%s/%s', $this->cacheUrl, $width, $height, $fileName);
    }

    /**
     * Returns file name with extension
     *
     * @param $file
     *
     * @return string
     */
    private function getImageFileName($file)
    {
        return sprintf('%s.%s', $file['id'], $file['extension']);
    }

    /**
     * Returns image url
     *
     * @param      $id
     * @param null $width
     * @param null $height
     *
     * @return string
     */
    public function getImageUrl($id, $width = null, $height = null)
    {
        if ((int)$id == 0) {
            return '';
        }
        $file         = $this->files[$id];
        $fileName     = $this->getImageFileName($file);
        $originalPath = $this->getImageOriginalPath($fileName);
        $cachePath    = $this->getImageCachePath($fileName, $width, $height);

        // return original image url
        if (0 == (int)$width || 0 == (int)$height) {
            return $this->getImageOriginalUrl($fileName);
        } else {

            // resize image
            if (!$this->getFilesystem()->exists($cachePath)) {
                $img = Image::make($originalPath);
                $img->resize($width, $height, true);
                $img->resizeCanvas($width, $height, 'center', false, $this->canvasBackgroundColour);
                $this->getFilesystem()->dumpFile($cachePath, $img);
            }

            // return cached image url
            return $this->getImageCacheUrl($fileName, $width, $height);
        }
    }
}