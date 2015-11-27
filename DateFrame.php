<?php

/**
 * DateFrame class tfor working with date intervals and producting SQL BETWEEN statements
 */
class DateFrame{
	
	public $start;
	public $end;
	public $diff;
	public $criteria = null;   
	
	public function __construct($start, $end, $field = null){

		$this->orig_start = $start;
		$this->orig_end   = $end;
		
		$this->start = date('Y-m-d', strtotime($start)) . ' 00:00:00';
		$this->end   = date('Y-m-d', strtotime($end))   . ' 23:59:59';
		
		$startdiff   = new DateTime($this->start);
		$enddiff     = new DateTime($this->end);
		$this->diff  = $enddiff->diff($startdiff)->format('%a') + 1;

		if($field){
			$this->criteria = self::getCriteriaBetween($field);
		}
	}

	public static function create($start, $end, $field = null){
		return new DateFrame($start, $end, $field);
	}	

	/**
	 * check wether date is contained within this DateFrame
	 * @param   datestring
	 * @returns boolean
	 */		
	public function contains($date){
		$date = strtotime($date);
		if(!$date) throw new InvalidArgumentException("Used `$date` and it could not be parsed.");
		return ($date >= strtotime($this->start) && $date <= strtotime($this->end));
	}
	
	/**
	 * check wether dateframe is overlapped by passed DateFrame
	 * @param   dateframe
	 * @returns boolean
	 */		
	public function overlaps($dateframe){
		if(($this->start <= $dateframe->end) && ($this->end >= $dateframe->start)){
			return true;
		}
		return false;
	}	

	public function setCriteria($field){
		$this->criteria = "$field BETWEEN '{$this->start}' AND '{$this->end}'";
	}

	public function getCriteria($field){
		$this->setCriteria($field);
		return $this->criteria;
	}

	/**
	 * split current DateFrame into array of DateFrames in intervals of one day.
	 * @returns array array of DateFrames
	 */	
	public function asDayIntervals(){

		$intervals = array();		
		$period = $this->getDatePeriod('P1D');

		foreach($period as $k => $date){
			$dateframe = new DateFrame($date->format('Y-m-d'), $date->format('Y-m-d'));
			$intervals[] = $dateframe;
		}
		return $intervals;
	}

	/**
	 * split this into intervals of seven days, with no regard to sun-sat weeks. last will be trimmed at this->end date
	 * @returns array array of DateFrames
	 */
	public function asSevenDayIntervals(){

		$intervals = array();
		$period = $this->getDatePeriod('P7D');
		
		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval('P6D'))->format('Y-m-d');
			if($end > $this->end) $end = $this->end;
			$dateframe = new DateFrame($start, $end);
			$intervals[] = $dateframe;
		}
		return $intervals;
	}
	
	/**
	 * split current DateFrame into array of DateFrames of seven day intervals, by natural Sun-Sat week.
	 * First "week" will contain this->start through next saturday, and will continue in sun-Sat weeks from there. Last week will be trimmed at this->end
	 *	  wether Sat or not.
	 * @returns array array of DateFrames
	 */		
	public function asWeekIntervals(){
	
		$intervals = array();
		$period = $this->getDatePeriod('P7D');
		
		$start_is_sunday = (date('D', strtotime($this->start)) == 'Sun');
		
		if($start_is_sunday === false){ // start is not sunday, then make the first week the remainder until the upcoming sunday
			$first_sunday = date('Y-m-d', strtotime("next sunday", strtotime($this->start)));
			$start = date('Y-m-d', strtotime($this->start));
			$end = new DateTime($first_sunday);
			$end = $end->sub(new DateInterval('P1D'))->format('Y-m-d');
			$dateframe = new DateFrame($start, $end);
			$intervals[] = $dateframe;
		}
		
		$period = $this->getDatePeriod('P7D', $first_sunday);

		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval('P6D'))->format('Y-m-d');
			if($end > $this->end) $end = $this->end;
			$dateframe = new DateFrame($start, $end);
			$intervals[] = $dateframe;
		}
		return $intervals;
	}
	
	/**
	 * split current DateFrame into array of DateFrames of month long intervals, by calendar month or months.
	 * @param number of months in interval
	 * @returns array array of DateFrames
	 */		
	public function asMonthIntervals($months=1){

		$intervals = array();
		$period = $this->getDatePeriod("P{$months}M");
		
		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval("P{$months}M"))->sub(new DateInterval('P1D'))->format('Y-m-d');
			if($end > $this->end) $end = $this->end;
			$dateframe = new DateFrame($start, $end);
			$dateframe->interval = "{$months}M";
			$intervals[] = $dateframe;
		}
		return $intervals;
	}

	/**
	 * split current DateFrame into array of DateFrames corresponding to quarters (Jan-Mar, Apr-Jun, Jul-Sep, Oct-Dec).
	 * @returns array array of DateFrames
	 */		
	public function asQuarterIntervals(){

		$intervals = array();
		$period = $this->getDatePeriod('P3M', $this->firstOfQuarter());
		
		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval('P3M'))->sub(new DateInterval('P1D'))->format('Y-m-d');
			if($start < $this->start) $start = $this->start;
			if($end   > $this->end  ) $end   = $this->end;
			$dateframe = new DateFrame($start, $end);
			$dateframe->interval = '1Q';
			$intervals[] = $dateframe;
		}
		return $intervals;
	}	

	/**
	 * split current DateFrame into array of DateFrames corresponding to full years.
	 * Similar to months, if start date is not 1st of a year, first dateframe will go from start date to end of year.
	 * @param int number of years in interval
	 * @returns array array of DateFrames
	 */		
	public function asYearIntervals($years=1){

		$intervals = array();
		$period = $this->getDatePeriod("P{$years}Y", $this->firstOfYear());
		
		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval("P{$years}Y"))->sub(new DateInterval('P1D'))->format('Y-m-d');
			if($start < $this->start) $start = $this->start;
			if($end   > $this->end  ) $end   = $this->end;
			$dateframe = new DateFrame($start, $end);
			$dateframe->interval = "{$years}Y";
			$intervals[] = $dateframe;
		}
		return $intervals;
	}

	/**
	 * @param string PHP DatePeriod interval, like P1D, P1W, etc (http://php.net/manual/en/class.dateinterval.php)
	 * @param date   override the start date with this date if required (see method asWeekIntervals). Otherwise, uses start and end of $this
	 * @returns DatePeriod
	 */
	private function getDatePeriod($interval, $override_start = false){
		
		$datestart    = new DateTime($this->start);
		
		if($override_start)
			$datestart = new DateTime($override_start);
		
		$dateinterval = new DateInterval($interval);
		$dateend      = new DateTime($this->end);

		$period = new DatePeriod(
			$datestart,
			$dateinterval,
			$dateend
		);
		
		return $period;
	}

	/**
	 * @param year_start
	 * @param  date
	 * @return int [1-4] corresponding to quarter
	 */
	private static function inQuarter($year_start=1, $date=false){

		if(!$date) $date = date('Y-m-d');

		$unix = strtotime($date);
		if(!$unix) throw new InvalidArgumentException("Invalid date. Used $date. Must be PHP strtotime readable.");

		$year = date('Y', $unix);

		foreach(static::businessQuarters() as $k => $quarter){
			$start = strtotime("{$year}-" . $quarter[0]);
			$end   = strtotime("{$year}-" . $quarter[1]);
			if($unix >= $start && $unix <= $end)
				return $k;
		}
		// should be impossible to get here.
		throw new Exception('Unknown error.');
	}  

	/**
	 * get date of first day of quarter which less than or equal to DateFrame start date
	 * @returns datestring
	 */	
	private function firstOfQuarter(){
		$first_of_Q = null;
		$Q = static::inQuarter($this->start);
		$business_quarters = static::businessQuarters();
		$qtr_dates = $business_quarters[$Q];
		$year = date('Y', strtotime($this->start));
		$first_of_Q = $year . '-' . $qtr_dates[0];
		return $first_of_Q;
	}
	
	/**
	 * get date of first day of year which less than or equal to DateFrame start date
	 * @returns datestring
	 */	
	private function firstOfYear(){
		$first_of_year = null;
		$year = date('Y', strtotime($this->start));
		$first_of_year = $year . '-01-01';
		return $first_of_year;
	}
	
}
