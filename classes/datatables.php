<?php defined('SYSPATH') or die('No direct script access.');
/**
 * DataTables
 * 
 * @package		DataTables
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class DataTables
{	
	/**
	 * Factory pattern
	 * 
	 * @access	public
	 * @param	mixed
	 * @return	DataTables
	 */
	public static function factory(Paginate $paginate = NULL)
	{
		return new DataTables($paginate);
	}
	
	/**
	 * Whether or not current request is via DataTables
	 * 
	 * @static
	 * @access	public
	 * @param	mixed	NULL|Request
	 * @return	bool
	 */
	public static function request(Request $request = NULL)
	{
		$request = ($request) ? $request : Request::current();
		
		return (bool) $request->query('sEcho');
	}

	/**
	 * Paginate
	 * 
	 * @access	protected
	 * @var		Paginate
	 */
	protected $_paginate;

	/**
	 * Paginate result
	 * 
	 * @access	protected
	 * @var		mixed
	 */
	protected $_result;	
	
	/**
	 * Rows
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_rows = array();		
	
	/**
	 * View
	 * 
	 * @access	protected
	 * @var		NULL|string
	 */
	protected $_view;	
	
	/**
	 * Initiate
	 * 
	 * @access	public
	 * @param	mixed	NULL|Paginate
	 * @return	void
	 */
	public function __construct(Paginate $paginate = NULL)
	{
		$this->paginate($paginate);
	}
	
	/**
	 * Get or set Paginate
	 * 
	 * @access	public
	 * @param	Paginate
	 * @return	mixed	$this|Paginate
	 */
	public function paginate(Paginate $paginate = NULL)
	{
		if ($paginate === NULL)
			return $this->_paginate;
			
		$this->_paginate = $paginate;
		
		return $this;
	}

	/**
	 * Set or get View file path
	 * 
	 * @access	public
	 * @param	mixed	NULL|string
	 * @return	mixed	$this|string
	 */
	public function view($path = NULL)
	{
		if ($path === NULL)
			return $this->_view;
		
		$this->_view = $path;
		
		return $this;
	}	
	
	/**
	 * Add row to output
	 * 
	 * @access	public
	 * @param	array
	 * @return	$this
	 */
	public function add_row(array $row)
	{
		$this->_rows[] = $row;
		
		return $this;
	}	
	
	/**
	 * Get result
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function result()
	{
		return $this->_result;
	}		
	
	/**
	 * Execute
	 * 
	 * @access	public
	 * @param	mixed	NULL|Request
	 * @return	$this
	 */
	public function execute(Request $request = NULL)
	{
		// Prevent multiple execution
		if ($this->_result === NULL)
		{
			$request = ($request) ? $request : Request::current();
			
			$columns = $this->_paginate->columns();
			
			if ($request->query('iSortCol_0') !== NULL)
			{
				for ($i = 0; $i < intval($request->query('iSortingCols')); $i++)
				{
					$column = $columns[intval($request->query('iSortCol_' . $i))];
					
					$sort = 'Paginate::SORT_' . strtoupper($request->query('sSortDir_' . $i));
					
					if (defined($sort))
					{
						$this->_paginate->sort($column, constant($sort));
					}
				}	
			}
			
			if ($request->query('iDisplayStart') !== NULL && $request->query('iDisplayLength') != '-1')
			{
				$start = $request->query('iDisplayStart');
				$length = $request->query('iDisplayLength');
				
				$this->_paginate->limit($start, $length);
			}

			if ($request->query('sSearch'))
			{
				$this->_paginate->search($request->query('sSearch'));
			}
			
			$this->_result = $this->_paginate->execute()->result();
	
			$this->_count_total = $this->_paginate->count_total();
			
			// Count should always match total unless search is being applied
			$this->_count = ($request->query('sSearch')) ? $this->_paginate->count() : $this->_count_total;
		}
	}
	
	/**
	 * Render
	 * 
	 * @access	public
	 * @return	string
	 */
	public function __toString()
	{
		return $this->render();
	}	
	
	/**
	 * Render
	 * 
	 * @access	public
	 * @return	string
	 */
	public function render()
	{
		$this->execute();
		
		if ($this->_view)
		{
			View::factory($this->_view, array('datatables' => $this))->render();
		}
		
		return json_encode(array
		(
			'sEcho' 				=> intval(Request::current()->query('sEcho')),
			'iTotalRecords' 		=> $this->_count_total,
			'iTotalDisplayRecords' 	=> $this->_count,
			'aaData' 				=> $this->_rows
		));
	}
}