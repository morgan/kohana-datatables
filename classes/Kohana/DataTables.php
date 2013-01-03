<?php defined('SYSPATH') or die('No direct script access.');
/**
 * DataTables
 * 
 * @package		DataTables
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_DataTables
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
	public static function is_request(Request $request = NULL)
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
	 * Request
	 * 
	 * @access	protected
	 * @var		NULL|Request
	 */
	protected $_request;
	
	/**
	 * Cached render
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $_render;
	
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
	 * Set or get Request
	 * 
	 * @access	public
	 * @param	mixed	NULL|Request
	 * @return	mixed	$this|Request|NULL
	 */
	public function request(Request $request = NULL)
	{
		if ($request === NULL)
		{
			if ($this->_request instanceof Request)
				return $this->_request;
		
			return Request::current();
		}
		
		$this->_request = $request;
		
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
	public function execute()
	{
		$request = $this->request();

		if ( ! $request instanceof Request)
			throw new Kohana_Exception('DataTables expecting valid Request. If within a 
				sub-request, have controller pass `$this->request`.');
		
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
		
		$this->_result = $this->_paginate
			->execute()
			->result();

		$this->_count_total = $this->_paginate->count_total();
		
		// Count should always match total unless search is being applied
		$this->_count = ($request->query('sSearch')) 
			? $this->_paginate->count_search_total() 
			: $this->_count_total;

		return $this;
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
     * @param	Response
	 * @return	string
	 */
	public function render(Response $response = NULL)
	{
		if ($this->_render === NULL)
		{
			if ($this->_view)
			{
				View::factory($this->_view, array('datatables' => $this))->render();
			}

			$this->_render = json_encode(array
			(
				'sEcho' 				=> intval($this->request()->query('sEcho')),
				'iTotalRecords' 		=> $this->_count_total,
				'iTotalDisplayRecords' 	=> $this->_count,
				'aaData' 				=> $this->_rows
			));
		}

        if ($response instanceof Response)
        {
            $response->headers('content-type', 'application/json');
            $response->body($this->_render);
        }
		
		return $this->_render;
	}
}
