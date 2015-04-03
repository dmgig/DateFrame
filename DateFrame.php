<?php

/**
 * DateFrame class to ease generation of MySQL BETWEEN clauses
 * Contructor requires start and end date. Each DateFrame can then be broken into
 * arrays of shorter interval date frames of day, seven day, Sun-Sat weeks, or months.
 */
class DateFrame{
	
	public $start;
	public $end;
	public $diff;
	public $criteria = null;   
	
	public function __construct($start, $end, $field = null){

		$this->start = date('Y-m-d', strtotime($start)) . ' 00:00:00';
		$this->end   = date('Y-m-d', strtotime($end))   . ' 23:59:59';
		
		$startdiff   = new DateTime($this->start);
		$enddiff     = new DateTime($this->end);
		$this->diff  = $enddiff->diff($startdiff)->format('%a');

		if($field){
			$this->criteria = self::getCriteriaBetween($field);
		}
	}

	public function setCriteria($field){
		$this->criteria = "$field BETWEEN '{$this->start}' AND '{$this->end}'";
	}
	
	public function getCriteria($field){
		$this->setCriteria($field);
		return $this->criteria;
	}
	
	public function asDayIntervals(){

		$intervals = array();		
		$period = $this->getDatePeriod('P1D');

		foreach($period as $k => $date){
			$dateframe = new DateFrame($date->format('Y-m-d'), $date->format('Y-m-d'));
			$intervals[] = $dateframe;
		}
		return $intervals;
	}

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
	
	public function asMonthIntervals(){

		$intervals = array();
		$period = $this->getDatePeriod('P1M');
		
		foreach($period as $k => $date){
			$start = $date->format('Y-m-d');
			$end   = $date->add(new DateInterval('P1M'))->sub(new DateInterval('P1D'))->format('Y-m-d');
			if($end > $this->end) $end = $this->end;
			$dateframe = new DateFrame($start, $end);
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
	
}
