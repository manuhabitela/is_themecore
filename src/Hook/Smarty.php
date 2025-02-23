<?php

namespace Oksydan\Module\IsThemeCore\Hook;

class Smarty extends AbstractHook
{
    const HOOK_LIST = [
      	'actionDispatcherBefore',
    ];

    public function hookActionDispatcherBefore() : void
    {
        $this->context->smarty->registerPlugin('function', 'imageUrl', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'imageUrl']);
        $this->context->smarty->registerPlugin('function', 'imageAttrs', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'imageAttrs']);
        $this->context->smarty->registerPlugin('block', 'replaceImageUrls', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'replaceImageUrls']);
        $this->context->smarty->registerPlugin('function', 'generateImagesSources', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'generateImagesSources']);
        $this->context->smarty->registerPlugin('function', 'generateImageSvgPlaceholder', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'generateImageSvgPlaceholder']);
        $this->context->smarty->registerPlugin('function', 'appendParamToUrl', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'appendParamToUrl']);
        $this->context->smarty->registerPlugin('block', 'images_block', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'imagesBlock']);
        $this->context->smarty->registerPlugin('block', 'cms_images_block', ['Oksydan\Module\IsThemeCore\Core\Smarty\SmartyHelperFunctions', 'cmsImagesBlock']);
    }
}
