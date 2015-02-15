<?php

	/**
	 * @group date
	 */
	public function test_dateFrame() {
		
		// w/o criteria
		$dateframe = new DateFrame('2015-01-01', '3/31/2015');
		$this->assertEquals('2015-01-01 00:00:00', $dateframe->start);
		$this->assertEquals('2015-03-31 23:59:59', $dateframe->end);
		$this->assertNull($dateframe->criteria);	

		$criteria = $dateframe->getCriteria('completeddate');
		$this->assertInstanceOf('Criteria', $criteria);
		$this->assertInstanceOf('Criteria', $dateframe->criteria);
		$this->assertInstanceOf('Criterion_between', $dateframe->criteria->where[0]);

		$by_day = $dateframe->asDayIntervals();
		$this->assertEquals('2015-01-01 00:00:00', $by_day[0]->start);
		
		$by_day = $dateframe->asDayIntervals();
		$keys = array_keys($by_day);
		$this->assertEquals('2015-03-31 23:59:59', $by_day[end($keys)]->end);

		$by_7day = $dateframe->asSevenDayIntervals();
		$this->assertEquals('2015-01-01 00:00:00', $by_7day[0]->start);
		$this->assertEquals('2015-01-28 23:59:59', $by_7day[3]->end);
		$keys = array_keys($by_7day);
		$this->assertEquals('2015-03-31 23:59:59', $by_7day[end($keys)]->end);
		
		$by_week = $dateframe->asWeekIntervals();
		$this->assertEquals('2015-01-01 00:00:00', $by_week[0]->start);
		$this->assertEquals('2015-01-04 00:00:00', $by_week[1]->start);
		$this->assertEquals('2015-01-10 23:59:59', $by_week[1]->end);		
		$keys = array_keys($by_week);
		$this->assertEquals('2015-03-31 23:59:59', $by_week[end($keys)]->end);		
		
		$by_month = $dateframe->asMonthIntervals();
		$this->assertEquals('2015-01-31 23:59:59', $by_month[0]->end);		
	
	}
