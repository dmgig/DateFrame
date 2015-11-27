# DateFrame

PHP DateFrame class to ease working with date intervals, and ease generation of SQL BETWEEN clauses
 
Contructor requires start and end date. 

```php
$dateframe = new DateFrame('2015-01-01', '3/31/2015');
```

Each DateFrame can then be *broken into arrays of shorter interval DateFrames* broken down by: day, seven days, Sun-Sat weeks, n months, quarters, n years.

```php
$by_month = $dateframe->asMonthIntervals(); 
// $by_month set to array of three DateFrame objects: Jan., Feb., Mar.
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
// $between set to "completed_date BETWEEN '2015-01-01 00:00:00' AND '2015-03-31 23:59:59'"

```

