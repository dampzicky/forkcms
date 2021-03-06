<?php

namespace Install\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Model as BackendModel;

/**
 * Step 4 of the Fork installer
 *
 * @author Davy Hellemans <davy@netlash.com>
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 * @author Matthias Mullie <forkcms@mullie.eu>
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Step4 extends Step
{
    /**
     * Executes this step.
     */
    public function execute()
    {
        $this->loadModules();
        $this->loadForm();
        $this->validateForm();
        $this->parseForm();
    }

    /**
     * Is this step allowed.
     *
     * @return bool
     */
    public static function isAllowed()
    {
        return Step3::isAllowed() && isset($_SESSION['default_language']) &&
               isset($_SESSION['default_interface_language']) &&
               isset($_SESSION['multiple_languages']) && isset($_SESSION['languages']) &&
               isset($_SESSION['interface_languages']);
    }

    /**
     * Loads the form.
     */
    private function loadForm()
    {
        // init var
        $modules = array();
        $checkedModules = (\SpoonSession::exists('modules')) ? \SpoonSession::get('modules') : array();

        // loop required modules
        foreach ($this->modules['required'] as $module) {
            // add to the list
            $modules[] = array(
                'label' => \SpoonFilter::toCamelCase($module),
                'value' => $module,
                'attributes' => array('disabled' => 'disabled')
            );

            // update $_POST if needed
            if (!isset($_POST['modules']) || !is_array($_POST['modules']) ||
                !in_array($module, $_POST['modules'])
            ) {
                $_POST['modules'][] = $module;
            }
        }

        // loop optional modules
        foreach ($this->modules['optional'] as $module) {
            // add to the list
            $modules[] = array('label' => \SpoonFilter::toCamelCase($module), 'value' => $module);
        }

        // add multi checkbox
        $this->frm->addMultiCheckbox(
            'modules',
            $modules,
            array_unique(array_merge($this->modules['required'], $checkedModules))
        );

        // example data
        $this->frm->addCheckbox(
            'example_data',
            (\SpoonSession::exists('example_data') ? \SpoonSession::get('example_data') : true)
        );

        // debug mode
        $this->frm->addCheckbox(
            'debug_mode',
            (\SpoonSession::exists('debug_mode') ? \SpoonSession::get('debug_mode') : false)
        );

        // specific debug email address
        $this->frm->addCheckbox(
            'different_debug_email',
            (\SpoonSession::exists('different_debug_email') ? \SpoonSession::get('different_debug_email') : false)
        );

        // specific debug email address text
        $this->frm->addText(
            'debug_email',
            (\SpoonSession::exists('debug_email')) ? \SpoonSession::get('debug_email') : ''
        );
    }

    /**
     * Scans the directory structure for modules and adds them to the list of optional modules
     */
    private function loadModules()
    {
        // fetch modules
        $tmpModules = BackendModel::getModulesOnFilesystem(false);
        foreach ($tmpModules as $module) {
            // not required nor hidden
            if (!in_array($module, $this->modules['required']) && !in_array($module, $this->modules['hidden'])) {
                // add to the list of optional installs
                $this->modules['optional'][] = $module;
            }
        }
    }

    /**
     * Validate the form based on the variables in $_POST
     */
    private function validateForm()
    {
        // form submitted
        if ($this->frm->isSubmitted()) {
            // validate email address
            if ($this->frm->getField('different_debug_email')->isChecked()) {
                $this->frm->getField(
                    'debug_email'
                )->isEmail('Please provide a valid e-mail address.');
            }

            // all valid
            if ($this->frm->isCorrect()) {
                // get selected modules
                $modules = $this->frm->getField('modules')->getValue();

                // add blog if example data was checked
                if ($this->frm->getField('example_data')->getChecked() &&
                    !in_array('blog', $modules)
                ) {
                    $modules[] = 'blog';
                }

                // set modules and default data
                \SpoonSession::set('modules', $modules);
                \SpoonSession::set(
                    'example_data',
                    $this->frm->getField('example_data')->getChecked()
                );

                // debug data
                \SpoonSession::set(
                    'debug_mode',
                    $this->frm->getField('debug_mode')->getChecked()
                );
                \SpoonSession::set(
                    'different_debug_email',
                    $this->frm->getField('different_debug_email')->getChecked()
                );
                \SpoonSession::set(
                    'debug_email',
                    $this->frm->getField('debug_email')->getValue()
                );

                // redirect
                \SpoonHTTP::redirect('/install?step=5');
            }
        }
    }
}
