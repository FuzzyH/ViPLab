<?php

include_once './Services/Component/classes/class.ilPluginConfigGUI.php';


/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * ViPLab config gui
 * General settings for vip lab plugin
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilViPLabConfigGUI extends ilPluginConfigGUI
{

	/**
	* Handles all commmands, default is "configure"
	*/
	public function performCommand($cmd)
	{
		global $ilTabs;

		$ilTabs->addTab(
			'settings',
			ilViPLabPlugin::getInstance()->txt('tab_settings'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'configure')
		);

		switch ($cmd)
		{
			case "configure":
			case "save":
				$this->$cmd();
				break;

		}
	}
	
	/**
	 * Configure plugin 
	 */
	protected function configure(ilPropertyFormGUI $form = null)
	{
		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initConfigurationForm();
		}
		$GLOBALS['tpl']->setContent($form->getHTML());
	}
	
	/**
	 * Init configuration form
	 * @return ilPropertyFormGUI
	 */
	protected function initConfigurationForm()
	{
		$this->getPluginObject()->includeClass('class.ilViPLabSettings.php');
		$settings = ilViPLabSettings::getInstance();
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($GLOBALS['ilCtrl']->getFormAction($this));
		$form->setTitle($this->getPluginObject()->txt('form_tab_settings'));

		$active = new ilCheckboxInputGUI($this->getPluginObject()->txt('form_tab_settings_activate'), 'active');
		$active->setChecked($settings->isActive());
		$form->addItem($active);
		
		
		// ecs servers
		include_once './Services/WebServices/ECS/classes/class.ilECSServerSettings.php';
		$ecs = ilECSServerSettings::getInstance()->getServers();
		$options = array();
		$options[0] = $GLOBALS['lng']->txt('select_one');
		foreach($ecs as $server)
		{
			$options[$server->getServerId()] = $server->getTitle();
		}
		
		$ecs_select = new ilSelectInputGUI($this->getPluginObject()->txt('form_tab_settings_ecs_server'), 'ecs');
		$ecs_select->setValue($settings->getECSServer());
		$ecs_select->setInfo($this->getPluginObject()->txt('form_tab_settings_ecs_server_info'));
		$ecs_select->setRequired(TRUE);
		$ecs_select->setOptions($options);
		$form->addItem($ecs_select);
		
		$editor = new ilNonEditableValueGUI($this->getPluginObject()->txt('form_tab_settings_editor'));
		
		$width = new ilNumberInputGUI($this->getPluginObject()->txt('form_tab_settings_editor_width'),'width');
		$width->setValue($settings->getWidth());
		$width->setMinValue(1);
		$width->setSize(4);
		$width->setRequired(TRUE);
		$editor->addSubItem($width);
		
		$height = new ilNumberInputGUI($this->getPluginObject()->txt('form_tab_settings_editor_height'),'height');
		$height->setValue($settings->getHeight());
		$height->setMinValue(1);
		$height->setSize(4);
		$height->setRequired(TRUE);
		$editor->addSubItem($height);
		$form->addItem($editor);
		
		// languages
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->getPluginObject()->txt('form_tab_settings_section_languages'));
		$form->addItem($section);
		$this->getPluginObject()->includeClass('class.ilViPLabUtil.php');
		
		$configured = $settings->getLanguages();
		foreach(ilViPLabUtil::getAvailableLanguages() as $lng_key)
		{
			$lang_type = new ilSelectInputGUI($this->getPluginObject()->txt('plang_'.$lng_key),'plang_'.$lng_key);
			
			
			$options = array();
			if($settings->getECSServer())
			{
				$options = $this->readAvailabeMids($settings);
				
			}
			else
			{
				$lang_type->setDisabled(TRUE);
			}
			$lang_type->setOptions($options);
			if(array_key_exists($lng_key, $configured))
			{
				$lang_type->setValue($configured[$lng_key]);
			}
			$form->addItem($lang_type);
		}
		
		
		$form->addCommandButton('save', $GLOBALS['lng']->txt('save'));
		return $form;
	}
	
	/**
	 * Read available mids
	 * @param ilViPLabSettings $settings
	 * @return type
	 */
	protected function readAvailabeMids(ilViPLabSettings $settings)
	{
		try
		{
			include_once './Services/WebServices/ECS/classes/class.ilECSCommunityReader.php';
			$reader = ilECSCommunityReader::getInstanceByServerId($settings->getECSServer());
			$participants = $reader->getParticipants();
			
		}
		catch(ilECSConnectorException $e)
		{
			ilUtil::sendFailure('Read from ecs server failed with message: '. $e);
		}

		$options = array();
		$options[0] = $GLOBALS['lng']->txt('select_one');
		
		foreach($participants as $mid => $part)
		{
			if($part->isSelf())
			{
				continue;
			}
			$options[$mid] = $part->getParticipantName().': '.$this->getPluginObject()->txt('ecs_mid').' '.$mid;
		}
		return $options;
	}
	
	/**
	 * Save settings
	 */
	protected function save()
	{
		$form = $this->initConfigurationForm();
		if($form->checkInput())
		{
			$this->getPluginObject()->includeClass('class.ilViPLabSettings.php');
			$settings = ilViPLabSettings::getInstance();
			
			$settings->setActive($form->getInput('active'));
			$settings->setECSServer($form->getInput('ecs'));
			$settings->setWidth($form->getInput('width'));
			$settings->setHeight($form->getInput('height'));
			
			$this->getPluginObject()->includeClass('class.ilViPLabUtil.php');
			
			$enabled_langs = array();
			foreach(ilViPLabUtil::getAvailableLanguages() as $lng_key)
			{
				$mid = $form->getInput('plang_'.$lng_key);
				if($mid)
				{
					$enabled_langs[$lng_key] = $mid;
				}
			}
			$settings->setLanguages($enabled_langs);
			$settings->update();
			
			ilUtil::sendSuccess($GLOBALS['lng']->txt('settings_saved'),TRUE);
			$GLOBALS['ilCtrl']->redirect($this,'configure');
			return TRUE;
		}
		
		ilUtil::sendFailure($GLOBALS['lng']->txt('err_check_input'),TRUE);
		$GLOBALS['ilCtrl']->redirect($this,'configure');
		return TRUE;
	}
}
?>