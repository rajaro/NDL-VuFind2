<?php

/**
 * SOLR QueryBuilder.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015-2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */

namespace FinnaSearch\Backend\Solr;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;

/**
 * SOLR QueryBuilder.
 *
 * @category VuFind
 * @package  Search
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class QueryBuilder extends \VuFindSearch\Backend\Solr\QueryBuilder
{
    /**
     * Maximum number of words in search query for spellcheck to be used
     */
    protected $maxSpellcheckWords;

    /**
     * Constructor.
     *
     * @param array  $specs                Search handler specifications
     * @param string $defaultDismaxHandler Default dismax handler (if no
     *                                     DismaxHandler set in specs).
     * @param int    $maxSpellcheckWords   Max number of words in query for
     *                                     spellcheck to be used
     *
     * @return void
     */
    public function __construct(
        array $specs = [],
        $defaultDismaxHandler = 'dismax',
        $maxSpellcheckWords = 5
    ) {
        parent::__construct($specs, $defaultDismaxHandler);
        $this->maxSpellcheckWords = $maxSpellcheckWords;
    }

    /**
     * Return SOLR search parameters based on a user query and params.
     *
     * @param AbstractQuery $query User query
     *
     * @return ParamBag
     */
    public function build(AbstractQuery $query)
    {
        $params = parent::build($query);

        if ($this->createSpellingQuery && ($sq = $params->get('spellcheck.q'))) {
            if (count(preg_split("/[\s,]/u", trim(end($sq)))) > $this->maxSpellcheckWords) {
                $params->set('spellcheck.q', '');
            }
        }

        if (!($query instanceof QueryGroup)) {
            $q = $params->get('q');
            foreach ($q as &$value) {
                $value = $this->getLuceneHelper()->finalizeSearchString($value);
            }
            $params->set('q', $q);
        }
        return $params;
    }

    /**
     * Reduce components of query group to a search string of a simple query.
     *
     * This function implements the recursive reduction of a query group.
     *
     * Finna: Added finalizeSearchString() call
     *
     * @param AbstractQuery $component Component
     *
     * @return string
     *
     * @see  self::reduceQueryGroup()
     * @todo Refactor so that functionality does not need to be copied
     */
    protected function reduceQueryGroupComponents(AbstractQuery $component)
    {
        if ($component instanceof QueryGroup) {
            $reduced = array_map(
                [$this, 'reduceQueryGroupComponents'],
                $component->getQueries()
            );
            $searchString = $component->isNegated() ? 'NOT ' : '';
            $reduced = array_filter(
                $reduced,
                function ($s) {
                    return '' !== $s;
                }
            );
            if ($reduced) {
                $searchString .= sprintf(
                    '(%s)',
                    implode(" {$component->getOperator()} ", $reduced)
                );
            }
        } else {
            $searchString  = $this->getLuceneHelper()
                ->normalizeSearchString($component->getString());
            $searchString  = $this->getLuceneHelper()
                ->finalizeSearchString($searchString);
            $searchHandler = $this->getSearchHandler(
                $component->getHandler(),
                $searchString
            );
            if ($searchHandler && '' !== $searchString) {
                $searchString
                    = $this->createSearchString($searchString, $searchHandler);
            }
        }
        return $searchString;
    }
}
