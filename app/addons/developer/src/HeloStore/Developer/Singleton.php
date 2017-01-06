<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */

namespace HeloStore\Developer;

use Tygh\CompanySingleton;

class Singleton extends CompanySingleton
{
    /**
     * @var array
     */
    protected $errors = array();
    /**
     * @var array
     */
    protected $results = array();

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add a new error
     * 
     * @param $error
     */
    public function addError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Add a new errors
     *
     * @param $errors
     */
    public function addErrors($errors)
    {
        $this->errors += $errors;
    }

    /**
     * Check if any errors present
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (!empty($this->errors));
    }

    /**
     * Get results
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Add a new result
     *
     * @param $key
     * @param $message
     */
    public function addResult($key, $message)
    {
        $this->results[$key] = $message;
    }

    /**
     * Check if any results present
     *
     * @return bool
     */
    public function hasResults()
    {
        return (!empty($this->results));
    }

    /**
     * @param int $company_id
     * @param array $params
     *
     * @return static
     */
    public static function instance($company_id = 0, $params = array())
    {
        return parent::instance($company_id, $params);
    }

}