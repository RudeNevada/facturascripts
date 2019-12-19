<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Core\Lib\Accounting\AccountingClosingClosing;
use FacturaScripts\Core\Lib\Accounting\AccountingClosingOpening;
use FacturaScripts\Core\Lib\Accounting\AccountingClosingRegularization;

/**
 * Class that performs accounting closures
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ClosingToAcounting
{

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     *
     * @var Ejercicio
     */
    protected $exercise;

    /**
     *
     * @var int
     */
    protected $journalClosing;

    /**
     *
     * @var int
     */
    protected $journalOpening;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }
    }

    public function delete($exercise, $closing = true, $opening = true): bool
    {
        $this->exercise = $exercise;
        try {
            self::$dataBase->beginTransaction();
            if ($closing && !($this->deleteRegularization() && $this->deleteClosing())) {
                return false;
            }

            if ($opening && !$this->deleteOpening()) {
                return false;
            }
            self::$dataBase->commit();
        } finally {
            $result = !self::$dataBase->inTransaction();
            if ($result == false) {
                self::$dataBase->rollback();
            }
        }

        return $result;
    }

    /**
     * Execute the main process of regularization, closing and opening
     * of accounts.
     *
     * @param Ejercicio $exercise
     * @param array     $data
     * @return bool
     */
    public function exec($exercise, $data): bool
    {
        $this->exercise = $exercise;
        $this->journalClosing = $data['journalClosing'] ?? 0;
        $this->journalOpening = $data['journalOpening'] ?? 0;

        try {
            self::$dataBase->beginTransaction();
            if ($this->execRegularization() && $this->execClosing() && $this->execOpening()) {
                self::$dataBase->commit();
            }
        } finally {
            $result = !self::$dataBase->inTransaction();
            if ($result == false) {
                self::$dataBase->rollback();
            }
        }

        return $result;
    }

    /**
     * Delete closing accounting entry
     *
     * @return bool
     */
    protected function deleteClosing(): bool
    {
        $closing = new AccountingClosingClosing();
        return $closing->delete($this->exercise);
    }

    /**
     * Delete opening accounting entry
     *
     * @return bool
     */
    protected function deleteOpening(): bool
    {
        $opening = new AccountingClosingOpening();
        return $opening->delete($this->exercise);
    }

    /**
     * Delete regularization accounting entry
     *
     * @return bool
     */
    protected function deleteRegularization(): bool
    {
        $regularization = new AccountingClosingRegularization();
        return $regularization->delete($this->exercise);
    }

    /**
     * Execute account closing
     *
     * @return bool
     */
    protected function execClosing(): bool
    {
        $closing = new AccountingClosingClosing();
        return $closing->exec($this->exercise, $this->journalClosing);
    }

    /**
     * Execute account opening
     *
     * @return bool
     */
    protected function execOpening(): bool
    {
        $opening = new AccountingClosingOpening();
        return $opening->exec($this->exercise, $this->journalOpening);
    }

    /**
     * Execute account regularization
     *
     * @return bool
     */
    protected function execRegularization(): bool
    {
        $regularization = new AccountingClosingRegularization();
        return $regularization->exec($this->exercise, $this->journalClosing);
    }
}
