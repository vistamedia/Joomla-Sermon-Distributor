<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.2.9
	@build			30th November, 2015
	@created		22nd October, 2015
	@package		Sermon Distributor
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Preacher View class
 */
class SermondistributorViewPreacher extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
                {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Assign the variables
		$this->form 		= $this->get('Form');
		$this->item 		= $this->get('Item');
		$this->script 		= $this->get('Script');
		$this->state		= $this->get('State');
                // get action permissions
		$this->canDo		= SermondistributorHelper::getActions('preacher',$this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref 		= $jinput->get('ref', 0, 'word');
		$this->refid            = $jinput->get('refid', 0, 'int');
		$this->referral         = '';
		if ($this->refid)
                {
                        // return to the item that refered to this item
                        $this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
                }
                elseif($this->ref)
                {
                        // return to the list view that refered to this item
                        $this->referral = '&ref='.(string)$this->ref;
                }

		// [6445] Get Linked view data
		$this->kalsermons		= $this->get('Kalsermons');

		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_SERMONDISTRIBUTOR_PREACHER_NEW' : 'COM_SERMONDISTRIBUTOR_PREACHER_EDIT'), 'pencil-2 article-add');
		// [10235] Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('preacher.create') && $isNew)
			{
				// [10247] We can create the record.
				JToolBarHelper::save('preacher.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('preacher.edit'))
			{
				// [10259] We can save the record.
				JToolBarHelper::save('preacher.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// [10264] Do not creat but cancel.
				JToolBarHelper::cancel('preacher.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// [10269] We can close it.
				JToolBarHelper::cancel('preacher.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// [10277] For new records, check the create permission.
				if ($this->canDo->get('preacher.create'))
				{
					JToolBarHelper::apply('preacher.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('preacher.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('preacher.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('preacher.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('preacher.edit'))
				{
					// [10304] We can save the new record
					JToolBarHelper::apply('preacher.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('preacher.save', 'JTOOLBAR_SAVE');
					// [10307] We can save this record, but check the create permission to see
					// [10308] if we can return to make a new one.
					if ($this->canDo->get('preacher.create'))
					{
						JToolBarHelper::custom('preacher.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				$canVersion = ($this->canDo->get('core.version') && $this->canDo->get('preacher.version'));
				if ($this->state->params->get('save_history', 1) && $this->canDo->get('preacher.edit') && $canVersion)
				{
					JToolbarHelper::versions('com_sermondistributor.preacher', $this->item->id);
				}
				if ($this->canDo->get('preacher.create'))
				{
					JToolBarHelper::custom('preacher.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('preacher.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// [10344] set help url for this view if found
		$help_url = SermondistributorHelper::getHelpUrl('preacher');
		if (SermondistributorHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_SERMONDISTRIBUTOR_HELP_MANAGER', false, $help_url);
		}
	}

        /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 30)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return SermondistributorHelper::htmlEscape($var, $this->_charset, true, 30);
		}
                // use the helper htmlEscape method instead.
		return SermondistributorHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(JText::_($isNew ? 'COM_SERMONDISTRIBUTOR_PREACHER_NEW' : 'COM_SERMONDISTRIBUTOR_PREACHER_EDIT'));
		$document->addStyleSheet(JURI::root() . "administrator/components/com_sermondistributor/assets/css/preacher.css"); 

		// [6480] Add the CSS for Footable.
		$document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.core.min.css');

		// [6482] Use the Metro Style
		if (!isset($this->fooTableStyle) || 0 == $this->fooTableStyle)
		{
			$document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.metro.min.css');
		}
		// [6487] Use the Legacy Style.
		elseif (isset($this->fooTableStyle) && 1 == $this->fooTableStyle)
		{
			$document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.standalone.min.css');
		}

		// [6492] Add the JavaScript for Footable
		$document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.js');
		$document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.sort.js');
		$document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.filter.js');
		$document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.paginate.js');

		$footable = "jQuery(document).ready(function() { jQuery(function () { jQuery('.footable').footable(); }); jQuery('.nav-tabs').on('click', 'li', function() { setTimeout(tableFix, 10); }); }); function tableFix() { jQuery('.footable').trigger('footable_resize'); }";
		$document->addScriptDeclaration($footable);

		$document->addScript(JURI::root() . $this->script);
		$document->addScript(JURI::root() . "administrator/components/com_sermondistributor/views/preacher/submitbutton.js");
		JText::script('view not acceptable. Error');
	}
}