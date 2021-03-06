<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Constants;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('docu_link', [$this, 'documentationLink']),
            new TwigFilter('multiline_indent', [$this, 'multilineIndent']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('class_name', [$this, 'getClassName']),
        ];
    }

    /**
     * @param object $object
     * @return null|string
     */
    public function getClassName($object)
    {
        if (!\is_object($object)) {
            return null;
        }

        return \get_class($object);
    }

    public function multilineIndent(?string $string, string $indent): string
    {
        if (null === $string || '' === $string) {
            return '';
        }

        $parts = [];

        foreach (explode("\r\n", $string) as $part) {
            foreach (explode("\n", $part) as $tmp) {
                $parts[] = $tmp;
            }
        }

        $parts = array_map(function ($part) use ($indent) {
            return $indent . $part;
        }, $parts);

        return implode(PHP_EOL, $parts);
    }

    /**
     * @param string $url
     * @return string
     */
    public function documentationLink($url = '')
    {
        return Constants::HOMEPAGE . '/documentation/' . $url;
    }
}
