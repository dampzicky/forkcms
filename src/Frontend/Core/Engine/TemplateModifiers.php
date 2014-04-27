<?php

namespace Frontend\Core\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Common\TemplateModifiers as BaseTemplateModifiers;
use Frontend\Core\Engine\Block\Widget as FrontendBlockWidget;
use Frontend\Modules\Profiles\Engine\Model as FrontendProfilesModel;

/**
 * Contains all Frontend-related custom modifiers
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 */
class TemplateModifiers extends BaseTemplateModifiers
{
    /**
     * Formats plain text as HTML, links will be detected, paragraphs will be inserted
     *    syntax: {$var|cleanupPlainText}
     *
     * @param string $var The text to cleanup.
     * @return string
     */
    public static function cleanupPlainText($var)
    {
        // redefine
        $var = (string) $var;

        // detect links
        $var = \SpoonFilter::replaceURLsWithAnchors(
            $var,
            Model::getModuleSetting('Core', 'seo_nofollow_in_comments', false)
        );

        // replace newlines
        $var = str_replace("\r", '', $var);
        $var = preg_replace('/(?<!.)(\r\n|\r|\n){3,}$/m', '', $var);

        // replace br's into p's
        $var = '<p>' . str_replace("\n", '</p><p>', $var) . '</p>';

        // cleanup
        $var = str_replace("\n", '', $var);
        $var = str_replace('<p></p>', '', $var);

        // return
        return $var;
    }

    /**
     * Format a number as a float
     *    syntax: {$var|formatfloat[:decimals]}
     *
     * @param float $number   The number to format.
     * @param int   $decimals The number of decimals.
     * @return string
     */
    public static function formatFloat($number, $decimals = 2)
    {
        return number_format((float) $number, (int) $decimals, '.', ' ');
    }

    /**
     * Format a number
     *    syntax: {$var|formatnumber}
     *
     * @param float $var The number to format.
     * @return string
     */
    public static function formatNumber($var)
    {
        // redefine
        $var = (float) $var;

        // get setting
        $format = Model::getModuleSetting('Core', 'number_format');

        // get amount of decimals
        $decimals = (strpos($var, '.') ? strlen(substr($var, strpos($var, '.') + 1)) : 0);

        // get separators
        $separators = explode('_', $format);
        $separatorSymbols = array('comma' => ',', 'dot' => '.', 'space' => ' ', 'nothing' => '');
        $decimalSeparator = (isset($separators[0], $separatorSymbols[$separators[0]]) ? $separatorSymbols[$separators[0]] : null);
        $thousandsSeparator = (isset($separators[1], $separatorSymbols[$separators[1]]) ? $separatorSymbols[$separators[1]] : null);

        // format the number
        return number_format($var, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Get the navigation html
     *    syntax: {$var|getnavigation[:type[:parentId[:depth[:excludeIds-splitted-by-dash[:tpl]]]]}
     *
     * @param string $var        The variable.
     * @param string $type       The type of navigation, possible values are: page, footer.
     * @param int    $parentId   The parent wherefore the navigation should be build.
     * @param int    $depth      The maximum depth that has to be build.
     * @param string $excludeIds Which pageIds should be excluded (split them by -).
     * @param string $tpl        The template that will be used.
     * @return string
     */
    public static function getNavigation(
        $var = null,
        $type = 'page',
        $parentId = 0,
        $depth = null,
        $excludeIds = null,
        $tpl = '/Core/Layout/Templates/Navigation.tpl'
    ) {
        // build excludeIds
        if ($excludeIds !== null) {
            $excludeIds = (array) explode('-', $excludeIds);
        }

        // get HTML
        $return = (string) Navigation::getNavigationHtml($type, $parentId, $depth, $excludeIds, $tpl);

        // return the var
        if ($return != '') {
            return $return;
        }

        // fallback
        return $var;
    }

    /**
     * Get a given field for a page-record
     *    syntax: {$var|getpageinfo:pageId[:field[:language]]}
     *
     * @param string $var      The string passed from the template.
     * @param int    $pageId   The id of the page to build the URL for.
     * @param string $field    The field to get.
     * @param string $language The language to use, if not provided we will use the loaded language.
     * @return string
     */
    public static function getPageInfo($var = null, $pageId, $field = 'title', $language = null)
    {
        // redefine
        $field = (string) $field;
        $language = ($language !== null) ? (string) $language : null;

        // get page
        $page = Navigation::getPageInfo((int) $pageId);

        // validate
        if (empty($page)) {
            return '';
        }
        if (!isset($page[$field])) {
            return '';
        }

        // return page info
        return $page[$field];
    }

    /**
     * Fetch the path for an include (theme file if available, core file otherwise)
     *    syntax: {$var|getpath:file}
     *
     * @param string $var  The variable.
     * @param string $file The base path.
     * @return string
     */
    public static function getPath($var, $file)
    {
        return Theme::getPath($file);
    }

    /**
     * Get the subnavigation html
     *   syntax: {$var|getsubnavigation[:type[:parentId[:startdepth[:enddepth[:excludeIds-splitted-by-dash[:tpl]]]]]}
     *
     *   NOTE: When supplying more than 1 ID to exclude, the single quotes around the dash-separated list are mandatory.
     *
     * @param string $var        The variable.
     * @param string $type       The type of navigation, possible values are: page, footer.
     * @param int    $pageId     The parent wherefore the navigation should be build.
     * @param int    $startDepth The depth to start from.
     * @param int    $endDepth   The maximum depth that has to be build.
     * @param string $excludeIds Which pageIds should be excluded (split them by -).
     * @param string $tpl        The template that will be used.
     * @return string
     */
    public static function getSubNavigation(
        $var = null,
        $type = 'page',
        $pageId = 0,
        $startDepth = 1,
        $endDepth = null,
        $excludeIds = null,
        $tpl = '/Core/Layout/Templates/Navigation.tpl'
    ) {
        // build excludeIds
        if ($excludeIds !== null) {
            $excludeIds = (array) explode('-', $excludeIds);
        }

        // get info about the given page
        $pageInfo = Navigation::getPageInfo($pageId);

        // validate page info
        if ($pageInfo === false) {
            return '';
        }

        // split URL into chunks
        $chunks = (array) explode('/', $pageInfo['full_url']);

        // remove language chunk
        $chunks = (SITE_MULTILANGUAGE) ? (array) array_slice($chunks, 2) : (array) array_slice($chunks, 1);
        if (count($chunks) == 0) {
            $chunks[0] = '';
        }

        // init var
        $parentURL = '';

        // build url
        for ($i = 0; $i < $startDepth - 1; $i++) {
            $parentURL .= $chunks[$i] . '/';
        }

        // get parent ID
        $parentID = Navigation::getPageId($parentURL);

        try {
            // get HTML
            $return = (string) Navigation::getNavigationHtml(
                $type,
                $parentID,
                $endDepth,
                $excludeIds,
                (string) $tpl
            );
        } catch (Exception $e) {
            return '';
        }

        // return the var
        if ($return != '') {
            return $return;
        }

        // fallback
        return $var;
    }

    /**
     * Get the URL for a given pageId & language
     *    syntax: {$var|geturl:pageId[:language]}
     *
     * @param string $var      The string passed from the template.
     * @param int    $pageId   The id of the page to build the URL for.
     * @param string $language The language to use, if not provided we will use the loaded language.
     * @return string
     */
    public static function getURL($var, $pageId, $language = null)
    {
        $language = ($language !== null) ? (string) $language : null;

        return Navigation::getURL((int) $pageId, $language);
    }

    /**
     * Get the URL for a give module & action combination
     *    syntax: {$var|geturlforblock:module[:action[:language]]}
     *
     * @param string $var      The string passed from the template.
     * @param string $module   The module wherefore the URL should be build.
     * @param string $action   A specific action wherefore the URL should be build, otherwise the default will be used.
     * @param string $language The language to use, if not provided we will use the loaded language.
     * @return string
     */
    public static function getURLForBlock($var, $module, $action = null, $language = null)
    {
        $action = ($action !== null) ? (string) $action : null;
        $language = ($language !== null) ? (string) $language : null;

        return Navigation::getURLForBlock((string) $module, $action, $language);
    }

    /**
     * Fetch an URL based on an extraId
     *    syntax: {$var|geturlforextraid:extraId[:language]}
     *
     * @param string $var      The string passed from the template.
     * @param int    $extraId  The id of the extra.
     * @param string $language The language to use, if not provided we will use the loaded language.
     * @return string
     */
    public static function getURLForExtraId($var, $extraId, $language = null)
    {
        $language = ($language !== null) ? (string) $language : null;

        return Navigation::getURLForExtraId((int) $extraId, $language);
    }

    /**
     * Parse a widget straight from the template, rather than adding it through pages.
     *
     * @param string $var    The variable.
     * @param string $module The module whose module we want to execute.
     * @param string $action The action to execute.
     * @param string $id     The widget id (saved in data-column).
     * @return string|null
     */
    public static function parseWidget($var, $module, $action, $id = null)
    {
        $data = $id !== null ? serialize(array('id' => $id)) : null;

        // create new widget instance and return parsed content
        $extra = new FrontendBlockWidget(Model::get('kernel'), $module, $action, $data);

        // set parseWidget because we will need it to skip setting headers in the display
        Model::getContainer()->set('parseWidget', true);

        try {
            $extra->execute();
            $content = $extra->getContent();
            Model::getContainer()->set('parseWidget', null);

            return $content;
        } catch (Exception $e) {
            // if we are debugging, we want to see the exception
            if (SPOON_DEBUG) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Output a profile setting
     *
     * @param string $var  The variable
     * @param string $name The name of the setting
     * @return string
     */
    public static function profileSetting($var, $name)
    {
        $profile = FrontendProfilesModel::get((int) $var);
        if ($profile === false) {
            return '';
        }

        // convert into array
        $profile = $profile->toArray();

        // @remark I know this is dirty, but I couldn't find a better way.
        if (in_array($name, array('display_name', 'registered_on', 'full_url')) && isset($profile[$name])) {
            return $profile[$name];
        } elseif (isset($profile['settings'][$name])) {
            return $profile['settings'][$name];
        } else {
            return '';
        }
    }

    /**
     * Formats a timestamp as a string that indicates the time ago
     *    syntax: {$var|timeago}
     *
     * @param string $var A UNIX-timestamp that will be formatted as a time-ago-string.
     * @return string
     */
    public static function timeAgo($var = null)
    {
        $var = (int) $var;

        // invalid timestamp
        if ($var == 0) {
            return '';
        }

        // return
        return '<abbr title="' . \SpoonDate::getDate(
            Model::getModuleSetting('Core', 'date_format_long') . ', ' . Model::getModuleSetting(
                'Core',
                'time_format'
            ),
            $var,
            FRONTEND_LANGUAGE
        ) . '">' . \SpoonDate::getTimeAgo($var, FRONTEND_LANGUAGE) . '</abbr>';
    }

    /**
     * Get the value for a user-setting
     *    syntax {$var|usersetting:setting[:userId]}
     *
     * @param string $var     The string passed from the template.
     * @param string $setting The name of the setting you want.
     * @param int    $userId  The userId, if not set by $var.
     * @return string
     */
    public static function userSetting($var = null, $setting, $userId = null)
    {
        $userId = ($var !== null) ? (int) $var : (int) $userId;
        $setting = (string) $setting;

        // validate
        if ($userId === 0) {
            throw new Exception('Invalid user id');
        }

        // get user
        $user = User::getBackendUser($userId);

        // return
        return (string) $user->getSetting($setting);
    }
}
