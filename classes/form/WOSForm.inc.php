<?php

/**
 * @file plugins/generic/webOfScience/classes/form/WOSForm.inc.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSForm
 * @ingroup plugins_generic_webOfScience
 *
 * @brief Plugin settings: connect to a Web of Science Network
 */

import('lib.pkp.classes.form.Form');

class WOSForm extends Form {

    /** @var $_plugin WebOfSciencePlugin */
    var $_plugin;

    /** @var $_journalId int */
    var $_journalId;

    /**
     * Constructor.
     * @param $plugin WebOfSciencePlugin
     * @param $journalId int
     * @see Form::Form()
     */
    function __construct(&$plugin, $journalId) {
        $this->_plugin =& $plugin;
        $this->_journalId = $journalId;

        parent::__construct($plugin->getTemplatePath() . 'wosSettingsForm.tpl');
        $this->addCheck(new FormValidator($this, 'auth_token', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrrs.settings.authTokenRequired'));
        $this->addCheck(new FormValidator($this, 'auth_key', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrrs.settings.journalTokenRequired'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @see Form::initData()
     */
    function initData() {
        $plugin =& $this->_plugin;
        $journalId = $this->_journalId;
        // Initialize from plugin settings
        $this->setData('auth_token', $plugin->getSetting($journalId, 'auth_token'));
        $this->setData('auth_key', $plugin->getSetting($journalId, 'auth_key'));
    }

    /**
     * @see Form::readInputData()
     */
    function readInputData() {
        $this->readUserVars(array('auth_token', 'auth_key'));
    }

    /**
     * Fetch the form.
     * @copydoc Form::fetch()
     */
    function fetch($request, $template = null, $display = false) {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @see Form::execute()
     */
    function execute() {
        $plugin =& $this->_plugin;
        $plugin->updateSetting($this->_journalId, 'auth_token', $this->getData('auth_token') , 'string');
        $plugin->updateSetting($this->_journalId, 'auth_key', $this->getData('auth_key'), 'string');
        $request = Application::get()->getRequest();
        $currentUser = $request->getUser();
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('plugins.generic.wosrrs.notifications.settingsUpdated')));
    }


}
?>
