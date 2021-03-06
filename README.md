# DateFrame

PHP DateFrame class to ease working with date intervals (date ranges), and ease generation of SQL BETWEEN clauses
 
Contructor requires start and end date in any format readable aby PHP's *strtotime* function

```php
$dateframe = new DateFrame('2015-01-01', '3/31/2015');
/**
 * DateFrame Object
 * (
 *   [start] => 2015-01-01 00:00:00
 *   [end] => 2015-03-31 23:59:59
 *   [days] => 90
 *   [criteria] => 
 *   [orig_start] => 2015-01-01
 *   [orig_end] => 3/31/2015
 * )
 */

```

Each DateFrame can then be *broken into an array of shorter interval DateFrames* broken down by: day, seven days, Sun-Sat weeks, *n* months, fiscal quarters, *n* years.

```php
$by_month = $dateframe->asMonthIntervals(); 
/**
 * $by_month set to array of three DateFrame objects: Jan., Feb., Mar.
 *
 * Array
 * (
 *   [0] => DateFrame Object
 *       (
 *          [start] => 2015-01-01 00:00:00
 *          [end] => 2015-01-31 23:59:59
 *          [days] => 31
 *          [orig_start] => 2015-01-01
 *          [orig_end] => 2015-01-31
 *          [interval] => 1M
 *      )
 *
 *  [1] => DateFrame Object
 *      (
 *          [start] => 2015-02-01 00:00:00
 *          [end] => 2015-02-28 23:59:59
 *          [days] => 28
 *          [orig_start] => 2015-02-01
 *          [orig_end] => 2015-02-28
 *          [interval] => 1M
 *      )
 *
 *   [2] => DateFrame Object
 *      (
 *          [start] => 2015-03-01 00:00:00
 *          [end] => 2015-03-31 23:59:59
 *          [days] => 31
 *          [orig_start] => 2015-03-01
 *          [orig_end] => 2015-03-31
 *          [interval] => 1M
 *      )
 * )
 */
```

Methods to check if DateFrame *contains* date, or DateFrame *overlaps* another DateFrame.

```php
$dateframe->contains('2/1/2015'); // equals true

$dateframe2 = new DateFrame('2014-01-01', '3/31/2014');
$dateframe->overlaps($dateframe2); // equals false
```

Method to *create SQL BETWEEN clause*
```php
$dateframe = new DateFrame('2015-01-01', '3/31/2015');
$between = $dateframe->getCriteria('completed_date');
/**
 * DateFrame Object
 * (
 *   [start] => 2015-01-01 00:00:00
 *   [end] => 2015-03-31 23:59:59
 *   [days] => 90
 *   [criteria] => completeddate BETWEEN '2015-01-01 00:00:00' AND '2015-03-31 23:59:59'
 *   [orig_start] => 2015-01-01
 *   [orig_end] => 3/31/2015
 * )
 */

```

