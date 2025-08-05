<?php

namespace Oksydan\Module\IsThemeCore\Core\Smarty;

use Oksydan\Module\IsThemeCore\Form\Settings\WebpConfiguration;
use Oksydan\Module\IsThemeCore\Core\Webp\WebpPictureGenerator;
use Symfony\Component\DomCrawler\Crawler;

class SmartyHelperFunctions {

    /**
     * get src/width/height attributes for a given image object+size
     *
     * depending on theme settings, image url may be a cloudflare one
     *
     * @param array $params array of:
     *  image: image info object (usually from a product)
     *  size: string (eg: 'home_default')
     *  fallback: fallback image info array (url + width + height)
     * @return string string of attributes to print inside a <img> tag
     */
    public static function imageAttrs($params) {
        $image = empty($params['image']) && isset($params['fallback']) ? $params['fallback'] : $params['image'];
        $size = $params['size'];
        $attributes = [];
        $attributesToPrint = [];

        if (empty($params['image']) && isset($params['fallback'])) {
            $attributes = [
                'src' => $params['fallback']['url'],
                'width' => $params['fallback']['width'],
                'height' => $params['fallback']['height'],
            ];
        }

        // if we dont use the fallback, use the image
        if (empty($attributes['src'])) {
            $src = self::imageUrl($params);
            $attributes = [
                'src' => $src,
                'width' => $image['bySize'][$size]['width'],
                'height' => $image['bySize'][$size]['height'],
            ];
        }

        foreach ($attributes as $attr => $value) {
            $attributesToPrint[] = $attr . '="' . $value . '"';
        }
        return implode($attributesToPrint, PHP_EOL);
    }

    /**
     * get image url for given image object + size
     *
     * depending on theme settings, image url may be a cloudflare one
     *
     * @param array $params array of:
     *  image: image info object (usually from a product)
     *  size: string (eg: 'home_default')
     *
     * @return string url of the image
     */
    public static function imageUrl($params) {
        $image = $params['image'];
        $size = $params['size'];

        $src = $image['bySize'][$size]['url'];
        // use cloudflare if enabled, and send to cloudflare the original, not resized image,
        // we let them do the resizing, they do it better

        if (\Configuration::get('THEMECORE_USE_CLOUDFLARE_IMAGES')) {
            $originalSrc = str_replace('-' . $size, '', $src);
            $cloudflareZone = \Configuration::get('THEMECORE_CLOUDFLARE_ZONE');

            $srcToSend = $originalSrc;

            /**
             * we can enable an option to send an already resized image to cloudflare
             * instead of the original one. Because uploaded images in the admin can be big, like 10Mb big.
             * So we have a 'cloudflare_default' image thumbnail that is generated that
             * resizes stuff at 3000x3000 max.
             */
            $useResizedImage = \Configuration::get('THEMECORE_CLOUDFLARE_RESIZED_IMAGES');
            if ($useResizedImage && !empty($image['bySize']['cloudflare_default'])) {
                // compare filesize, as the resized one can be heavier sometimes…
                $originalPath = substr($originalSrc, strpos($originalSrc, '/img/') + 5);
                $forCloudflareSrc = $image['bySize']['cloudflare_default']['url'];
                $forCloudflarePath = substr($forCloudflareSrc, strpos($forCloudflareSrc, '/img/') + 5);

                if (filesize(_PS_IMG_DIR_ . $originalPath) > filesize(_PS_IMG_DIR_ . $forCloudflarePath)) {
                    $srcToSend = $forCloudflareSrc;
                }
            }

            $isImageAbsolutePath = substr($srcToSend, 0, 1) === "/";
            $useWorker = !$isImageAbsolutePath && strpos($srcToSend, $cloudflareZone) !== 0;
            if ($useWorker) {
                $src = $cloudflareZone
                    . '/cdn-img-worker/'
                    . '?width=' . $image['bySize'][$size]['width']
                    . (!empty($image['bySize'][$size]['height']) ? '&height=' . $image['bySize'][$size]['height'] : '')
                    . '&image=' . $srcToSend;
            } else {
                $src = $cloudflareZone
                    . '/cdn-cgi/image/format=auto,fit=scale-down'
                    . ',width=' . $image['bySize'][$size]['width']
                    . (!empty($image['bySize'][$size]['height']) ? ',height=' . $image['bySize'][$size]['height'] : '')
                    . ($isImageAbsolutePath ? $srcToSend : '/' . $srcToSend);
            }
        }

        return $src;
    }

    /**
     * block to replace img urls with (maybe) cloudflare ones in given html content
     *
     * @param array $params width: max width of images
     * @param null|string $content html content in smarty block. Can be null depending on block calls
     * @return string html content with maybe replaced img urls
     */
    public static function replaceImageUrls($params, $content) {
        if (is_null($content) || empty(trim($content))) {
            return "";
        }
        if (empty($params['width']) || !\Configuration::get('THEMECORE_USE_CLOUDFLARE_IMAGES')) {
            return $content;
        }
        $maxWidth = $params['width'];
        $dom = new Crawler($content);
        $cloudflareZone = \Configuration::get('THEMECORE_CLOUDFLARE_ZONE');
        $currentWebsite = \Context::getContext()->shop->getBaseURL(true);
        $imgs = $dom->filter('img[src^="/"], img[src^="' . $currentWebsite . '"], img[src^="' . $cloudflareZone . '"]');
        foreach ($imgs as $node) {
            $width = $node->getAttribute('width');
            $height = $node->getAttribute('height');
            $imgUrlParams = [
                'image' => [
                    'bySize' => [
                        'auto' => [
                            'url' => $node->getAttribute('src'),
                            'width' => null,
                            'height' => null,
                        ]
                    ]
                ],
                'size' => 'auto',
            ];
            if (empty($width) || strpos($width, '%') !== false) {
                $node->setAttribute('width', $maxWidth);
                $node->removeAttribute('height');
                $imgUrlParams['image']['bySize']['auto']['width'] = $maxWidth;
            }
            if (!empty($width) && !empty($height) && is_numeric($width) && is_numeric($height)) {
                $ratio = $width / $height;
                $newWidth = min($width, $maxWidth);
                $newHeight = intval($newWidth / $ratio);
                $node->setAttribute('width', $newWidth);
                $node->setAttribute('height', $newHeight);
                $imgUrlParams['image']['bySize']['auto']['width'] = $newWidth;
                $imgUrlParams['image']['bySize']['auto']['height'] = $newHeight;
            }
            $node->setAttribute('src', self::imageUrl($imgUrlParams));
        }
        try {
            return $dom->filter('body')->html();
        } catch (\InvalidArgumentException $e) {
            return $content;
        }

    }

    /**
     * manu's note: not used anymore, kept for reference but all webp related stuff in the plugin is disabled
     *
     * @deprecated
     */
    public static function generateImagesSources($params) {
      $image = $params['image'];
      $size = $params['size'];
      $lazyLoad = isset($params['lazyload']) ? $params['lazyload'] : true;
      $attributes = [];
      $highDpiImagesEnabled = (bool) \Configuration::get('PS_HIGHT_DPI');

      $img = $image['bySize'][$size]['url'];

      if ($highDpiImagesEnabled) {
        $size2x = $size . '2x';
        $img2x = str_replace($size, $size2x, $img);
        $attributes['srcset'] = "$img, $img2x 2x";
      } else {
        $attributes['src'] = $img;
      }

      if ($lazyLoad) {
        $attributes['loading'] = 'lazy';
      }

      $attributesToPrint = [];

      foreach ($attributes as $attr => $value) {
        $attributesToPrint[] = $attr . '="' . $value . '"';
      }

      return implode($attributesToPrint, PHP_EOL);
    }

    /**
     * manu's note: not used anymore, kept for reference but all webp related stuff in the plugin is disabled
     *
     * @deprecated
     */
    public static function generateImageSvgPlaceholder($params) {
      $width = $params['width'];
      $height = $params['height'];

      return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height' viewBox='0 0 1 1'%3E%3C/svg%3E";
    }

    public static function appendParamToUrl($params) {
      list(
        'url' => $url,
        'key' => $key,
        'value' => $value
      ) = $params;

      $query = parse_url($url, PHP_URL_QUERY);

      if ($query) {
        parse_str($query, $queryParams);
        $queryParams[$key] = $value;
        $url = str_replace("?$query", '?' . http_build_query($queryParams), $url);
      } else {
        $url .= '?' . urlencode($key) . '=' . urlencode($value);
      }

      return $url;
    }

    /**
     * manu's note: not used anymore, kept for reference but all webp related stuff in the plugin is disabled
     *
     * @deprecated
     */
    public static function imagesBlock($params, $content, $smarty)
    {
      $webpEnabled = isset($params['webpEnabled']) ? $params['webpEnabled'] : \Configuration::get(WebpConfiguration::THEMECORE_WEBP_ENABLED);

      if ($webpEnabled && !empty($content)) {
        $pictureGenerator = new WebpPictureGenerator($content);

        $pictureGenerator
          ->loadContent()
          ->generatePictureTags();

        return $pictureGenerator->getContent();
      }

      return $content;
    }

    /**
     * manu's note: not used anymore, kept for reference but all webp related stuff in the plugin is disabled
     *
     * @deprecated
     */
    public static function cmsImagesBlock($params, $content, $smarty)
    {
      $doc = new \DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8">' . $content);

      $images = $doc->getElementsByTagName('img');

      $domains = \Tools::getDomains();
      $medias = [
        \Configuration::get('PS_MEDIA_SERVER_1'),
        \Configuration::get('PS_MEDIA_SERVER_2'),
        \Configuration::get('PS_MEDIA_SERVER_3'),
      ];

      $internalUrls = [];

      foreach ($domains as $domain => $options) {
        $internalUrls[] = $domain;
      }

      foreach ($medias as $media) {
        if ($media) {
          $internalUrls[] = $media;
        }
      }

      foreach ($images as $image) {
        $newImg = $doc->createElement('img');
        $src = urldecode($image->attributes->getNamedItem('src')->nodeValue);

        if (!preg_match('/' . implode('|', $internalUrls) . '/i', $src)) {
          $newImg->setAttribute('data-external-url', '');
        }

        foreach ($image->attributes as $attribute) {
          $newImg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        $image->parentNode->replaceChild($newImg, $image);
      }

      $content = $doc->saveHTML();
      $content = str_replace('<?xml encoding="utf-8" ?>', '', $content);

      $webpEnabled = isset($params['webpEnabled']) ? $params['webpEnabled'] : \Configuration::get(WebpConfiguration::THEMECORE_WEBP_ENABLED);

      if ($webpEnabled && !empty($content)) {
        $pictureGenerator = new WebpPictureGenerator($content);

        $content = $doc->saveHTML();
        $content = str_replace('<meta http-equiv="Content-Type" content="charset=utf-8">', '', $content);

        return $pictureGenerator->getContent();
      }

      return $content;
    }
}
