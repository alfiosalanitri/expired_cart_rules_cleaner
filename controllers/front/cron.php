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
class expired_cart_rules_cleanercronModuleFrontController extends ModuleFrontController
{

    /**
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();
        try {
            if(!Tools::getValue('token'))
                throw new Exception('Missing token!');
            if( Tools::getValue('token') !== $this->module->getCronjobsToken())
                throw new \Exception('Invalid token!');

            die($this->deleteExpiredCartRulesFromCarts());
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Loop through all expired cart rules and remove them from the database.
     *
     * @return string
     * @throws Exception
     */
    function deleteExpiredCartRulesFromCarts()
    {
        $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $cartRules = $this->getExpiredCartRules($db);

        if(empty($cartRules))
            throw new Exception('All good, there are no expired or deactivated cart rules in the abandoned carts.');

        $todo = count($cartRules);
        $done = 0;
        foreach ($cartRules as $cartRule) {
            $idCart = $cartRule['id_cart'];
            $idCartRule = $cartRule['id_cart_rule'];
            $delete = $db->delete('cart_cart_rule', "id_cart_rule = '$idCartRule' AND id_cart = '$idCart'");
            if($delete) {
                $done++;
            }
        }
        $message = sprintf('%d expired cart rules have been removed from the abandoned carts.', $done);
        if($todo != $done) {
            $message = sprintf('Sorry, there were %d expired cart rules to remove, but only %d were removed.', $todo, $done);
        }
        return $message;
    }

    /**
     * Fetch from the database the cart rules that are either expired or deactivated and associated with carts that have no orders yet.
     *
     * @param Db $db
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    protected function getExpiredCartRules(Db $db)
    {
        $request = 'SELECT cr.* FROM `' . _DB_PREFIX_ . 'cart_cart_rule` AS cr';
        $request .= ' LEFT JOIN `' . _DB_PREFIX_ . 'orders` AS o ON o.id_cart = cr.id_cart';
        $request .= ' WHERE o.id_order IS NULL AND cr.id_cart_rule IN
        ( SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE date_to < NOW() OR active = 0 );';
        return $db->executeS($request);
    }
}