<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\MailTemplate\Transformation;

use Pelago\Emogrifier;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Symfony\Component\DomCrawler\Crawler;
use DOMElement;
use DOMAttr;

class CSSInlineTransformation extends AbstractMailTemplateTransformation
{
    /**
     * {@inheritdoc}
     */
    public function apply($templateContent, array $templateVariables)
    {
        /**
         * For unknown reason Emogrifier modifies href attribute with variables written
         * like this {shop_url} so we temporarily change them to @shop_url@
         */
        $templateContent = preg_replace('/\{(\w+)\}/', '@\1@', $templateContent);

        $cssContent = $this->getCssContent($templateContent);
        $inliner = new Emogrifier($templateContent, $cssContent);
        $templateContent = $inliner->emogrify();

        $converter = new CssToAttributeConverter($templateContent);
        $templateContent = $converter->convertCssToVisualAttributes()->render();

        return preg_replace('/@(\w+)@/', '{\1}', $templateContent);
    }

    /**
     * @param $templateContent
     *
     * @return string
     */
    private function getCssContent($templateContent)
    {
        $crawler = new Crawler($templateContent);
        $cssTags = $crawler->filter('link[type="text/css"]');
        $cssUrls = [];
        /** @var DOMElement $cssTag */
        foreach ($cssTags as $cssTag) {
            /** @var DOMAttr $hrefAttr */
            if ($hrefAttr = $cssTag->attributes->getNamedItem('href')) {
                $cssUrls[] = $hrefAttr->nodeValue;
            }
        }
        $cssContents = '';
        foreach ($cssUrls as $cssUrl) {
            $cssContent = @file_get_contents($cssUrl);
            if (!empty($cssContent)) {
                $cssContents .= $cssContent;
            }
        }

        return $cssContents;
    }
}
