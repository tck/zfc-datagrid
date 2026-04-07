<?php
namespace ZfcDatagrid\Column\Style;

use function is_array;
use function implode;

/**
 * Css class for the row/cell.
 */
class CSSClass extends AbstractStyle
{
    /**
     * @param string|array $class
     */
    public function __construct(private $class)
    {
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return is_array($this->class) ? implode(' ', $this->class) : $this->class;
    }

    /**
     * @param string|array $class
     *
     * @return $this
     */
    public function setClass($class): self
    {
        $this->class = $class;

        return $this;
    }
}
