<?php

namespace Winged\Form\Components;

use Winged\Components\ComponentParser;

/**
 * Class RadioComponent
 * @package Winged\Form\Components
 */
class RadioComponent extends ComponentParser{

    /**
     * @param $args
     */
    public function parser($args){
        extract($args);
        /**
         * @var $property string
         * @var $class string
         * @var $obj Model
         * @var $inputOptions array
         * @var $elementOptions array
         * @var $isArray bool
         */
        $classEnd = explode('\\', $class);
        $classEnd = end($classEnd);
        $this->reset();
        if (($data = array_key_exists_check('values', $inputOptions))) {
            $count = 0;
            if (is_array($data) && !empty($data)) {
                foreach ($data as $key => $cla) {
                    $selected = '';
                    if ($obj->requestKey($property) !== null) {
                        if ($key == $obj->requestKey($property)) {
                            $selected = ' checked="checked"';
                        }
                    }
                    if (is_array($obj->{$property})) {
                        if (in_array($key, $obj->{$property})) {
                            $selected = ' checked="checked"';
                        }
                    }
                    if ($isArray) {
                        $isArray = '[]';
                    }
                    $this->DOM
                        ->query('.radio')
                        ->append('<div>
                                              <input name="' . $class . '[' . $property . ']' . $isArray . '" id="' . $classEnd . '_' . $property . '_' . $count . '" value="' . $key . '" type="radio" ' . $selected . '>
                                              <label for="' . $classEnd . '_' . $property . '_' . $count . '">
                                                  <span></span>' . $cla . '
                                              </label>
                                          </div>');
                    $count++;
                }
            }
        }

        if (method_exists($class, 'labels')) {
            $labels = $obj->labels();
            if (($text = array_key_exists_check($property, $labels)) !== false) {
                $this->DOM->query('label')[0]->text($text);
            }
        }

        $this->addOptions($this->DOM->query('html *')[0], $elementOptions);
    }

    public function reset(){
        $reset = \pQuery::parseStr($this->original->html());
        $this->DOM = $reset;
    }
}