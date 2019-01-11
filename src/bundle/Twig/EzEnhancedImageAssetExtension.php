<?php
/**
 * @copyright Novactive
 * Date: 11/01/2019
 */

namespace AppBundle\Twig;

use eZ\Publish\API\Repository\Exceptions\InvalidVariationException;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException;
use eZ\Publish\SPI\Variation\VariationHandler;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class EzEnhancedImageAssetExtension.
 *
 * @package EzEnhancedImageAssetBundle\Twig
 */
class EzEnhancedImageAssetExtension extends Twig_Extension
{
    /** @var VariationHandler */
    protected $imageVariationService;

    /**
     * EzEnhancedImageAssetExtension constructor.
     *
     * @param VariationHandler $imageVariationService
     */
    public function __construct(VariationHandler $imageVariationService)
    {
        $this->imageVariationService    = $imageVariationService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $functions = parent::getFunctions();

        return array_merge(
            $functions,
            [
                new Twig_SimpleFunction(
                    'ez_image_focus_alias',
                    [$this, 'getImageFocusVariation'],
                    ['is_safe' => ['html']]
                ),
            ]
        );
    }

    /**
     * Returns the image variation object for $field/$versionInfo.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field       $field
     * @param \eZ\Publish\API\Repository\Values\Content\VersionInfo $versionInfo
     * @param string                                                $variationName
     * @param array                                                 $focusPoint
     *
     * @return \eZ\Publish\SPI\Variation\Values\Variation|null
     */
    public function getImageFocusVariation(Field $field, VersionInfo $versionInfo, $variationName, array $focusPoint = [])
    {
        try {
            return $this->imageVariationService->getVariation($field, $versionInfo, $variationName, $focusPoint);
        } catch (InvalidVariationException $e) {
            if (isset($this->logger)) {
                $this->logger->error("Couldn't get variation '{$variationName}' for image with id {$field->value->id}");
            }
        } catch (SourceImageNotFoundException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because source image can't be found"
                );
            }
        } catch (InvalidArgumentException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because an image could not be created from the given input"
                );
            }
        }
    }
}
