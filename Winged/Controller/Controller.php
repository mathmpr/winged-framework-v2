<?php

namespace Winged\Controller;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Winged\Assets\Assets;
use Winged\Date\Date;
use Winged\File\File;
use Winged\Utils\RandomName;
use Winged\Utils\WingedLib;
use Winged\Winged;
use Winged\WingedConfig;
use Winged\Error\Error;
use Winged\Buffer\Buffer;

class Controller
{
    public static $CONTROLLERS_PATH = './controllers/';
    public static $MODELS_PATH = './models/';
    public static $VIEWS_PATH = './views/';
    public $controller_path = false;
    public $controller_name = false;
    public $action_name = false;
    public $query_params = [];
    public $method_args = [];
    public $controller_reset = false;
    public $body_class = null;
    public $html_class = null;
    private $js = [];
    private $css = [];
    private $first_render = true;
    private $remove_css = [];
    private $remove_js = [];
    private $tojson = [];
    private $callable = null;
    private $head_path = false;
    public $error_level = 0;
    public $vect = [];
    public $assets = null;
    public $appended_abstract_head_content = [];
    /**
     * @var $minify_cache null | File
     */
    public $minify_cache = null;

    public function __construct()
    {
        $this->assets = new Assets($this);
        $this->copy();
    }

    public function dynamic($key, $value)
    {
        $this->{$key} = $value;
        return $this;
    }

    public function virtualString($path = '')
    {
        $exp = explode('/', $path);
        if (count7($exp) >= 1 && $exp[0] != '') {
            Winged::$controller_page = $exp[0];
            if (isset($exp[1])) {
                Winged::$controller_action = $exp[1];
                array_splice($exp, 1, 1);
            } else {
                Winged::$controller_action = 'index';
            }
            array_splice($exp, 0, 1);
        }
        if (count7($exp) > 0) {
            Winged::$params = $exp;
        }
        return $this->find();
    }

    public function virtual($controller, $action = false, $uri = false)
    {
        Winged::$controller_page = $controller;
        if ($action === false) {
            Winged::$controller_action = 'index';
        } else {
            Winged::$controller_action = $action;
        }
        if (is_array($uri)) {
            Winged::$params = $uri;
        }
        return $this->find();
    }

    public function find()
    {
        if (!$this->reset()) {
            $this->controller_name = $this->getControllerName();
            if (WingedConfig::$PARENT_FOLDER_MVC) {
                self::$CONTROLLERS_PATH = Winged::$parent . WingedLib::dotslash(self::$CONTROLLERS_PATH) . '/';
                self::$MODELS_PATH = Winged::$parent . WingedLib::dotslash(self::$MODELS_PATH) . '/';
                self::$VIEWS_PATH = Winged::$parent . WingedLib::dotslash(self::$VIEWS_PATH) . '/';
            }
            if (file_exists(self::$CONTROLLERS_PATH) && is_directory(self::$CONTROLLERS_PATH)) {
                if (file_exists(self::$CONTROLLERS_PATH . $this->controller_name . '.php') && !is_directory(self::$CONTROLLERS_PATH . $this->controller_name . '.php')) {
                    $this->controller_path = self::$CONTROLLERS_PATH . $this->controller_name . '.php';
                    include_once $this->controller_path;
                    if (class_exists($this->controller_name)) {
                        $method = $this->getActionName();
                        $this->action_name = $method;
                        if (is_array(Winged::$controller_params)) {
                            if (in_array(Winged::$controller_action, Winged::$controller_params)) {
                                $key = array_search(Winged::$controller_action, Winged::$controller_params);
                                unset(Winged::$controller_params[$key]);
                            }
                            if (in_array(Winged::$controller_page, Winged::$controller_params)) {
                                $key = array_search(Winged::$controller_page, Winged::$controller_params);
                                unset(Winged::$controller_params[$key]);
                            }
                            Winged::$controller_params = array_values(Winged::$controller_params);
                        }
                        $obj = new $this->controller_name();
                        if (method_exists($obj, 'beforeAction')) {
                            $to_call = [];
                            $this->getGetArgs();
                            $reflect = new \ReflectionMethod($this->controller_name, 'beforeAction');
                            $apply = $reflect->getParameters();
                            if (!empty($apply)) {
                                foreach ($apply as $key => $value) {
                                    if (isset($this->method_args[$value->name])) {
                                        $to_call[] = $this->method_args[$value->name];
                                    }
                                }
                            }

                            $return = $reflect->invokeArgs($obj, [Winged::$controller_action]);
                            if ($return !== null) {
                                if (is_array($return)) {
                                    try {
                                        $json = json_encode($return);
                                        echo $json;
                                    } catch (\Exception $error) {
                                        return true;
                                    }
                                }
                                if (Error::exists() && WingedConfig::$CONTROLLER_DEBUG) {
                                    Error::display(__LINE__, __FILE__);
                                }
                                return true;
                            }
                        }
                        $to_call = [];
                        if (method_exists($obj, $method)) {
                            $this->getGetArgs();
                            $reflect = new \ReflectionMethod($this->controller_name, $method);
                            $apply = $reflect->getParameters();
                            $controller_warn = false;
                            if (!empty($apply)) {
                                foreach ($apply as $key => $value) {
                                    if (!isset($this->method_args[$value->name])) {
                                        $controller_warn = true;
                                        Error::push(__CLASS__, "Action '" . $method . "' requires parameter'" . $value->name . "'", __FILE__, __LINE__);
                                    } else {
                                        $to_call[] = $this->method_args[$value->name];
                                    }
                                }
                            }
                            if (!$controller_warn) {
                                if ($this->error_level == 1) {
                                    Error::clear();
                                }
                                $return = $reflect->invokeArgs($obj, $to_call);
                                if (is_array($return)) {
                                    try {
                                        $json = json_encode($return);
                                        echo $json;
                                    } catch (\Exception $error) {
                                        return true;
                                    }
                                }
                                if (Error::exists() && WingedConfig::$CONTROLLER_DEBUG) {
                                    Error::display(__LINE__, __FILE__);
                                }
                                return true;
                            }
                        } else {
                            if (method_exists($obj, 'whenActionNotFound')) {
                                $reflect = new \ReflectionMethod($this->controller_name, 'whenActionNotFound');
                                $apply = $reflect->getParameters();
                                if (!empty($apply)) {
                                    foreach ($apply as $key => $value) {
                                        if (isset($this->method_args[$value->name])) {
                                            $to_call[] = $this->method_args[$value->name];
                                        }
                                    }
                                }
                                $return = $reflect->invokeArgs($obj, [Winged::$controller_action]);
                                if (is_array($return)) {
                                    try {
                                        $json = json_encode($return);
                                        echo $json;
                                    } catch (\Exception $error) {
                                        return true;
                                    }
                                }
                                if (Error::exists() && WingedConfig::$CONTROLLER_DEBUG) {
                                    Error::display(__LINE__, __FILE__);
                                }
                                return true;
                            }
                        }
                    } else {
                        if (WingedConfig::$CONTROLLER_DEBUG) {
                            Error::push(__CLASS__, "Controller class '" . $this->controller_name . "' no exists in file '" . $this->controller_path . "'", __FILE__, __LINE__);
                        }
                    }
                }
                return false;
            }
            return false;
        }
        return true;
    }

    public function getQueryStringConcat($push = array())
    {
        $param = '';
        $fisrt = true;
        foreach ($this->method_args as $key => $value) {
            if ($fisrt) {
                $fisrt = false;
                if (array_key_exists($key, $push)) {
                    $param .= $key . '=' . $push[$key];
                    unset($push[$key]);
                } else {
                    $param .= $key . '=' . $value;
                }
            } else {
                if (array_key_exists($key, $push)) {
                    $param .= '&' . $key . '=' . $push[$key];
                    unset($push[$key]);
                } else {
                    $param .= '&' . $key . '=' . $value;
                }
            }
        }
        $fisrt = false;
        foreach ($push as $key => $value) {
            if ($fisrt) {
                $fisrt = false;
                $param .= $key . '=' . $value;
            } else {
                $param .= '&' . $key . '=' . $value;
            }
        }
        return $param;
    }

    private function reset()
    {
        $parent = Winged::$parent;
        $outher_conf = $parent . "config/config.php";
        if (file_exists($outher_conf) && !is_directory($outher_conf) && !$this->controller_reset) {
            include_once $outher_conf;
            mb_internal_encoding(WingedConfig::$INTERNAL_ENCODING);
            mb_http_output(WingedConfig::$OUTPUT_ENCODING);
            Error::clear();
            $this->controller_reset = true;
            Winged::start();
            return true;
        }
        return false;
    }

    public function allExists($vect)
    {
        $controller = false;
        if (array_key_exists('name', $vect)) {
            $controller = $this->getControllerName($vect['name']);
        }
        $action = false;
        if (array_key_exists('action', $vect)) {
            $action = $this->getActionName($vect['action']);
        }
        if ($action && $controller) {
            if (file_exists(self::$CONTROLLERS_PATH . $controller . '.php') && !is_directory(self::$CONTROLLERS_PATH . $controller . '.php')) {
                include_once self::$CONTROLLERS_PATH . $controller . '.php';
                $obj = new $controller();
                if (method_exists($obj, $action)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function loadModel($model = "", $root = false)
    {
        $model_str = $model;
        $model = explode('.', $model_str);
        if (end($model) != 'php') {
            $model_str .= '.php';
        }
        if ($root) {
            $path = './models/' . $model_str;
        } else {
            $path = self::$MODELS_PATH . $model_str;
        }
        if (file_exists($path)) {
            include_once($path);
        } else {
            Error::push(__CLASS__, "model in " . $path . " can't included because file not found.", __FILE__, __LINE__);
        }
    }

    private function getControllerName($name = '')
    {
        if ($name == '') {
            $name = Winged::$controller_page;
        }
        $exp = explode("-", str_replace(array('.', '_'), '-', $name));
        foreach ($exp as $index => $names) {
            $exp[$index] = ucfirst($exp[$index]);
        }
        return trim(implode('', $exp) . 'Controller');
    }

    private function getActionName($name = '')
    {
        if ($name == '') {
            $name = Winged::$controller_action;
        }
        $exp = explode("-", str_replace(array('.', '_'), '-', $name));
        foreach ($exp as $index => $names) {
            $exp[$index] = ucfirst($exp[$index]);
        }
        return trim('action' . implode('', $exp));
    }

    public function getGetArgs()
    {
        foreach ($_GET as $index => $value) {
            if (is_array($value)) {
                $this->method_args[$index] = $value;
            } else if ((intval($value) == 0 && $value == '0') || intval($value) > 0) {
                $this->method_args[$index] = (int)$value;
            } else {
                $this->method_args[$index] = $value;
            }
        }
        return;
    }

    public function redirectOnly($path = '')
    {
        header('Location: ' . $path);
    }

    public function redirectTo($path = '', $keep_args = true)
    {
        $args_path = explode('?', $path);
        $path = $args_path[0];
        $args = explode('?', server('request_uri'));
        $join = [];
        if (count7($args) >= 2 && $keep_args) {
            if (count7($args_path) >= 2) {
                $args = explode('&', end($args));
                $args_path = explode('&', end($args_path));
                foreach ($args_path as $arg) {
                    $from_redi = explode('=', $arg);
                    $key_redi = array_shift($from_redi);
                    $from_redi = [$key_redi => (count7($from_redi) > 1 ? implode('=', $from_redi) : end($from_redi))];
                    foreach ($args as $varg) {
                        $from_url = explode('=', $varg);
                        $key_url = array_shift($from_url);
                        $from_url = [$key_url => (count7($from_url) > 1 ? implode('=', $from_url) : end($from_url))];
                        if ($key_redi == $key_url) {
                            $join[$key_redi] = $from_redi[$key_redi];
                        } else {
                            $join[$key_url] = $from_url[$key_url];
                        }
                    }
                }
                $args = '';
                foreach ($join as $key => $value) {
                    $args .= $key . '=' . $value;
                }
            } else {
                $args = '?' . array_pop($args);
            }
        } else {
            if (count7($args_path) >= 2) {
                $args = end($args_path);
            } else {
                $args = '';
            }
        }
        if ($path != '') {
            if (endstr($path) != '/') {
                $path .= '/';
            }
        }
        if (trim($args) != '') {
            $args = '?' . $args;
        }
        if (WingedConfig::$PARENT_FOLDER_MVC) {
            $parent = WingedLib::dotslash(Winged::$parent);
            if ($parent == '') {
                header('Location: ' . Winged::$protocol . $path . $args);
            } else {
                header('Location: ' . Winged::$protocol . $parent . '/' . $path . $args);
            }
        } else {
            header('Location: ' . Winged::$protocol . $path . $args);
        }
        exit;
    }

    public function setNicknamesToUri($nicks = [])
    {
        $narr = [];
        if (Winged::$controller_params == false) {
            Winged::$controller_params = [];
        }
        if (count7($nicks) > count7(Winged::$controller_params)) {
            for ($x = 0; $x < count7($nicks); $x++) {
                if (array_key_exists($x, Winged::$controller_params)) {
                    $narr[$nicks[$x]] = Winged::$controller_params[$x];
                } else {
                    $narr[$nicks[$x]] = null;
                }
            }
        } else {
            for ($x = 0; $x < count7($nicks); $x++) {
                $narr[$nicks[$x]] = Winged::$controller_params[$x];
            }
        }
        Winged::$controller_params = $narr;
    }

    public function afterThis($callable)
    {
        return $this->afterAction($callable);
    }

    public function afterAction($callable)
    {
        if (is_callable($callable)) {
            $this->callable = $callable;
            return true;
        }
        return false;
    }

    public function copy()
    {
        if (Winged::$controller !== null) {
            Winged::$controller->getGetArgs();
            $this->controller_path = Winged::$controller->controller_path;
            $this->controller_name = Winged::$controller->controller_name;
            $this->query_params = Winged::$controller->query_params;
            $this->method_args = Winged::$controller->method_args;
            $this->action_name = Winged::$controller->action_name;
        }
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->method_args)) {
            return $this->method_args[$key];
        }
        return false;
    }

    public function params($key)
    {
        if (array_key_exists($key, $this->query_params)) {
            return $this->query_params[$key];
        }
        return false;
    }

    public function ignoreHeadContent()
    {
        $this->head_path = null;
    }

    public function renderHtml($path, $vars = [], $loads = [])
    {
        $path = $this->getViewFile($path);
        if (file_exists($path) && !is_directory($path)) {
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($loads as $key => $value) {
                if (file_exists($value) && !is_directory($value)) {
                    include_once $value;
                }
            }
            if ($this->first_render) {
                $this->first_render = false;
                Buffer::reset();
                include_once $path;
                $content = Buffer::get();
                Buffer::kill();
                Buffer::start();
                ?>
                <!doctype html>
                <html>
                <?php
                $this->pureHtml($content);
                ?>
                </html>
                <?php
                $content = Buffer::getKill();
                $rep = [];
                $match = false;
                $matchs = null;
                if (is_int(stripos($content, '<textarea'))) {
                    $match = preg_match_all('#<textarea(.*?)>(.*?)</textarea>#is', $content, $matchs);
                    if ($match) {
                        foreach ($matchs[0] as $match) {
                            $rep[] = '#___' . RandomName::generate('sisisisi') . '___#';
                        }
                        $content = str_replace($matchs[0], $rep, $content);
                    }
                }
                $content = preg_replace('#> <#', '><', preg_replace('# {2,}#', ' ', preg_replace('/[\n\r]|/', '', $content)));
                if ($match) {
                    $content = str_replace($rep, $matchs[0], $content);
                }
                if (WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS) {
                    if (is_array(WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS)) {
                        if (in_array('img', WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS)) {
                            $content = preg_replace_callback('#<img.+?src="([^"]*)".*?/?>#i', function ($found) {
                                return str_replace($found[1], $found[1] . '?get=' . RandomName::generate('sisisi'), $found[0]);
                            }, $content);
                        }
                        if (in_array('script', WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS)) {
                            $content = preg_replace_callback('#<script.+?src="([^"]*)".*?/?>#i', function ($found) {
                                return str_replace($found[1], $found[1] . '?get=' . RandomName::generate('sisisi'), $found[0]);
                            }, $content);
                        }
                        if (in_array('source', WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS)) {
                            $content = preg_replace_callback('#<source.+?src="([^"]*)".*?/?>#i', function ($found) {
                                return str_replace($found[1], $found[1] . '?get=' . RandomName::generate('sisisi'), $found[0]);
                            }, $content);
                        }
                        if (in_array('link', WingedConfig::$USE_UNICID_ON_INCLUDE_ASSETS)) {
                            $content = preg_replace_callback('#<link.+?href="([^"]*)".*?/?>#i', function ($found) {
                                return str_replace($found[1], $found[1] . '?get=' . RandomName::generate('sisisi'), $found[0]);
                            }, $content);
                        }
                    }
                }
                ?>
                <?= $content; ?>
                <?php
            } else {
                include_once $path;
            }
        } else {
            Error::push(__CLASS__, "file " . $path . " can't rendred because file not found.", __FILE__, __LINE__);
        }
    }

    private function getViewFile($path)
    {
        $path .= '.php';
        return self::$VIEWS_PATH . $path;
    }

    private function pureHtml($html_page)
    {

        $this->createCssMinify();
        $this->createJsMinify();

        if ($this->html_class != null) {
            Buffer::reset();
            ?>
            <!doctype html>
            <html class="<?= $this->html_class ?>">
            <?php
        }
        ?>
        <head>
            <?php
            $head_content_path = WingedConfig::$HEAD_CONTENT_PATH;
            if ($this->head_path !== false) {
                $head_content_path = $this->head_path;
            }
            if ($head_content_path !== null) {
                if (file_exists($head_content_path) && !is_directory($head_content_path)) {
                    include_once $head_content_path;
                }
                foreach ($this->appended_abstract_head_content as $head) {
                    ?>
                    <?= $head ?>
                    <?php
                }
            } else {
                ?>
                <meta charset="utf-8"/>
                <?php
            }

            foreach ($this->css as $identifier => $content) {
                if (!in_array($identifier, $this->remove_css)) {
                    if ($content['type'] === 'file') {
                        ?>
                        <link href="<?= $this->makeAssetsSrc($content['string']) ?>" type="text/css" rel="stylesheet"
                              charset="utf-8"/>
                        <?php
                    } else if ($content['type'] === 'script') {
                        ?>
                        <?= $content['string'] ?>
                        <?php
                    } else if ($content['type'] === 'url') {
                        ?>
                        <link href="<?= $content['string'] ?>" type="text/css" rel="stylesheet"
                              charset="utf-8"/>
                        <?php
                    }
                }
            }
            ?>
        </head>

        <body <?= $this->body_class !== null && $this->body_class !== false ? 'class="' . $this->body_class . '"' : '' ?>>
        <?= $html_page ?>
        </body>
        <?php
        foreach ($this->js as $identifier => $content) {
            if (!in_array($identifier, $this->remove_js)) {
                if ($content['type'] === 'file') {
                    ?>
                    <script src="<?= $this->makeAssetsSrc($content['string']) ?>" type="text/javascript"
                            charset="utf-8"></script>
                <?php
                } else if ($content['type'] === 'script') {
                    ?>
                    <?= $content['string'] ?>
                    <?php
                } else if ($content['type'] === 'url') {
                ?>
                    <script src="<?= $content['string'] ?>" type="text/javascript"
                            charset="utf-8"></script>
                    <?php
                }
            }
        }
    }

    private function createJsMinifyNew($path, $read)
    {
        $read[$path] = [
            'create_at' => Date::now()->timestamp(),
            'formed_with' => [],
            'cache_file' => './cache/js/' . RandomName::generate('sisisisi', true, false) . '.js'
        ];
        $cache_file = new File($read[$path]['cache_file']);
        $minify = new JS();
        foreach ($this->js as $identifier => $content) {
            if (!in_array($identifier, $this->remove_js)) {
                if ($content['type'] === 'file') {
                    $file = new File($content['string'], false);
                    if ($file->exists()) {
                        $minify->add($file->read());
                        $this->removeJs($identifier);
                        $read[$path]['formed_with'][$content['string']] = [
                            'time' => $file->modifyTime(),
                            'path' => $file->file_path,
                            'name' => $file->file,
                            'identifier' => $identifier,
                        ];
                    }
                }
            }
        }
        $cache_file->write($minify->minify());
        $this->js = array_merge([$path => ['string' => $cache_file->file_path, 'type' => 'file']], $this->js);
        $this->persistsMinifiedCacheFileInformation($read);
    }

    private function createJsMinify()
    {
        $this->createMinifiedCacheFileInformation();
        $read = $this->readMinifiedCacheFileInformation();
        $path = '';
        foreach ($this->js as $identifier => $content) {
            if (!in_array($identifier, $this->remove_js)) {
                if ($content['type'] === 'file') {
                    $path .= $content['string'];
                }
            }
            $path = md5($path);
        }
        if (WingedConfig::$AUTO_MINIFY) {
            if (!array_key_exists($path, $read) && $path !== '') {
                $this->createJsMinifyNew($path, $read);
            } else {
                if (array_key_exists($path, $read)) {
                    $check = $read[$path];
                    $renew = false;
                    foreach ($check['formed_with'] as $key => $former) {
                        $file = new File($key, false);
                        if (!$file->exists()) {
                            $renew = true;
                        } else {
                            if ($former['time'] != $file->modifyTime()) {
                                $renew = true;
                            }
                        }
                        if (!array_key_exists($former['identifier'], $this->js)) {
                            $renew = true;
                        }
                    }
                    if ((int)Date::now()->diff((new Date($check['create_at'])), ['i'])->minutes > (int)WingedConfig::$AUTO_MINIFY) {
                        $renew = true;
                    }
                    if ($renew) {
                        $old_cache_file = new File($read[$path]['cache_file']);
                        if ($old_cache_file->exists()) {
                            $old_cache_file->delete();
                        }
                        $this->createJsMinifyNew($path, $read);
                    } else {
                        foreach ($check['formed_with'] as $key => $former) {
                            $this->removeJs($former['identifier']);
                        }
                        $this->js = array_merge([$path => ['string' => $check['cache_file'], 'type' => 'file']], $this->js);
                    }
                }
            }
        } else {
            if (array_key_exists($path, $read)) {
                $check = $read[$path];
                foreach ($check['formed_with'] as $key => $former) {
                    $this->removeJs($former['identifier']);
                }
                $this->js = array_merge([$path => ['string' => $check['cache_file'], 'type' => 'file']], $this->js);
            }
        }
    }

    private function createCssMinifyNew($path, $read)
    {
        $read[$path] = [
            'create_at' => Date::now()->timestamp(),
            'formed_with' => [],
            'cache_file' => './cache/css/' . RandomName::generate('sisisisi', true, false) . '.css'
        ];
        $cache_file = new File($read[$path]['cache_file']);
        $minify = new CSS();
        foreach ($this->css as $identifier => $content) {
            if (!in_array($identifier, $this->remove_css)) {
                if ($content['type'] === 'file') {
                    $file = new File($content['string'], false);
                    if ($file->exists()) {
                        $minify->add($file->read());
                        $this->removeCss($identifier);
                        $read[$path]['formed_with'][$content['string']] = [
                            'time' => $file->modifyTime(),
                            'path' => $file->file_path,
                            'name' => $file->file,
                            'identifier' => $identifier,
                        ];
                    }
                }
            }
        }
        $cache_file->write($minify->minify());
        $this->css = array_merge([$path => ['string' => $cache_file->file_path, 'type' => 'file']], $this->css);
        $this->persistsMinifiedCacheFileInformation($read);
    }

    private function createCssMinify()
    {
        $this->createMinifiedCacheFileInformation();
        $read = $this->readMinifiedCacheFileInformation();
        $path = '';
        foreach ($this->css as $identifier => $content) {
            if (!in_array($identifier, $this->remove_css)) {
                if ($content['type'] === 'file') {
                    $path .= $content['string'];
                }
            }
            $path = md5($path);
        }
        if (WingedConfig::$AUTO_MINIFY) {
            if (!array_key_exists($path, $read) && $path !== '') {
                $this->createCssMinifyNew($path, $read);
            } else {
                //check and update if need
                if (array_key_exists($path, $read)) {
                    $check = $read[$path];
                    $renew = false;
                    foreach ($check['formed_with'] as $key => $former) {
                        $file = new File($key, false);
                        if (!$file->exists()) {
                            $renew = true;
                        } else {
                            if ($former['time'] != $file->modifyTime()) {
                                $renew = true;
                            }
                        }
                        if (!array_key_exists($former['identifier'], $this->css)) {
                            $renew = true;
                        }
                    }
                    if ((int)Date::now()->diff((new Date($check['create_at'])), ['i'])->minutes > (int)WingedConfig::$AUTO_MINIFY) {
                        $renew = true;
                    }
                    if ($renew) {
                        $old_cache_file = new File($read[$path]['cache_file']);
                        if ($old_cache_file->exists()) {
                            $old_cache_file->delete();
                        }
                        $this->createCssMinifyNew($path, $read);
                    } else {
                        foreach ($check['formed_with'] as $key => $former) {
                            $this->removeCss($former['identifier']);
                        }
                        $this->css = array_merge([$path => ['string' => $check['cache_file'], 'type' => 'file']], $this->css);
                    }
                }
            }
        } else {
            if (array_key_exists($path, $read)) {
                $check = $read[$path];
                foreach ($check['formed_with'] as $key => $former) {
                    $this->removeCss($former['identifier']);
                }
                $this->css = array_merge([$path => ['string' => $check['cache_file'], 'type' => 'file']], $this->css);
            }
        }
    }

    private function createMinifiedCacheFileInformation()
    {
        $file = new File('./minify.cache.json', false);
        if (!$file->exists()) {
            $file = new File('./minify.cache.json');
            $file->write(json_encode([]));
        } else {
            $file = new File('./minify.cache.json');
        }
        $this->minify_cache = $file;
    }

    private function persistsMinifiedCacheFileInformation($content)
    {
        return ($this->minify_cache->write(json_encode($content)));
    }

    private function readMinifiedCacheFileInformation()
    {
        return json_decode($this->minify_cache->read(), true);
    }

    private function makeAssetsSrc($src = '')
    {
        return Winged::$protocol . $src;
    }

    public function rewriteHeadContentPath($path)
    {
        $this->head_path = $path;
    }

    public function getHeadContentPath()
    {
        return $this->head_path;
    }

    public function returnContent($path, $vars = array(), $loads = array())
    {
        $path = $this->getViewFile($path);
        if (file_exists($path) && !is_directory($path)) {
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($loads as $key => $value) {
                if (file_exists($value) && !is_directory($value)) {
                    include_once $value;
                }
            }
            if (Winged::$controller_debug) {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    if (Error::exists()) {
                        Error::display(__LINE__, __FILE__);
                    }
                    $content = Buffer::get();
                    Buffer::kill();
                    return $content;
                } else {
                    include_once $path;
                }
            } else {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    $content = Buffer::get();
                    Buffer::kill();
                    return $content;
                } else {
                    include_once $path;
                }
            }
            if ($this->callable !== null) {
                call_user_func($this->callable);
            }
        } else {
            Error::push(__CLASS__, "file {$path} can't rendred because file not found.", __FILE__, __LINE__);
        }
    }

    public function renderAnyFile($path, $vars = array(), $loads = array())
    {
        if (file_exists($path) && !is_directory($path)) {
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($loads as $key => $value) {
                if (file_exists($value) && !is_directory($value)) {
                    include_once $value;
                }
            }
            if (Winged::$controller_debug) {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    if (Error::exists()) {
                        Error::display(__LINE__, __FILE__);
                    }
                    $content = Buffer::get();
                    Buffer::kill();
                    echo $content;
                } else {
                    include_once $path;
                }
            } else {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    $content = Buffer::get();
                    Buffer::kill();
                    echo $content;
                } else {
                    include_once $path;
                }
            }
            if ($this->callable !== null) {
                call_user_func($this->callable);
            }
        } else {
            Error::push(__CLASS__, "file {$path} can't rendred because file not found.", __FILE__, __LINE__);
        }
    }

    public function renderPartial($path, $vars = array(), $loads = array(), $return = false)
    {
        $path = $this->getViewFile($path);
        if (file_exists($path) && !is_directory($path)) {
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($loads as $key => $value) {
                if (file_exists($value) && !is_directory($value)) {
                    include_once $value;
                }
            }
            if (Winged::$controller_debug) {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    if (Error::exists()) {
                        Error::display(__LINE__, __FILE__);
                    }
                    $content = Buffer::get();
                    Buffer::kill();
                    if ($return) {
                        return $content;
                    }
                    echo $content;
                } else {
                    include_once $path;
                }
            } else {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    $content = Buffer::get();
                    Buffer::kill();
                    if ($return) {
                        return $content;
                    }
                    echo $content;
                } else {
                    include_once $path;
                }
            }
            if ($this->callable !== null) {
                call_user_func($this->callable);
            }
        } else {
            Error::push(__CLASS__, "file {$path} can't rendred because file not found.", __FILE__, __LINE__);
        }
        return false;
    }

    public function renderJson($path, $vars = array(), $loads = array())
    {
        $path = $this->getViewFile($path);
        if (file_exists($path) && !is_directory($path)) {
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($vars as $key => $value) {
                if (!is_int($key) && is_string($key)) {
                    ${$key} = $value;
                }
            }
            foreach ($loads as $key => $value) {
                if (file_exists($value) && !is_directory($value)) {
                    include_once $value;
                }
            }
            if (Winged::$controller_debug) {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    if (Error::exists()) {
                        Error::display(__LINE__, __FILE__);
                    }
                    Buffer::kill();
                    $this->array2json();
                } else {
                    include_once $path;
                }
            } else {
                if ($this->first_render) {
                    $this->first_render = false;
                    Buffer::reset();
                    include_once $path;
                    Buffer::kill();
                    $this->array2json();
                } else {
                    include_once $path;
                }
            }
            if ($this->callable !== null) {
                call_user_func($this->callable);
            }
        } else {
            Error::push(__CLASS__, "file {$path} can't rendred because file not found.", __FILE__, __LINE__);
        }
    }

    private function array2json()
    {
        echo json_encode($this->tojson);
    }

    public function addToJsonResponse($value, $index_name = '')
    {
        if (!array_key_exists($index_name, $this->tojson) && gettype($index_name) === 'string') {
            $this->tojson[$index_name] = $value;
        } else {
            if (array_key_exists($index_name, $this->tojson)) {
                array_push($this->tojson, $value);
            } else {
                $this->tojson[$index_name] = $value;
            }
        }
        return true;
    }

    public function removeJs($identifier)
    {
        array_push($this->remove_js, $identifier);
        return;
    }

    public function removeCss($identifier)
    {
        array_push($this->remove_css, $identifier);
        return;
    }

    public function addJs($identifier, $string, $options = [], $url = false)
    {
        if (file_exists($string) && !is_directory($string) && !$url) {
            if (array_key_exists($identifier, $this->js)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, js file '" . $this->js[$identifier]['string'] . "' never load.", __FILE__, __LINE__);
            }
            $this->js[$identifier] = [];
            $this->js[$identifier]['string'] = $string;
            $this->js[$identifier]['type'] = 'file';
            $this->js[$identifier]['options'] = $options;
        } else if (!$url) {
            if (array_key_exists($identifier, $this->js)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, script '" . htmlspecialchars($this->js[$identifier]['string']) . "' never load.", __FILE__, __LINE__);
            }
            $this->js[$identifier] = array();
            $this->js[$identifier]['string'] = $string;
            $this->js[$identifier]['type'] = 'script';
            $this->js[$identifier]['options'] = $options;
        } else if ($url) {
            if (array_key_exists($identifier, $this->js)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, script url '" . $this->js[$identifier]['string'] . "' never load.", __FILE__, __LINE__);
            }
            $this->js[$identifier] = array();
            $this->js[$identifier]['string'] = $string;
            $this->js[$identifier]['type'] = 'url';
            $this->js[$identifier]['options'] = $options;
        }
        return;
    }

    public function addCss($identifier, $string, $options = [], $url = false)
    {
        if (file_exists($string) && !is_directory($string) && !$url) {
            if (array_key_exists($identifier, $this->css)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, script file '" . $this->css[$identifier]['string'] . "' never load.", __FILE__, __LINE__);
            }
            $this->css[$identifier] = array();
            $this->css[$identifier]['string'] = $string;
            $this->css[$identifier]['type'] = 'file';
            $this->css[$identifier]['options'] = $options;
        } else if (!$url) {
            if (array_key_exists($identifier, $this->css)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, css '" . htmlspecialchars($this->css[$identifier]['string']) . "' never load.", __FILE__, __LINE__);
            }
            $this->css[$identifier] = array();
            $this->css[$identifier]['string'] = $string;
            $this->css[$identifier]['type'] = 'script';
            $this->css[$identifier]['options'] = $options;
        } else if ($url) {
            if (array_key_exists($identifier, $this->css)) {
                Error::push(__CLASS__, "Index '" . $identifier . "' was doubled, css url '" . $this->css[$identifier]['string'] . "' never load.", __FILE__, __LINE__);
            }
            $this->css[$identifier] = array();
            $this->css[$identifier]['string'] = $string;
            $this->css[$identifier]['type'] = 'url';
            $this->css[$identifier]['options'] = $options;
        }
        return;
    }

    public function pushAbstractHead($identifier, $string)
    {
        $this->appended_abstract_head_content[$identifier] = $string;
    }

    public function removeAbstractHead($identifier)
    {
        if (array_key_exists($identifier, $this->appended_abstract_head_content)) {
            unset($this->appended_abstract_head_content[$identifier]);
        }
    }

}