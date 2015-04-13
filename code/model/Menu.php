<?php

class Menu extends DataObject{
	
 	private static $db = array(
 		'Title' => 'Varchar',
 		'StartDate' => 'Datetime',
 		'EndDate' => 'Datetime'
 	);

 	private static $has_many = array(
 		'ProductSelections' => 'MenuProductSelection',
 		'Groups' => 'MenuGroup'
 	);

 	private static $summary_fields = array(
 		'Title',
 		'StartDate.Nice'
 	);

 	private static $field_labels = array(
 		'StartDate.Nice' => 'Start Date'
 	);

 	public function getCMSFields() {
 		$fields = parent::getCMSFields();
 		if ($grid = $fields->fieldByName("Root.ProductSelections.ProductSelections")){
 			//move field to main tab
 			$fields->removeByName("ProductSelections");
 			$fields->addFieldToTab("Root.Main", $grid);

 			$grid->setConfig($conf = new GridFieldConfig_RecordEditor());
 			$conf->removeComponentsByType("GridFieldDataColumns")
 				->removeComponentsByType("GridFieldDataColumns")
 				->removeComponentsByType("GridFieldFilterHeader")
 				->removeComponentsByType("GridFieldPaginator")
 				->removeComponentsByType("GridFieldPageCount")
	 			->addComponent(new GridFieldOrderableRows())
	 			->addComponent(new GridFieldEditableColumns());

 			$summaryfields = MenuProductSelection::config()->summary_fields;
			unset($summaryfields['Group']);

			//add editable group column to grid
			$groups = $this->Groups();
 			if($groups->exists()){
 				$dropdown = DropdownField::create("GroupID", 'Grouping', 
					$groups->map('ID', 'Title')->toArray()
				)->setHasEmptyDefault(true);
	 			$summaryfields['GroupID']  = array(
	 				'title' => 'Group',
					'callback' => function($record, $column, $grid) use ($dropdown){
						return $dropdown;
					}
				);
		 		$conf->getComponentByType('GridFieldDetailForm')
					->setItemEditFormCallback(function($form, $component) use ($dropdown){
						$fields = $form->Fields();
						if(!$fields->fieldByName("GroupID")){
							$fields->push($dropdown);
						}
					});
	 		}

	 		$conf->getComponentByType('GridFieldEditableColumns')
	 			->setDisplayFields($summaryfields);

 			//re-add edit/delete row actions so they are in the correct order
 			$conf->removeComponentsByType("GridFieldEditButton")
 				->removeComponentsByType("GridFieldDeleteAction")
				->addComponent(new GridFieldEditButton())
				->addComponent(new GridFieldDeleteAction())
				->addComponent($importer = new GridFieldImporter('before'));

			$loader = $importer->getLoader($grid);
			$self = $this;
			$loader->transforms = array(
				"Product.Title" => array(
					"create" => false,
					"link" => true,
					"required" => true
				),
				"Group.Title" => array(
					'list' => $this->Groups()
				)
			);
			$loader->duplicateChecks = array(
				"Product.Title" => "Product.Title"
			);
 		}

 		if ($grid = $fields->fieldByName("Root.Groups.Groups")){
 			$conf = $grid->getConfig()
 				->removeComponentsByType("GridFieldAddExistingAutocompleter")
 				->removeComponentsByType('GridFieldDataColumns')
 				->removeComponentsByType('GridFieldAddNewButton')
 				->removeComponentsByType("GridFieldFilterHeader")
 				->removeComponentsByType("GridFieldPaginator")
 				->removeComponentsByType("GridFieldPageCount")
 				->addComponent($newbutton = new GridFieldAddNewInlineButton())
 				->addComponent(new GridFieldEditableColumns())
 				->addComponent(new GridFieldOrderableRows());

 			$newbutton->setTitle("Add Menu Grouping");

			$conf->getComponentByType('GridFieldEditableColumns')
					->setDisplayFields(array(
						'Title' => function($record, $column, $grid) {
							return new TextField($column, 'Title');
						}
					));

			//re-add edit/delete row actions so they are in the correct order
 			$conf->removeComponentsByType("GridFieldEditButton")
 				->removeComponentsByType("GridFieldDeleteAction")
				->addComponent(new GridFieldDeleteAction());
 		}

 		return $fields;
 	}

}

class MenuGroup extends DataObject{

	private static $db = array(
		'Title' => 'Varchar',
		'Sort' => 'Int'
	);

	private static $has_one = array(
		'Parent' => 'Menu'
	);

	public function validate() {
		$result =  parent::validate();
		if(!$this->Title){
			$result->error("Menu group must have a title.");
		}
		return $result;
	}

}