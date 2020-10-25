<?php

/**
 * Class to parse css information.
 *
 * See the readme file : http://www.phpclasses.org/browse/file/4685.html
 *
 * $Id: cssparser.inc.php,v 1.1 2005/10/07 18:08:21 skenow Exp $
 *
 * @author     http://www.phpclasses.org/browse/package/1289.html
 */
class cssparser
{
    public $css;

    public $html;

    public function __construct($html = true)
    {
        // Register "destructor"

        register_shutdown_function([&$this, 'finalize']);

        $this->html = (false !== $html);

        $this->Clear();
    }

    public function finalize()
    {
        unset($this->css);
    }

    public function Clear()
    {
        unset($this->css);

        $this->css = [];

        if ($this->html) {
            $this->Add('ADDRESS', '');

            $this->Add('APPLET', '');

            $this->Add('AREA', '');

            $this->Add('A', 'text-decoration : underline; color : Blue;');

            $this->Add('A:visited', 'color : Purple;');

            $this->Add('BASE', '');

            $this->Add('BASEFONT', '');

            $this->Add('BIG', '');

            $this->Add('BLOCKQUOTE', '');

            $this->Add('BODY', '');

            $this->Add('BR', '');

            $this->Add('B', 'font-weight: bold;');

            $this->Add('CAPTION', '');

            $this->Add('CENTER', '');

            $this->Add('CITE', '');

            $this->Add('CODE', '');

            $this->Add('DD', '');

            $this->Add('DFN', '');

            $this->Add('DIR', '');

            $this->Add('DIV', '');

            $this->Add('DL', '');

            $this->Add('DT', '');

            $this->Add('EM', '');

            $this->Add('FONT', '');

            $this->Add('FORM', '');

            $this->Add('H1', '');

            $this->Add('H2', '');

            $this->Add('H3', '');

            $this->Add('H4', '');

            $this->Add('H5', '');

            $this->Add('H6', '');

            $this->Add('HEAD', '');

            $this->Add('HR', '');

            $this->Add('HTML', '');

            $this->Add('IMG', '');

            $this->Add('INPUT', '');

            $this->Add('ISINDEX', '');

            $this->Add('I', 'font-style: italic;');

            $this->Add('KBD', '');

            $this->Add('LINK', '');

            $this->Add('LI', '');

            $this->Add('MAP', '');

            $this->Add('MENU', '');

            $this->Add('META', '');

            $this->Add('OL', '');

            $this->Add('OPTION', '');

            $this->Add('PARAM', '');

            $this->Add('PRE', '');

            $this->Add('P', '');

            $this->Add('SAMP', '');

            $this->Add('SCRIPT', '');

            $this->Add('SELECT', '');

            $this->Add('SMALL', '');

            $this->Add('STRIKE', '');

            $this->Add('STRONG', '');

            $this->Add('STYLE', '');

            $this->Add('SUB', '');

            $this->Add('SUP', '');

            $this->Add('TABLE', '');

            $this->Add('TD', '');

            $this->Add('TEXTAREA', '');

            $this->Add('TH', '');

            $this->Add('TITLE', '');

            $this->Add('TR', '');

            $this->Add('TT', '');

            $this->Add('UL', '');

            $this->Add('U', 'text-decoration : underline;');

            $this->Add('VAR', '');
        }
    }

    public function SetHTML($html)
    {
        $this->html = (false !== $html);
    }

    public function Add($key, $codestr)
    {
        $key = mb_strtolower($key);

        //    $codestr = strtolower($codestr);

        if (!isset($this->css[$key])) {
            $this->css[$key] = [];
        }

        $codes = explode(';', $codestr);

        if (count($codes) > 0) {
            foreach ($codes as $indexval => $code) {
                $code = trim($code);

                @list($codekey, $codevalue) = explode(':', $code);

                if (mb_strlen($codekey) > 0) {
                    $this->css[$key][trim($codekey)] = trim($codevalue);
                }
            }
        }
    }

    public function Get($key, $property)
    {
        $key = mb_strtolower($key);

        //    $property = strtolower($property);

        @list($tag, $subtag) = explode(':', $key);

        @list($tag, $class) = explode('.', $tag);

        @list($tag, $id) = explode('#', $tag);

        $result = '';

        foreach ($this->css as $_tag => $value) {
            @list($_tag, $_subtag) = explode(':', $_tag);

            @list($_tag, $_class) = explode('.', $_tag);

            @list($_tag, $_id) = explode('#', $_tag);

            $tagmatch = (0 == strcmp($tag, $_tag)) | (0 == mb_strlen($_tag));

            $subtagmatch = (0 == strcmp($subtag, $_subtag)) | (0 == mb_strlen($_subtag));

            $classmatch = (0 == strcmp($class, $_class)) | (0 == mb_strlen($_class));

            $idmatch = (0 == strcmp($id, $_id));

            if ($tagmatch & $subtagmatch & $classmatch & $idmatch) {
                $temp = $_tag;

                if ((mb_strlen($temp) > 0) & (mb_strlen($_class) > 0)) {
                    $temp .= '.' . $_class;
                } elseif (0 == mb_strlen($temp)) {
                    $temp = '.' . $_class;
                }

                if ((mb_strlen($temp) > 0) & (mb_strlen($_subtag) > 0)) {
                    $temp .= ':' . $_subtag;
                } elseif (0 == mb_strlen($temp)) {
                    $temp = ':' . $_subtag;
                }

                if (isset($this->css[$temp][$property])) {
                    $result = $this->css[$temp][$property];
                }
            }
        }

        return $result;
    }

    public function GetSection($key)
    {
        $key = mb_strtolower($key);

        @list($tag, $subtag) = explode(':', $key);

        @list($tag, $class) = explode('.', $tag);

        @list($tag, $id) = explode('#', $tag);

        $result = [];

        foreach ($this->css as $_tag => $value) {
            @list($_tag, $_subtag) = explode(':', $_tag);

            @list($_tag, $_class) = explode('.', $_tag);

            @list($_tag, $_id) = explode('#', $_tag);

            $tagmatch = (0 == strcmp($tag, $_tag)) | (0 == mb_strlen($_tag));

            $subtagmatch = (0 == strcmp($subtag, $_subtag)) | (0 == mb_strlen($_subtag));

            $classmatch = (0 == strcmp($class, $_class)) | (0 == mb_strlen($_class));

            $idmatch = (0 == strcmp($id, $_id));

            if ($tagmatch & $subtagmatch & $classmatch & $idmatch) {
                $temp = $_tag;

                if ((mb_strlen($temp) > 0) & (mb_strlen($_class) > 0)) {
                    $temp .= '.' . $_class;
                } elseif (0 == mb_strlen($temp)) {
                    $temp = '.' . $_class;
                }

                if ((mb_strlen($temp) > 0) & (mb_strlen($_subtag) > 0)) {
                    $temp .= ':' . $_subtag;
                } elseif (0 == mb_strlen($temp)) {
                    $temp = ':' . $_subtag;
                }

                foreach ($this->css[$temp] as $property => $value) {
                    $result[$property] = $value;
                }
            }
        }

        return $result;
    }

    public function ParseStr($str)
    {
        $this->Clear();

        // Remove comments

        $str = preg_replace("/\/\*(.*)?\*\//Usi", '', $str);

        // Parse this damn csscode

        $parts = explode('}', $str);

        if (count($parts) > 0) {
            foreach ($parts as $indexval => $part) {
                @list($keystr, $codestr) = explode('{', $part);

                $keys = explode(',', trim($keystr));

                if (count($keys) > 0) {
                    foreach ($keys as $indexval => $key) {
                        if (mb_strlen($key) > 0) {
                            $key = str_replace("\n", '', $key);

                            $key = str_replace('\\', '', $key);

                            $this->Add($key, trim($codestr));
                        }
                    }
                }
            }
        }

        return (count($this->css) > 0);
    }

    public function Parse($filename)
    {
        $this->Clear();

        if (file_exists($filename)) {
            return $this->ParseStr(file_get_contents($filename));
        }

        return false;
    }

    public function GetCSS()
    {
        $result = '';

        foreach ($this->css as $key => $values) {
            $result .= $key . " {\n";

            foreach ($values as $key => $value) {
                $result .= "  $key: $value;\n";
            }

            $result .= "}\n\n";
        }

        return $result;
    }
}
