<?php

namespace Winged\Form;

class HtmlHelper
{

    public $_html = null;

    public function completeAnyHtml(\phpQueryObject $html, $options)
    {
        if (($type = array_key_exists_check('type', $options)) !== false) {
            $html->attr('type', $type);
        }

        if (($placeholder = array_key_exists_check('placeholder', $options)) !== false) {
            $html->attr('placeholder', $placeholder);
        }

        if (($disable = array_key_exists_check('disable', $options)) !== false) {
            $html->attr('disable', $disable);
        }

        if (($autocomplete = array_key_exists_check('autocomplete', $options)) !== false) {
            $html->attr('autocomplete', $autocomplete);
        }

        if (($value = array_key_exists_check('value', $options)) !== false) {
            $html->attr('value', $value);
        }

        if (($id = array_key_exists_check('id', $options)) !== false) {
            $html->attr('id', $id);
        }

        if (($attrs = array_key_exists_check('attrs', $options)) !== false) {
            if (is_array($attrs) && !empty($attrs)) {
                foreach ($attrs as $attr => $value) {
                    if ($value) {
                        $html->attr($attr, $value);
                    }
                }
            }
        }

        if (($class = array_key_exists_check('class', $options)) !== false) {
            if (is_array($class) && !empty($class)) {
                foreach ($class as $cla) {
                    $html->addClass($cla);
                }
            }
        }

        if ($data = array_key_exists_check('data', $options)) {
            if (is_array($data) && !empty($data)) {
                foreach ($data as $key => $cla) {
                    $html->attr($key, $cla);
                }
            }
        }

        if ($selectors = array_key_exists_check('selectors', $options)) {
            if (is_array($selectors)) {
                foreach ($selectors as $selector => $function) {
                    if (is_array($function)) {
                        $keys = array_keys($function);
                        $method = array_shift($keys);
                        if ($method === 0) {
                            if (method_exists($html, $function[$method])) {
                                $html->find($selector)->{$function[$method]}();
                            }
                        } else {
                            if (method_exists($html, $method)) {
                                $html->find($selector)->{$method}($function[$method]);
                            }
                        }
                        $clone = $html->find($selector)->clone();
                        $clone = $this->completeAnyHtml($clone, $function);
                        $html->find($selector)->replaceWith($clone);
                    }
                }
            }
        }

        return $html;

    }

    public function completeAnyHtmlPrintable($html, $options)
    {

        $closed_tag = false;

        if ($html[strlen($html) - 1] == '>') {
            $html[strlen($html) - 1] = ' ';
        }

        if ($html[strlen($html) - 1] == '>' && $html[strlen($html) - 2] == '/') {
            $html[strlen($html) - 1] = ' ';
            $html[strlen($html) - 2] = ' ';
            $closed_tag = true;
        }

        $html = trim($html);

        if (($type = array_key_exists_check('type', $options)) !== false) {
            $html .= ' type="' . $type . '"';
        }

        if (($placeholder = array_key_exists_check('placeholder', $options)) !== false) {
            $html .= ' placeholder="' . $placeholder . '"';
        }

        if (($disable = array_key_exists_check('disable', $options)) !== false) {
            $html .= ' disable="' . $disable . '"';
        }

        if (($autocomplete = array_key_exists_check('autocomplete', $options)) !== false) {
            $html .= ' autocomplete="' . $autocomplete . '"';
        }

        if (($value = array_key_exists_check('value', $options)) !== false) {
            $html .= ' value="' . $value . '"';
        }

        if (($id = array_key_exists_check('id', $options)) !== false) {
            $html .= ' id="' . $id . '"';
        }

        if (($attrs = array_key_exists_check('attrs', $options)) !== false) {
            if (is_array($attrs)) {
                foreach ($attrs as $attr => $value) {
                    if ($value) {
                        $html .= ' ' . $attr . '="' . $value . '"';
                    }
                }
            }
        }

        $first = true;

        if ($class = array_key_exists_check('class', $options)) {
            if (is_array($class) && !empty($class)) {
                $html .= ' class="';
                foreach ($class as $cla) {
                    if ($first) {
                        $html .= $cla;
                        $first = false;
                    } else {
                        $html .= ' ' . $cla;
                    }
                }
                $html .= '"';
            }
        }

        if ($data = array_key_exists_check('data', $options)) {
            if (is_array($data) && !empty($data)) {
                foreach ($data as $key => $cla) {
                    $html .= ' ' . $key . '="' . $cla . '"';
                }
            }
        }

        if ($closed_tag) {
            $html .= '/>';
        } else {
            $html .= '>';
        }

        return $html;

    }

    public function completeMainHtml(\phpQueryObject $html, $options, $main)
    {
        if (($type = array_key_exists_check('type', $options)) !== false) {
            $html->find($main['main_selector'])->attr('type', $type);
        }

        if (($placeholder = array_key_exists_check('placeholder', $options)) !== false) {
            $html->find($main['main_selector'])->attr('placeholder', $placeholder);
        }

        if (($disable = array_key_exists_check('disable', $options)) !== false) {
            $html->find($main['main_selector'])->attr('disable', $disable);
        }

        if (($autocomplete = array_key_exists_check('autocomplete', $options)) !== false) {
            $html->find($main['main_selector'])->attr('autocomplete', $autocomplete);
        }

        if (($value = array_key_exists_check('value', $options)) !== false) {
            $html->find($main['main_selector'])->attr('value', $value);
        }

        if (($id = array_key_exists_check('id', $options)) !== false) {
            $html->find($main['main_selector'])->attr('id', $id);
        }

        if (($attrs = array_key_exists_check('attrs', $options)) !== false) {
            if (is_array($attrs)) {
                foreach ($attrs as $attr => $value) {
                    if ($value) {
                        $html->find($main['main_selector'])->attr($attr, $value);
                    }
                }
            }
        }

        if ($class = array_key_exists_check('class', $options)) {
            if (is_array($class) && !empty($class)) {
                foreach ($class as $cla) {
                    $html->find($main['main_selector'])->addClass($cla);
                }
            }
        }

        if ($data = array_key_exists_check('data', $options)) {
            if (is_array($data) && !empty($data)) {
                foreach ($data as $key => $cla) {
                    $html->find($main['main_selector'])->attr($key, $cla);
                }
            }
        }

        if ($selectors = array_key_exists_check('selectors', $options)) {
            if (is_array($selectors)) {
                foreach ($selectors as $selector => $function) {
                    if (is_array($function)) {
                        $keys = array_keys($function);
                        $method = array_shift($keys);
                        if ($method === 0) {
                            if (method_exists($html, $function[$method])) {
                                $html->find($selector)->{$function[$method]}();
                            }
                        } else {
                            if (method_exists($html, $method)) {
                                $html->find($selector)->{$method}($function[$method]);
                            }
                        }
                        $clone = $html->find($selector)->clone();
                        $clone = $this->completeAnyHtml($clone, $function);
                        $html->find($selector)->replaceWith($clone);
                    }
                }
            }
        }

        return $html;

    }

    public function html($property, $options = [], $element_options = [], $is_array = false, $force_return = false)
    {

        $continue = false;
        $main = false;
        $html = false;

        if (property_exists($this->class, $property) || $property == null || $property === false) {
            if ($html_name = array_key_exists_check('document', $options)) {
                $html_name = strtolower($html_name);
                if (array_key_exists($html_name, FormHtmlStore::$ELEMENTS)) {
                    $main = FormHtmlStore::$ELEMENTS[$html_name];
                    if ($html = array_key_exists_check('html', FormHtmlStore::$ELEMENTS[$html_name])) {
                        $continue = true;
                        if (property_exists($this->class, $html_name)) {
                            $html = $this->obj->{$html_name};
                        }
                    }
                }
            }
        }

        if ($continue) {

            $html = \phpQuery::newDocument($html);

            if ($property) {
                if ($main['main'] == 'select') {
                    if ($is_array) {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . '][]');
                    } else {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . ']');
                    }
                    $html->find($main['main_selector'])->attr('id', $this->class . '_' . $property);
                    $html->find($main['main_selector'])->parent()->parent()->find('label')->attr('for', $this->class . '_' . $property);

                    $html = $this->completeMainHtml($html, $options, $main);

                    if ($data = array_key_exists_check('options', $options)) {
                        if (is_array($data) && !empty($data)) {
                            foreach ($data as $key => $cla) {
                                $selected = '';
                                if ($key == $this->obj->{$property}) {
                                    $selected = ' selected="selected"';
                                }
                                $html->find($main['main_selector'])->append('<option value="' . $key . '"' . $selected . '>' . $cla . '</option>');
                            }
                        }
                    }
                }

                if ($main['main'] == 'input') {
                    if ($is_array) {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . '][]');
                    } else {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . ']');
                    }

                    $html->find($main['main_selector'])->attr('id', $this->class . '_' . $property);
                    $html->find($main['main_selector'])->attr('value', $this->obj->requestKey($property));
                    $html->find('label:first-child')->attr('for', $this->class . '_' . $property);

                    $html = $this->completeMainHtml($html, $options, $main);
                }

                if ($main['main'] == 'checkbox' || $main['main'] == 'radio') {
                    if ($data = array_key_exists_check('values', $options)) {
                        $count = 0;
                        if (is_array($data) && !empty($data)) {
                            foreach ($data as $key => $cla) {

                                $selected = '';
                                if ($this->obj->requestKey($property) !== null) {
                                    if ($key == $this->obj->requestKey($property)) {
                                        $selected = ' checked="checked"';
                                    }
                                }

                                if (is_array($this->obj->{$property})) {
                                    if (in_array($key, $this->obj->{$property})) {
                                        $selected = ' checked="checked"';
                                    }
                                }

                                if ($is_array) {
                                    $is_array = '[]';
                                }

                                $html
                                    ->find($main['main_selector'])
                                    ->append('<div>
                                              <input name="' . $this->class . '[' . $property . ']' . $is_array . '" id="' . $this->class . '_' . $property . '_' . $count . '" value="' . $key . '" type="' . $main['main'] . '" ' . $selected . '>
                                              <label for="' . $this->class . '_' . $property . '_' . $count . '">
                                                  <span></span>' . $cla . '
                                              </label>
                                          </div>');
                                $count++;
                            }
                        }
                    }
                    $html = $this->completeMainHtml($html, $options, $main);
                }

                if ($main['main'] == 'textarea') {
                    if ($is_array) {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . '][]');
                    } else {
                        $html->find($main['main_selector'])->attr('name', $this->class . '[' . $property . ']');
                    }
                    $html->find($main['main_selector'])->attr('id', $this->class . '_' . $property);
                    if (array_key_exists('value', $options)) {
                        $html->find($main['main_selector'])->html($options['value']);
                    } else {
                        $html->find($main['main_selector'])->html($this->obj->requestKey($property));
                    }
                    $html->find('label:first-child')->attr('for', $this->class . '_' . $property);
                    $html = $this->completeMainHtml($html, $options, $main);
                }


                if (!empty($element_options)) {
                    $this->completeAnyHtml($html->children()->eq(0), $element_options);
                }


                if (method_exists($this->class, 'labels')) {
                    $labels = $this->obj->labels();
                    if (($text = array_key_exists_check($property, $labels)) !== false) {
                        $html->find('label')->eq(0)->text($text);
                    }
                }

                if ($this->obj->hasErrors()) {
                    $errors = $this->obj->getErros();
                    $error = array_key_exists_check($property, $errors);
                    if ($error) {
                        if (is_array($error)) {
                            $error = array_shift($error);
                        }
                        $html->find('label.error')->attr('style', 'display: block;');
                        $html->find('label.error')->attr('for', $this->class . '_' . $property);
                        $html->find('label.error')->attr('id', $this->class . '_' . $property . '_error');
                        $html->find('label.error')->text($error);
                    }
                }

            } else {
                if ($main['main'] == 'button') {
                    $html = $this->completeMainHtml($html, $options, $main);
                }
            }

            if ($this->_html != null && !$force_return) {
                $this->_html->find('form')->append($html);
                return $this;
            } else {
                if ($force_return) {
                    return $html->markup();
                }
                echo $html->markup();
            }
        }
        return false;
    }
}