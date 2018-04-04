<?php
include_once(dirname(__FILE__) . "/config.php");
include_once(dirname(__FILE__) . "/logging.php");

class tpl {

    private $var = array();
    private $replace_var = array();
    private $header = null;
    private $footer = null;
    private $strict = false;

    public function __construct($header = null, $footer = null, $strict = false) {
        $this->header = $header;
        $this->footer = $footer;
        $this->strict = $strict;
    }

    public function assign($key, $value = '') {
        if ($key == "instance" || $key == ":instance" || $key == ":app" || $key == "app" || $key == "vendor" || $key == ":vendor") {
            logging::fatal("TPL", "do not use variable: $key");
        }

        if (is_array($key)) {
            // $this->var = array_merge($this->var, $key);
            foreach ($key as $k => $v) {
                $this->assign($k, $v);
            }
        } else {
            if ($key{0} == ':') {
                $this->replace_var[$key] = $value;
            } else {
                $this->var[$key] = $value;
            }
        }
    }

    public function set($key, $value = '') {
        $this->assign($key, $value);
    }

    public function get($key = null) {
        if ($key == null)
            return $this->var;
        return isset($this->var[$key]) ? $this->var[$key] : null;
    }

    private function render($file) {

        $content = file_get_contents($file);

        $matches = array();
        preg_match_all('/\{:(\$[^\}]*)\}/', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $node) {
            // logging::d("Debug", "match 0 = " . $node[0]);
            // logging::d("Debug", "match 1 = " . $node[1]);
            $content = str_replace($node[0], "<?php echo {$node[1]}; ?>", $content);
        }

        $matches = array();
        preg_match_all('/\{=([^\}]*)\}/', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $node) {
            // logging::d("Debug", "match 0 = " . $node[0]);
            // logging::d("Debug", "match 1 = " . $node[1]);
            $content = str_replace($node[0], "<?php echo {$node[1]}; ?>", $content);
        }

        // logging::d("Debug", $content, false);

        extract($this->var, EXTR_OVERWRITE);
        // include($file);
        eval("?> $content");
    }

    public function display_file($filename) {
        ob_start();
        ob_implicit_flush(false);
        $this->render($filename);
        $c = ob_get_clean();
        $c = $this->replace($c);

        echo $c;
        if (DEBUG) {
            echo '<script type="text/javascript">var SERVERS = ' . json_encode($_SERVER) . '; console.debug("SERVERS:"); console.debug(SERVERS);</script>' . "\n";
            echo '<script type="text/javascript">var REQUESTS = ' . json_encode($_REQUEST) . '; console.debug("REQUESTS:"); console.debug(REQUESTS);</script>' . "\n";
            echo '<script type="text/javascript">var SESSIONS = ' . json_encode($_SESSION) . '; console.debug("SESSIONS:"); console.debug(SESSIONS);</script>' . "\n";
        }
    }

    public function display($tplname, $mem = false) {
        list($file, $fileurl) = $this->parse($tplname);
        if (empty($file))
            return;

        $header = "";
        $footer = "";

        if ($this->header != null) {
            $hf = ROOT_PATH . TPL_PATH . $this->header . ".htm";
            if (is_file($hf)) {
                ob_start();
                ob_implicit_flush(false);
                extract($this->var, EXTR_OVERWRITE);
                include($hf);
                $header = ob_get_clean();
            }
        }

        if ($this->footer != null) {
            $ff = ROOT_PATH . TPL_PATH . $this->footer . ".htm";
            if (is_file($ff)) {
                ob_start();
                ob_implicit_flush(false);
                extract($this->var, EXTR_OVERWRITE);
                include($ff);
                $footer = ob_get_clean();
            }
        }


        ob_start();
        ob_implicit_flush(false);

        if (PARSE_SCRIPT) {
            extract($this->var, EXTR_OVERWRITE);
            echo '<script>';
            list($js, $jsurl) = $this->parse($tplname, "js");
            if ($js != null) {
                // logging::d("Tpl", "attach js: $js");
                // $c = "<script type=\"text/javascript\" src=\"$js\"></script>\n" . $c;
                include($js);
            }
            list($js, $jsurl) = $this->parse($tplname, "jscommon");
            if ($js != null) {
                // logging::d("Tpl", "attach js: $js");
                // $c = "<script type=\"text/javascript\" src=\"$js1\"></script>\n" . $c;
                include($js);

            }
            echo '</script>';
            echo '<style>';
            list($css, $cssurl) = $this->parse($tplname, "css");
            if ($css != null) {
                // logging::d("Tpl", "attach css: $css");
                // $c = "<link rel='stylesheet' href='$css'>\n" . $c;
                include($css);
            }

            list($css, $cssurl) = $this->parse($tplname, "csscommon");
            if ($css != null) {
                // logging::d("Tpl", "attach css: $css");
                // $c = "<link rel='stylesheet' href='$css'>\n" . $c;
                include($css);
            }
            echo '</style>';
        } else {
            list($js, $jsurl) = $this->parse($tplname, "js");
            if ($jsurl != null) {
                echo "<script type=\"text/javascript\" src=\"$jsurl\"></script>\n";
            }
            list($js, $jsurl) = $this->parse($tplname, "jscommon");
            if ($jsurl != null) {
                echo "<script type=\"text/javascript\" src=\"$jsurl\"></script>\n";
            }
            list($css, $cssurl) = $this->parse($tplname, "css");
            if ($cssurl != null) {
                echo "<link rel='stylesheet' href='$cssurl'>\n";
            }
            list($css, $cssurl) = $this->parse($tplname, "csscommon");
            if ($cssurl != null) {
                echo "<link rel='stylesheet' href='$cssurl'>\n";
            }
        }
        $script = ob_get_clean();

        ob_start();
        ob_implicit_flush(false);
        $this->render($file);
        $c = ob_get_clean();

        // logging::d("Debug", "header: $header");
        // logging::d("Debug", "c: $c");
        // logging::d("Debug", "footer: $footer");

        $script = $this->replace($script);
        $header = $this->replace($header);
        $c = $this->replace($c);
        $footer = $this->replace($footer);

        $c = $header . $c . $footer;
        $c = str_replace("</head>", "$script\n</head>", $c);

        if ($mem) {
            return $c;
        }

        // echo $script;
        // echo $header;
        echo $c;
        // echo $footer;

        if (false && DEBUG) {
            // debug output.
            $info1 = empty($this->var) ? json_encode(array()) : json_encode($this->var);
            $info2 = empty($this->replace_var) ? json_encode(array()) : json_encode($this->replace_var);
            echo '<script type="text/javascript">var PARSE_VAR = ' . $info1 . '; console.debug("persevar:"); console.debug(PARSE_VAR);</script>' . "\n";
            echo '<script type="text/javascript">var REPLACE_VAR = ' . $info2 . '; console.debug("replacevar:"); console.debug(REPLACE_VAR);</script>' . "\n";
            echo '<script type="text/javascript">var SERVERS = ' . json_encode($_SERVER) . '; console.debug("SERVERS:"); console.debug(SERVERS);</script>' . "\n";
            echo '<script type="text/javascript">var REQUESTS = ' . json_encode($_REQUEST) . '; console.debug("REQUESTS:"); console.debug(REQUESTS);</script>' . "\n";
            echo '<script type="text/javascript">var SESSIONS = ' . json_encode($_SESSION) . '; console.debug("SESSIONS:"); console.debug(SESSIONS);</script>' . "\n";
        }
    }

    private function parse($tplname, $ext = "htm") {
        if ($ext == "js") {
            $file = ROOT_PATH . TPL_JS_PATH . $tplname . "." . $ext;
            $ff = INSTANCE_URL . TPL_JS_PATH . $tplname . "." . $ext;
        } else if ($ext == "css") {
            $file = ROOT_PATH . TPL_CSS_PATH . $tplname . "." . $ext;
            $ff = INSTANCE_URL . TPL_CSS_PATH . $tplname . "." . $ext;
        } else if ($ext == "jscommon") {
            $pth = dirname($tplname);
            $file = ROOT_PATH . TPL_JS_PATH . $pth . "/common.js";
            $ff = INSTANCE_URL . TPL_JS_PATH . $pth . "/common.js";
        } else if ($ext == "csscommon") {
            $pth = dirname($tplname);
            $file = ROOT_PATH . TPL_CSS_PATH . $pth . "/common.css";
            $ff = INSTANCE_URL . TPL_CSS_PATH . $pth . "/common.css";
        } else if ($ext == "tpl" || $ext == "htm") {
            $file = ROOT_PATH . TPL_PATH . $tplname . "." . $ext;
            $ff = $file;
        } else {
            return array(null, null);
        }


        if (!is_file($file)) {
            // $index = dirname($file);
            // $index = "$index/index.$ext";
            // if (is_file($index)) {
            //     logging::d("Tpl", "redirect $tplname to index.$ext");
            //     return $index;
            // }
            return array(null, null);
        }
        return array(0 => $file, 1 => $ff);
    }

    private function replace($content) {
        foreach ($this->replace_var as $key => $value) {
            $rep = "[$key]";
            $content = str_replace($rep, $value, $content);
        }

        if (!$this->strict) {
            foreach ($this->var as $key => $value) {
                if (!is_string($value) && !is_numeric($value)) {
                    $value = json_encode($value);
                }
                $rep = "[:$key]";
                $content = str_replace($rep, $value, $content);
            }
        }

        $content = str_replace("[:instance]", rtrim(INSTANCE_URL, "/"), $content);
        $content = str_replace("[:app]", rtrim(APP_URL, "/"), $content);
        $content = str_replace("[:vendor]", rtrim(VENDOR_URL, "/"), $content);
        $content = str_replace("[:orgnization]", ORGANIZATION_NAME, $content);
        return $content;
    }
};


