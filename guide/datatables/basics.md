# Setup

[Paginate](https://github.com/morgan/kohana-paginate) is taking care of interacting with 
whichever backend you choose (Database, ORM, ORM-REST, Dispatch, custom, etc). Once you have a Paginate 
object, simply:

	$paginate = Paginate::factory($orm);
	
	$datatables = DataTables::factory($paginate);
	
# Basic example

## Controller

	public function action_browse()
	{
		if (DataTables::is_request())
		{		
			$orm = ORM::factory('user');
	
			$paginate = Paginate::factory($orm)
				->columns(array('id', 'name'));
	
			$datatables = DataTables::factory($paginate)->execute();
			
			foreach ($datatables->result() as $user)
			{
				$datatables->add_row(array
				(
					$user->id,
					$user->name
				));
			}			

			$datatables->render($this->response);
		}
		else
			throw new HTTP_Exception_500();	
	}
	
# Example using view

## Controller
	
	$user = ORM::factory('user');
	
	$paginate = Paginate::factory($dispatch)
		->columns(array('id', 'name'));
	
	DataTables::factory($paginate)
		->request($this->request)
		->view('user/browse')
		->execute()
		->render($this->response);
				
## View "user/browse"

Using a view allows for per column manipulation.

	foreach ($datatables->result() as $user)
	{
		$datatables->add_row(array
		(
			$user->id,
			$user->name
		));
	}
