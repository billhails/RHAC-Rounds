<?php

class Wrapper
{
    protected $body;

    protected function formatBody()
    {
        return implode(
            '',
            array_map(
                function($item) {
                    return "$item";
                },
                $this->body
            )
        );
    }

    public function __toString()
    {
        return $this->formatBody();
    }
}

class HTML extends Wrapper
{
    private $tag;
    private $attrs;

    public function __construct($tag, $args)
    {
        $this->tag = $tag;
        $this->attrs = array();
        $this->body = array();
        if (count($args)) {
            if (is_array($args[0])) {
                $this->attrs = $args[0];
            }
            array_shift($args);
        }
        if (count($args)) {
            $this->body = $args;
        }
    }

    public function __toString()
    {
        return $this->openTag()
            . $this->formatAttrs()
            . $this->closeOpeningTag()
            . $this->formatBody()
            . $this->closeTag();
    }

    private function openTag()
    {
        return '<' . $this->tag;
    }

    private function formatAttrs()
    {
        return implode(
            '',
            array_map(
                function ($name, $value) {
                    return " $name='$value'";
                },
                array_keys($this->attrs),
                array_values($this->attrs)
            )
        );
    }

    private function closeOpeningTag()
    {
        if (count($this->body)) {
            return '>';
        } else {
            return '';
        }
    }

    private function closeTag()
    {
        if (count($this->body)) {
            return '</' . $this->tag . '>';
        } else {
            return ' />';
        }
    }
}

function _w() { return new Wrapper(func_get_args()); }
function _nbsp() { return '&nbsp;'; }
function _table() { return new HTML('table', func_get_args()); }
function _thead() { return new HTML('thead', func_get_args()); }
function _tfooter() { return new HTML('tfooter', func_get_args()); }
function _tr() { return new HTML('tr', func_get_args()); }
function _th() { return new HTML('th', func_get_args()); }
function _td() { return new HTML('td', func_get_args()); }
function _p() { return new HTML('p', func_get_args()); }
