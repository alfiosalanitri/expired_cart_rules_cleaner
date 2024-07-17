<?php
/*
 * MIT License
 *
 * Copyright (C) 2024 Alfio Salanitri
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    Alfio Salanitri <www.alfiosalanitri.it>
 * @copyright Since 2024
 * @license   MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 *
 */
class Expired_cart_rules_cleaner extends Module
{
    /**
     * @var bool|string
     */
    protected $cronjob_token = false;

    /**
     *
     * There is an unresolved bug in PrestaShop (at least up to version 8.1.2. https://github.com/PrestaShop/PrestaShop/issues/19393)
     * that allows users who have entered a discount code in the cart before it expires to still make purchases with the discount
     * even after the code has expired or been deactivated, as long as the cart is still active.
     *
     * This module provides a URL that can be manually accessed or scheduled via a periodic cronjob
     * to remove discount codes from abandoned carts that have expired or been deactivated.
     *
     * Verified with PrestaShop versions 1.7.8.7 and 8.1.2
     */
    public function __construct()
    {
        $this->name = 'expired_cart_rules_cleaner';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alfio Salanitri (www.alfiosalanitri.it)';
        $this->need_instance = 0;
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = 'Expired Cart Rules Cleaner';
        $this->description = 'Remove expired or deactivated discount codes from abandoned carts via cronjob (see: https://github.com/PrestaShop/PrestaShop/issues/19393)';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->cronjob_token = \Tools::hash($this->name.'/cron');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * @return bool|string
     */
    public function getCronjobsToken()
    {
        return $this->cronjob_token;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->renderForm();
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderForm()
    {
        $cronLink = $this->context->link->getModuleLink($this->name, 'cron', array('token' => $this->getCronjobsToken()));
        $fields_form = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => 'Cronjob',
                        'icon' => 'icon-refresh'
                    ),
                    'message' => array(
                        ''
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => 'Cron',
                            'name' => 'CRON_INFO',
                            'desc' => 'Run this cron job daily (recommended at 00:15) or manually via this link: ' . sprintf('<a href="%s" target="_blank">%s</a>', $cronLink, $cronLink),
                            'disabled' => true
                        ),
                    )
                )
            )
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => array(
                'CRON_INFO' => '15 00 * * * curl -s -k "'.$cronLink.'" > /dev/null 2>&1'
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($fields_form);
    }
}
