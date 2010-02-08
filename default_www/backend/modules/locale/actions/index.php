<?php

/**
 * BackendLocaleIndex
 *
 * This is the index-action, it will display the overview of language labels
 *
 * @package		backend
 * @subpackage	locale
 *
 * @author 		Davy Hellemans <davy@netlash.com>
 * @author 		Tijs Verkoyen <tijs@netlash.com>
 * @since		2.0
 */
class BackendLocaleIndex extends BackendBaseActionIndex
{
	/**
	 * Filter variables
	 *
	 * @var	arra
	 */
	private $filter;


	/**
	 * Form
	 *
	 * @var BackendForm
	 */
	private $frm;


	/**
	 * Execute the action
	 *
	 * @return	void
	 */
	public function execute()
	{
		// call parent, this will probably add some general CSS/JS or other required files
		parent::execute();

		// set filter
		$this->setFilter();

		// load form
		$this->loadForm();

		// load datagrids
		$this->loadDataGrid();

		// parse page
		$this->parse();

		// display the page
		$this->display();
	}


	/**
	 * Builds the query for this datagrid
	 *
	 * @return	array		An array with two arguments containing the query and its parameters.
	 */
	private function buildQuery()
	{
		// init var
		$parameters = array();

		// start query
		$query = 'SELECT l.id, l.language,
						l.application,
						l.module,
						l.type,
						l.name,
						l.value
						FROM locale AS l
						WHERE 1';

		// add language
		if($this->filter['language'] !== null)
		{
			$query .= ' AND l.language = ?';
			$parameters[] = $this->filter['language'];
		}

		// add application
		if($this->filter['application'] !== null)
		{
			$query .= ' AND l.application = ?';
			$parameters[] = $this->filter['application'];
		}

		// add module
		if($this->filter['module'] !== null)
		{
			$query .= ' AND l.module = ?';
			$parameters[] = $this->filter['module'];
		}

		// add type
		if($this->filter['type'] !== null)
		{
			$query .= ' AND l.type = ?';
			$parameters[] = $this->filter['type'];
		}

		// add name
		if($this->filter['name'] !== null)
		{
			$query .= ' AND l.name LIKE  ?';
			$parameters[] = '%'. $this->filter['name'] .'%';
		}

		// add value
		if($this->filter['value'] !== null)
		{
			$query .= ' AND l.value LIKE ?';
			$parameters[] = '%'. $this->filter['value'] .'%';
		}

		return array($query, $parameters);
	}


	/**
	 * Load the datagrids.
	 *
	 * @return void
	 */
	private function loadDataGrid()
	{
		// fetch query and parameters
		list($query, $parameters) = $this->buildQuery();

		// create datagrid
		$this->datagrid = new BackendDataGridDB($query, $parameters);

		// overrule default URL
		$this->datagrid->setURL(BackendModel::createURLForAction(null, null, null, array('offset' => '[offset]', 'order' => '[order]', 'sort' => '[sort]', 'language' => $this->filter['language'], 'application' => $this->filter['application'], 'module' => $this->filter['module'], 'type' => $this->filter['type'], 'name' => $this->filter['name'], 'value' => $this->filter['value']), false));

		// header labels
		$this->datagrid->setHeaderLabels(array('language' => ucfirst(BL::getLabel('Language')), 'application' => ucfirst(BL::getLabel('Application')), 'module' => ucfirst(BL::getLabel('Module')), 'type' => ucfirst(BL::getLabel('Type')), 'name' => ucfirst(BL::getLabel('Name')), 'value' => ucfirst(BL::getLabel('Value'))));

		// sorting columns
		$this->datagrid->setSortingColumns(array('language', 'application', 'module', 'type', 'name', 'value'), 'name');

		// add the multicheckbox column
		$this->datagrid->addColumn('checkbox', '<div class="checkboxHolder"><input type="checkbox" name="toggleChecks" value="toggleChecks" />', '<input type="checkbox" name="id[]" value="[id]" class="inputCheckbox" /></div>');
		$this->datagrid->setColumnsSequence('checkbox');

		// add mass action dropdown
		$ddmMassAction = new SpoonDropDown('action', array('delete' => BL::getLabel('Delete')), 'delete');
		$this->datagrid->setMassAction($ddmMassAction);

		// update value
		$this->datagrid->setColumnFunction(array('BackendDataGridFunctions', 'truncate'), array('[value]', 30), 'value', true);

		// add columns
		$this->datagrid->addColumn('edit', null, BL::getLabel('Edit'), BackendModel::createURLForAction('edit', null, null, array('language' => $this->filter['language'], 'application' => $this->filter['application'], 'module' => $this->filter['module'], 'type' => $this->filter['type'], 'name' => $this->filter['name'], 'value' => $this->filter['value'])) .'&id=[id]', BL::getLabel('Edit'));
	}


	/**
	 * Load the form
	 *
	 * @todo	davy: velden opkuisen
	 * @todo	davy: testen wat dit geeft als je met speciale karaketers in de URL werkt. (urlencode nodig?)
	 *
	 * @return	void
	 */
	private function loadForm()
	{
		// create form
		$this->frm = new BackendForm('filter', BackendModel::createURLForAction(), 'get');

		// add fields
		$this->frm->addTextField('name', $this->filter['name']);
		$this->frm->addTextField('value', $this->filter['value']);
		$this->frm->addDropDown('language', BL::getInterfaceLanguages(), $this->filter['language']);
		$this->frm->getField('language')->setDefaultElement(ucfirst(BL::getLabel('ChooseALanguage')));
		$this->frm->addDropDown('application', array('backend' => 'Backend', 'frontend' => 'Frontend'), $this->filter['application']);
		$this->frm->getField('application')->setDefaultElement(ucfirst(BL::getLabel('ChooseAnApplication')));
		$this->frm->addDropDown('module', BackendModel::getModulesForDropDown(false), $this->filter['module']);
		$this->frm->getField('module')->setDefaultElement(ucfirst(BL::getLabel('ChooseAModule')));
		$this->frm->addDropDown('type', BackendLocaleModel::getTypesForDropDown(), $this->filter['type']);
		$this->frm->getField('type')->setDefaultElement(ucfirst(BL::getLabel('ChooseAType')));

		// manually parse fields
		$this->frm->parse($this->tpl);
	}


	/**
	 * Parse & display the page
	 *
	 * @return	void
	 */
	private function parse()
	{
		// parse datagrid
		$this->tpl->assign('datagrid', ($this->datagrid->getNumResults() != 0) ? $this->datagrid->getContent() : false);

		// parse paging & sorting
		$this->tpl->assign('offset', (int) $this->datagrid->getOffset());
		$this->tpl->assign('order', (string) $this->datagrid->getOrder());
		$this->tpl->assign('sort', (string) $this->datagrid->getSort());

		// parse filter
		$this->tpl->assign($this->filter);
	}


	/**
	 * Sets the filter based on the $_GET array.
	 *
	 * @return	void
	 */
	private function setFilter()
	{
		$this->filter['language'] = $this->getParameter('language');
		$this->filter['application'] = $this->getParameter('application');
		$this->filter['module'] = $this->getParameter('module');
		$this->filter['type'] = $this->getParameter('type');
		$this->filter['name'] = $this->getParameter('name');
		$this->filter['value'] = $this->getParameter('value');
	}
}

?>