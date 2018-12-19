<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\ControllerPlugin;

use CMTV\CriteriaBuilder\Constants as C;
use XF\ControllerPlugin\AbstractPlugin;

class ListEmpty extends AbstractPlugin
{
    const EMOJI_CODES = [
        '😨',
        '😦',
        '😢',
        '🤯',
        '😬'
    ];

    public function actionListEmpty(string $titlePhraseName, string $addPhraseName, string $emptyPhraseName, string $addUrl, ?string $emoji = null)
    {
        $viewParams = [
            'emoji' => $emoji ?: self::EMOJI_CODES[array_rand(self::EMOJI_CODES)],
            'titlePhrase' => \XF::phrase($titlePhraseName),
            'addPhrase' => \XF::phrase($addPhraseName),
            'emptyPhrase' => \XF::phrase($emptyPhraseName),
            'addUrl' => $addUrl
        ];

        return $this->view(
            C::ADDON_PREFIX('Listing\Empty'),
            C::TEMPLATE_PREFIX('list_empty'),
            $viewParams
        );
    }
}