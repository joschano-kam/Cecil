<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Exception\RuntimeException;

/**
 * Class Collection.
 */
class Collection extends CecilCollection
{
    /**
     * Returns all "showable" pages.
     */
    public function showable(): self
    {
        return $this->filter(function (Page $page) {
            if (
                $page->getVariable('published') === true   // is published
                && $page->isVirtual() === false            // is created from a file
                && $page->getVariable('redirect') === null // is not a redirection
                && $page->getVariable('exclude') !== true  // is not excluded from lists
            ) {
                return true;
            }
        });
    }

    /**
     * Alias of showable().
     */
    public function all(): self
    {
        return $this->showable();
    }

    /**
     * Sorts pages by.
     *
     * $options: date|updated|title|weight
     * $options:
     *   variable: date|updated|title|weight
     *   desc_title: false|true
     *   reverse: false|true
     */
    public function sortBy(array|string|null $options): self
    {
        $sortBy = \is_string($options) ? $options : $options['variable'] ?? 'date';
        $sortMethod = \sprintf('sortBy%s', ucfirst(str_replace('updated', 'date', $sortBy)));
        if (!method_exists($this, $sortMethod)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid value for `sortby` to sort collection "%s".', $sortBy, $this->getId()));
        }

        return $this->$sortMethod($options);
    }

    /**
     * Sorts pages by date (or 'updated'): the most recent first.
     */
    public function sortByDate(array|string|null $options = null): self
    {
        $opt = [];
        // backward compatibility (i.e. $options = 'updated')
        if (\is_string($options)) {
            $opt['variable'] = $options;
        }
        // options
        $opt['variable'] = $options['variable'] ?? 'date';
        $opt['descTitle'] = $options['descTitle'] ?? false;
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        $pages = $this->usort(function ($a, $b) use ($opt) {
            if ($a[$opt['variable']] == $b[$opt['variable']]) {
                // if dates are equal and "descTitle" is true
                if ($opt['descTitle'] && (isset($a['title']) && isset($b['title']))) {
                    return strnatcmp($b['title'], $a['title']);
                }

                return 0;
            }

            return $a[$opt['variable']] > $b[$opt['variable']] ? -1 : 1;
        });
        if ($opt['reverse']) {
            $pages = $pages->reverse();
        }

        return $pages;
    }

    /**
     * Sorts pages by title (natural sort).
     */
    public function sortByTitle(array|string|null $options = null): self
    {
        $opt = [];
        // options
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        return $this->usort(function ($a, $b) use ($opt) {
            return ($opt['reverse'] ? -1 : 1) * strnatcmp($a['title'], $b['title']);
        });
    }

    /**
     * Sorts by weight (the heaviest first).
     */
    public function sortByWeight(array|string|null $options = null): self
    {
        $opt = [];
        // options
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        return $this->usort(function ($a, $b) use ($opt) {
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($opt['reverse'] ? -1 : 1) * ($a['weight'] < $b['weight'] ? -1 : 1);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): Page
    {
        return parent::get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function first(): ?Page
    {
        return parent::first();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $callback): self
    {
        return parent::filter($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(?\Closure $callback = null): self
    {
        return parent::usort($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(): self
    {
        return parent::reverse();
    }
}
