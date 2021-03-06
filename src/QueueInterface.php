<?php

namespace DominionEnterprises\Mongo;

/**
 * Abstraction of mongo db collection as priority queue.
 *
 * Tied priorities are ordered by time. So you may use a single priority for normal queuing (default args exist for
 * this purpose).  Using a random priority achieves random get()
 */
interface QueueInterface
{
    /**
     * Ensure an index for the get() method.
     *
     * @param array $beforeSort fields in get() call to index before the sort field.
     * @param array $afterSort fields in get() call to index after the sort field.
     *
     * @return void
     *
     * @throws \InvalidArgumentException value of $beforeSort or $afterSort is not 1 or -1 for ascending and descending
     * @throws \InvalidArgumentException key in $beforeSort or $afterSort was not a string
     */
    public function ensureGetIndex(array $beforeSort = [], array $afterSort = []);

    /**
     * Ensure an index for the count() method.
     *
     * @param array $fields fields in count() call to index in same format as \MongoDB\Collection::createIndex()
     * @param bool $includeRunning whether to include the running field in the index
     *
     * @return void
     *
     * @throws \InvalidArgumentException $includeRunning was not a boolean
     * @throws \InvalidArgumentException key in $fields was not a string
     * @throws \InvalidArgumentException value of $fields is not 1 or -1 for ascending and descending
     */
    public function ensureCountIndex(array $fields, $includeRunning);

    /**
     * Get a non running message from the queue.
     *
     * @param array $query in same format as \MongoDB\Collection::find() where top level fields do not contain operators.
     * Lower level fields can however. eg: valid {a: {$gt: 1}, "b.c": 3}, invalid {$and: [{...}, {...}]}
     * @param int $runningResetDuration second duration the message can stay unacked before it resets and can be
     *                                  retreived again.
     * @param int $waitDurationInMillis millisecond duration to wait for a message.
     * @param int $pollDurationInMillis millisecond duration to wait between polls.
     *
     * @return array|null the message or null if one is not found
     *
     * @throws \InvalidArgumentException $runningResetDuration, $waitDurationInMillis or $pollDurationInMillis was not
     *                                   an int
     * @throws \InvalidArgumentException key in $query was not a string
     */
    public function get(array $query, $runningResetDuration, $waitDurationInMillis = 3000, $pollDurationInMillis = 200);

    /**
     * Count queue messages.
     *
     * @param array $query in same format as \MongoDB\Collection::find() where top level fields do not contain operators.
     * Lower level fields can however. eg: valid {a: {$gt: 1}, "b.c": 3}, invalid {$and: [{...}, {...}]}
     * @param bool|null $running query a running message or not or all
     *
     * @return int the count
     *
     * @throws \InvalidArgumentException $running was not null and not a bool
     * @throws \InvalidArgumentException key in $query was not a string
     */
    public function count(array $query, $running = null);

    /**
     * Acknowledge a message was processed and remove from queue.
     *
     * @param array $message message received from get()
     *
     * @return void
     *
     * @throws \InvalidArgumentException $message does not have a field "id" that is a MongoId
     */
    public function ack(array $message);

    /**
     * Atomically acknowledge and send a message to the queue.
     *
     * @param array $message the message to ack received from get()
     * @param array $payload the data to store in the message to send. Data is handled same way
     *                       as \MongoDB\Collection::insertOne()
     * @param int $earliestGet earliest unix timestamp the message can be retreived.
     * @param float $priority priority for order out of get(). 0 is higher priority than 1
     * @param bool $newTimestamp true to give the payload a new timestamp or false to use given message timestamp
     *
     * @return void
     *
     * @throws \InvalidArgumentException $message does not have a field "id" that is a MongoId
     * @throws \InvalidArgumentException $earliestGet was not an int
     * @throws \InvalidArgumentException $priority was not a float
     * @throws \InvalidArgumentException $priority is NaN
     * @throws \InvalidArgumentException $newTimestamp was not a bool
     */
    public function ackSend(array $message, array $payload, $earliestGet = 0, $priority = 0.0, $newTimestamp = true);

    /**
     * Requeue message to the queue. Same as ackSend() with the same message.
     *
     * @param array $message message received from get().
     * @param int $earliestGet earliest unix timestamp the message can be retreived.
     * @param float $priority priority for order out of get(). 0 is higher priority than 1
     * @param bool $newTimestamp true to give the payload a new timestamp or false to use given message timestamp
     *
     * @return void
     *
     * @throws \InvalidArgumentException $message does not have a field "id" that is a MongoId
     * @throws \InvalidArgumentException $earliestGet was not an int
     * @throws \InvalidArgumentException $priority was not a float
     * @throws \InvalidArgumentException priority is NaN
     * @throws \InvalidArgumentException $newTimestamp was not a bool
     */
    public function requeue(array $message, $earliestGet = 0, $priority = 0.0, $newTimestamp = true);

    /**
     * Send a message to the queue.
     *
     * @param array $payload the data to store in the message. Data is handled same way as \MongoDB\Collection::insertOne()
     * @param int $earliestGet earliest unix timestamp the message can be retreived.
     * @param float $priority priority for order out of get(). 0 is higher priority than 1
     *
     * @return void
     *
     * @throws \InvalidArgumentException $earliestGet was not an int
     * @throws \InvalidArgumentException $priority was not a float
     * @throws \InvalidArgumentException $priority is NaN
     */
    public function send(array $payload, $earliestGet = 0, $priority = 0.0);
}
