<?php

namespace Lullabot\Mpx\DataService;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Lullabot\Mpx\DataService\Access\Account;

/**
 * An ObjectList represents a list of data service objects from a search query.
 *
 * @see https://docs.theplatform.com/help/wsf-retrieving-data-objects#tp-toc11
 * @see https://docs.theplatform.com/help/wsf-cjson-format#cJSONformat-cJSONobjectlistpayloads
 */
class ObjectList implements \ArrayAccess, \Iterator, JsonInterface
{
    /**
     * An array of namespaces in the results.
     *
     * @var string[]
     */
    protected $xmlNs;

    /**
     * The start index of this result list.
     *
     * @var int
     */
    protected $startIndex;

    /**
     * The number of items per page in this result set.
     *
     * @var int
     */
    protected $itemsPerPage;

    /**
     * The total number of entries.
     *
     * @var int
     */
    protected $entryCount;

    /**
     * @var ObjectInterface[]
     */
    protected $entries = [];

    /**
     * The total count of objects across all pages.
     *
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var ObjectListQuery
     */
    protected $objectListQuery;

    /**
     * The position of the array index.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * The factory used to generate the next object list request.
     *
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * The account context used for this list.
     *
     * @var Account
     */
    protected $account;

    /**
     * The original JSON of this object list.
     *
     * @var array
     */
    protected $json;

    /**
     * @return string[]
     */
    public function getXmlNs(): array
    {
        return $this->xmlNs;
    }

    /**
     * @param string[] $xmlNs
     */
    public function setXmlNs(array $xmlNs)
    {
        $this->xmlNs = $xmlNs;
    }

    /**
     * @return int
     */
    public function getStartIndex(): int
    {
        return $this->startIndex;
    }

    /**
     * @param int $startIndex
     */
    public function setStartIndex(int $startIndex)
    {
        $this->startIndex = $startIndex;
    }

    /**
     * @return int
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @param int $itemsPerPage
     */
    public function setItemsPerPage(int $itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @return int
     */
    public function getEntryCount(): int
    {
        return $this->entryCount;
    }

    /**
     * Set the number of entries in the current list.
     *
     * @param int $entryCount
     */
    public function setEntryCount(int $entryCount)
    {
        $this->entryCount = $entryCount;
    }

    /**
     * @return ObjectInterface[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param ObjectInterface[] $entries
     */
    public function setEntries(array $entries)
    {
        $this->entries = $entries;
        $this->rewind();
    }

    /**
     * Return the total results of this list across all pages.
     *
     * @return int The total number of results.
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * Set the total results of this list across all pages.
     *
     * @param int The total number of results.
     */
    public function setTotalResults(int $totalResults)
    {
        $this->totalResults = $totalResults;
    }

    /**
     * @return ObjectListQuery
     */
    public function getObjectListQuery(): ObjectListQuery
    {
        if (!isset($this->objectListQuery)) {
            throw new \LogicException('This object list does not have an ObjectListQuery set.');
        }

        return $this->objectListQuery;
    }

    /**
     * @param ObjectListQuery $byFields
     */
    public function setObjectListQuery(ObjectListQuery $byFields)
    {
        $this->objectListQuery = $byFields;
    }

    /**
     * Set the objects needed to generate a next request.
     *
     * @param DataObjectFactory $dataObjectFactory The factory used to load the next ObjectList.
     * @param IdInterface       $account           (optional) The account context to use for the request.
     */
    public function setDataObjectFactory(DataObjectFactory $dataObjectFactory, IdInterface $account = null)
    {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->account = $account;
    }

    /**
     * Return if this object list has a next list to load.
     *
     * @return bool True if a next list exists, false otherwise.
     */
    public function hasNext(): bool
    {
        return !empty($this->entries) && ($this->getStartIndex() + $this->getItemsPerPage() - 1 < $this->getTotalResults());
    }

    /**
     * Return the next object list request, if one exists.
     *
     * @see \Lullabot\Mpx\DataService\ObjectList::setDataObjectFactory
     *
     * @return PromiseInterface|bool A promise to the next ObjectList, or false if no list exists.
     */
    public function nextList()
    {
        if (!$this->hasNext()) {
            return false;
        }

        if (!isset($this->dataObjectFactory)) {
            throw new \LogicException('setDataObjectFactory must be called before calling nextList.');
        }

        if (!isset($this->objectListQuery)) {
            throw new \LogicException('setByFields must be called before calling nextList.');
        }

        $byFields = clone $this->objectListQuery;
        $range = Range::nextRange($this);
        $byFields->setRange($range);

        return $this->dataObjectFactory->selectRequest($byFields, $this->account);
    }

    /**
     * Yield select requests for all pages of this object list.
     *
     * @return \Generator A generator returning promises to object lists.
     */
    public function yieldLists(): \Generator
    {
        if (!isset($this->dataObjectFactory)) {
            throw new \LogicException('setDataObjectFactory must be called before calling nextList.');
        }

        if (!isset($this->objectListQuery)) {
            throw new \LogicException('setByFields must be called before calling nextList.');
        }

        // We need to yield ourselves first.
        $thisList = new Promise();
        $thisList->resolve($this);
        yield $thisList;

        $ranges = Range::nextRanges($this);
        foreach ($ranges as $range) {
            $byFields = clone $this->objectListQuery;
            $byFields->setRange($range);
            yield $this->dataObjectFactory->selectRequest($byFields, $this->account);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->getEntries()[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getEntries()[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->entries[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->entries[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->entries[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->entries[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function setJson(string $json)
    {
        $this->json = \GuzzleHttp\json_decode($json, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getJson()
    {
        if (!$this->json) {
            throw new \LogicException('This object has no original JSON representation available');
        }

        return $this->json;
    }
}
