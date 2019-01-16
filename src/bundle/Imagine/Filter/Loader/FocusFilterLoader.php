<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Novactive\EzEnhancedImageAssetBundle\Imagine\Filter\Loader;

use Imagine\Filter\Basic\Crop;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use Novactive\EzEnhancedImageAssetBundle\Service\FocusPointService;

class FocusFilterLoader implements LoaderInterface
{
    /** @var FocusPointService */
    protected $focusPointService;

    /**
     * @param FocusPointService $focusPointService
     * @required
     */
    public function setFocusPointService(FocusPointService $focusPointService): void
    {
        $this->focusPointService = $focusPointService;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ImageInterface $image, array $options = [])
    {
        $originalBox       = $image->getSize();
        $cropBox           = $this->getCropBox($options, $originalBox);
        $xCropStart        = $this->getFocusPos($originalBox->getWidth(), $cropBox->getWidth(), $options['pos'][0]);
        $yCropStart        = $this->getFocusPos($originalBox->getHeight(), $cropBox->getHeight(), $options['pos'][1], true);

        if (($originalBox->getWidth() > $cropBox->getWidth() || $originalBox->getHeight() > $cropBox->getHeight())
            || (!empty($options['allow_upscale']) && ($originalBox->getWidth() !== $cropBox->getWidth() || $originalBox->getHeight() !== $cropBox->getHeight()))
        ) {
            $filter = new Crop(new Point($xCropStart, $yCropStart), $cropBox);
            $image  = $filter->apply($image);
        }

        return $image;
    }

    /**
     * @param $options
     * @param BoxInterface $originalBox
     *
     * @return Box
     */
    protected function getCropBox($options, BoxInterface $originalBox)
    {
        $width  = isset($options['size'][0]) ? $options['size'][0] : null;
        $height = isset($options['size'][1]) ? $options['size'][1] : null;

        $origWidth  = $originalBox->getWidth();
        $origHeight = $originalBox->getHeight();

        if (null === $width || null === $height) {
            if (null === $height) {
                $height = (int) (($width / $origWidth) * $origHeight);
            } elseif (null === $width) {
                $width = (int) (($height / $origHeight) * $origWidth);
            }
        }

        $width  = $width > $origWidth ? $origWidth : $width;
        $height = $height > $origHeight ? $origHeight : $height;

        return new Box($width, $height);
    }

    /**
     * @param $originalSize
     * @param $croppedSize
     * @param $pos
     * @param bool $negative
     *
     * @return float|int
     */
    public function getFocusPos($originalSize, $croppedSize, $pos, $negative = false)
    {
        $focusPointVal  = $this->focusPointService->getPixelFocusPointVal($originalSize, $pos, $negative);
        $cropPointVal   = $this->focusPointService->getPosCropVal($focusPointVal, $croppedSize);

        if ($cropPointVal <= 0) {
            $val = 0;
        } elseif ($cropPointVal > ($originalSize - $croppedSize)) {
            $val = $originalSize - $croppedSize;
        } else {
            $val = $cropPointVal;
        }

        return $val;
    }
}
