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
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

class FocusFilterLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ImageInterface $image, array $options = [])
    {

        if (!empty($options['filter'])) {
            $filter = constant('Imagine\Image\ImageInterface::FILTER_'.mb_strtoupper($options['filter']));
        }
        if (empty($filter)) {
            $filter = ImageInterface::FILTER_UNDEFINED;
        }

        $width  = isset($options['size'][0]) ? $options['size'][0] : null;
        $height = isset($options['size'][1]) ? $options['size'][1] : null;

        $size       = $image->getSize();
        $origWidth  = $size->getWidth();
        $origHeight = $size->getHeight();

        if (null === $width || null === $height) {
            if (null === $height) {
                $height = (int) (($width / $origWidth) * $origHeight);
            } elseif (null === $width) {
                $width = (int) (($height / $origHeight) * $origWidth);
            }
        }

        $width = $width > $origWidth ? $origWidth : $width;
        $height = $height > $origHeight ? $origHeight : $height;

        $x = $this->getFocusPos($origWidth, $width, $options["pos"][0]);
        $y = $this->getFocusPos($origHeight, $height, $options["pos"][1], true);

        if (($origWidth > $width || $origHeight > $height)
            || (!empty($options['allow_upscale']) && ($origWidth !== $width || $origHeight !== $height))
        ) {

            $filter = new Crop(new Point($x, $y), new Box($width, $height));
            //$filter = new Thumbnail(new Box($width, $height), $mode, $filter);
            $image  = $filter->apply($image);
        }

        return $image;
    }

    /**
     * @param $originalSize
     * @param $cropedSize
     * @param $pos
     * @param bool $negative
     * @return float|int
     */
    private function getFocusPos($originalSize, $cropedSize, $pos, $negative = false)
    {
        $percent = $negative ? ((-$pos + 1) / 2) * 100 : (($pos + 1) / 2) * 100 ;
        $focus   = ($originalSize * $percent) / 100;
        $nval    = $focus - ($cropedSize / 2);

        if ($nval < 0) {
            $val = 0;
        }
        elseif ($nval > ($originalSize - ($cropedSize / 2))) {
            $val = $originalSize - $cropedSize;
        }
        else {
            $val = $nval;
        }

        return $val;
    }
}
