<?php

namespace Common;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Model as BackendModel;

/**
 * This is our class with custom modifiers.
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Wouter Sioen <wouter@woutersioen.be>
 */
class TemplateModifiers
{
    /**
     * Dumps the data
     *  syntax: {$var|dump}
     *
     * @param string $var The variable to dump.
     * @return string
     */
    public static function dump($var)
    {
        \Spoon::dump($var, false);
    }

    /**
     * Format a number as currency
     *    syntax: {$var|formatcurrency[:currency[:decimals]]}
     *
     * @param string $var      The string to form.
     * @param string $currency The currency to will be used to format the number.
     * @param int    $decimals The number of decimals to show.
     * @return string
     */
    public static function formatCurrency($var, $currency = 'EUR', $decimals = null)
    {
        // @later get settings from backend
        switch ($currency) {
            case 'EUR':
                $decimals = ($decimals === null) ? 2 : (int) $decimals;

                // format as Euro
                return '€ ' . number_format((float) $var, $decimals, ',', ' ');
                break;
        }
    }

    /**
     * Highlights all strings in <code> tags.
     *    syntax: {$var|highlight}
     *
     * @param string $var The string passed from the template.
     * @return string
     */
    public static function highlightCode($var)
    {
        // regex pattern
        $pattern = '/<code>.*?<\/code>/is';

        // find matches
        if (preg_match_all($pattern, $var, $matches)) {
            // loop matches
            foreach ($matches[0] as $match) {
                // encase content in highlight_string
                $content = str_replace($match, highlight_string($match, true), $var);

                // replace highlighted code tags in match   @todo    shouldn't this be $var =
                $content = str_replace(array('&lt;code&gt;', '&lt;/code&gt;'), '', $var);
            }
        }

        return $var;
    }

    /**
     * Get a random var between a min and max
     * syntax: {$var|rand:min:max}
     *
     * @param string $var The string passed from the template.
     * @param int    $min The minimum number.
     * @param int    $max The maximum number.
     * @return int
     */
    public static function random($var = null, $min, $max)
    {
        return rand((int) $min, (int) $max);
    }

    /**
     * Convert a multiline string into a string without newlines so it can be handles by JS
     * syntax: {$var|stripnewlines}
     *
     * @param string $var The variable that should be processed.
     * @return string
     */
    public static function stripNewlines($var)
    {
        return str_replace(array("\n", "\r"), '', $var);
    }

    /**
     * Truncate a string
     *    syntax: {$var|truncate:max-length[:append-hellip]}
     *
     * @param string $var       The string passed from the template.
     * @param int    $length    The maximum length of the truncated string.
     * @param bool   $useHellip Should a hellip be appended if the length exceeds the requested length?
     * @return string
     */
    public static function truncate($var = null, $length, $useHellip = true)
    {
        // remove special chars, all of them, also the ones that shouldn't be there.
        $var = \SpoonFilter::htmlentitiesDecode($var, ENT_QUOTES);

        // remove HTML
        $var = strip_tags($var);

        // less characters
        if (mb_strlen($var) <= $length) {
            return \SpoonFilter::htmlspecialchars($var);
        } else {
            // more characters
            // hellip is seen as 1 char, so remove it from length
            if ($useHellip) {
                $length = $length - 1;
            }

            // get the amount of requested characters
            $var = mb_substr($var, 0, $length, SPOON_CHARSET);

            // add hellip
            if ($useHellip) {
                $var .= '…';
            }

            // return
            return \SpoonFilter::htmlspecialchars($var, ENT_QUOTES);
        }
    }
}
