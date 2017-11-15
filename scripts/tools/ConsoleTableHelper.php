<?php
/**
 * Copyright (c) 2017 Open Assessment Technologies, S.A.
 *
 * @author A.Zagovorichev, <zagovorichev@1pt.com>
 */


namespace oat\taoDeliveryRdf\scripts\tools;


class ConsoleTableHelper
{
    /**
     * columns of the header for the table
     * not required
     * @var array
     */
    private $header = [];
    /**
     * rows of the table with data to show
     * @var array
     */
    private $table = [];
    /**
     * expected count of the columns for this table
     * @var int
     */
    private $columns = 0;
    /**
     * Max length of the column content for each column
     * indent included
     * @var array
     */
    private $columnSizer = [];
    /**
     * Intends between data and border (to make it more readable)
     * @var int
     */
    private $indent = 5;

    public function addHeader(array $row)
    {
        if (!count($row)) {
            throw new \Exception('Row can not be empty');
        }

        if ($this->columns && $this->columns != count($row)) {
            throw new \Exception('Table should have ' . $this->columns . 'column(s)');
        } else {
            $this->columns = count($row);
        }

        $this->header = $row;
        $this->recountColSizer($row);
    }

    public function addRow(array $row)
    {
        if (!count($row)) {
            throw new \Exception('Row can not be empty');
        }

        if ($this->columns != count($row)) {
            throw new \Exception('Table should have ' . $this->columns . 'column(s)');
        } else {
            $this->columns = count($row);
        }

        $this->table[] = $row;
        $this->recountColSizer($row);
    }

    public function addRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }

    /**
     * @return \common_report_Report
     */
    public function generateReport()
    {
        $tableReport = \common_report_Report::createInfo('');
        $tableReport->add(\common_report_Report::createInfo($this->generateTopRow()));
        foreach ($this->generateTableRows([$this->header]) as $row) {
            $tableReport->add(\common_report_Report::createInfo($row));
        }
        $tableReport->add(\common_report_Report::createInfo($this->generateTableRowSeparator()));
        foreach ($this->generateTableRows($this->table) as $row) {
            $tableReport->add(\common_report_Report::createInfo($row));
        }
        $tableReport->add(\common_report_Report::createInfo($this->generateBottomRow()));

        return $tableReport;
    }

    private function recountColSizer(array $row) {
        foreach ($row as $key => $val) {
            $length = mb_strlen($val) + $this->indent*2;
            if (!isset($this->columnSizer[$key]) || $this->columnSizer[$key] < $length) {
                $this->columnSizer[$key] = $length;
            }
        }
    }

    private function generateTopRow()
    {
        $row = '╔';
        foreach ($this->columnSizer as $key => $size) {
            if ($key) {
                $row .= '╤';
            }
            for ($i=0; $i<$size; $i++) {
                $row .= '═';
            }
        }
        $row .= '╗';
        return $row;
    }

    private function generateBottomRow()
    {
        $row = '╚';
        foreach ($this->columnSizer as $key => $size) {
            if ($key) {
                $row .= '╧';
            }
            for ($i=0; $i<$size; $i++) {
                $row .= '═';
            }
        }
        $row .= '╝';
        return $row;
    }

    private function generateTableRowSeparator()
    {
        $row = '╟';
        foreach ($this->columnSizer as $key => $size) {
            if ($key) {
                $row .= '┽';
            }
            for ($i=0; $i<$size; $i++) {
                $row .= '─';
            }
        }
        $row .= '╢';
        return $row;
    }

    private function generateTableRows(array $rows)
    {
        $tbl = [];
        foreach ($rows as $row) {
            $str = '║';
            foreach ($row as $key => $val) {
                if ($key) {
                    $str .= '│';
                }
                $str .= str_pad('', $this->indent) . $val;
                $length = mb_strlen($val);
                if ($length + $this->indent < $this->columnSizer[$key]) {
                    $str .= str_pad('', $this->columnSizer[$key] - $length - $this->indent*2);
                }
                $str .= str_pad('', $this->indent);
            }
            $str .= '║';
            $tbl[] = $str;
        }

        return $tbl;
    }
}
