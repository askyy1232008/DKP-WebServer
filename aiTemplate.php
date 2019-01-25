<?php

class aiTemplate {

  /**
   * Path to the ai-script directory.
   *
   * @var string
   * @access protected
   */
  var $aiPath = 'ai/';

  /**
   * Language name.
   *
   * @var string
   * @access protected
   */
  var $language= 'de';

  /**
   * Path to the templates of the ai-script.
   *
   * @var string
   * @access protected
   * 
   */
  var $templatePath = 'templates/';

  /**
   * Path to the language files of the ai-script.
   *
   * @var string
   * @access protected
   */
  var $languagePath = 'languages/';

  /**
   * Prefix for variables in the ai templates.
   *
   * @var string
   * @access protected
   */
  var $varPrefix = '{$';

  /**
   * Suffix for variables in the ai templates.
   *
   * @var string
   * @access protected
   */
  var $varSuffix = '}';

  /**
   * Number of spaces that the templates are "tabbed".
   *
   * @var integer
   * @access protected
   */
  var $tabs = 0;

  /**
   * Every language variable is safed here.
   *
   * @var array
   * @access protected
   * 
   */
   var $langVars = array();
  /**
   * Every template variable that is set is safed here.
   *
   * @var array
   * @access protected
   */
  var $vars = array();

  /**
   * Fatal errors while parsing/fetching anything.
   *
   * @var boolean
   * @access public
   */
  var  $fatalError = false;

  /**
   * Errors are shown (using print) or not.
   *
   * @var boolean
   * @access protected
   */
  var $showErrors = false;

  /**
   * Constructor of the ai base class.
   *
   * @access public
   */
  function aiTemplate($aiPath, $language, $languagePath='languages/', $templatePath='templates/', $showErrors=false) {

    // Set some vars.
    $this->aiPath = $aiPath;
    $this->language = $language;
    if($languagePath != NULL) $this->languagePath = $languagePath;
    if($templatePath != NULL) $this->templatePath = $templatePath;
    $this->showErrors = $showErrors;

  }

  /**
   * Set if errors are shown or not.
   *
   * @param boolean $state Show errors or not
   * @access public
   */
  function SetError($state) {

    if(is_bool($state)) {

      $this->showErrors = $state;

    } else {

      $this->showErrors = false;

    }

  }

  /**
   * Class internal print functions.
   * Use {@link http://php.net/print print()} if {@link $showErrors} is
   * true.
   *
   * @param string $string String that is printed
   * @param boolean $fatal Errors is fatal for the script
   * @access protected
   */
  function _Error($string, $fatal=false) {

    // Print only if show errors parameter is true.
    if($this->showErrors) {

      print '<strong>ai ERROR:</strong>'.$string."<br />\n";

    }

    // Set fatalError
    if($fatal) $this->fatalError = true;

  }

  /**
   * Fetch a template, includes requirung and parsing.
   *
   * @param string $name Name of the template without extension.
   * @return string Fetched template
   * @access public
   */
  function TplFetch($name, $clearVars=false) {

    // Template could load.
    if($file = file_get_contents($this->aiPath.$this->templatePath.$name.'.html')) {

      // Return parsed template.
	  
      return $this->LineTrim($this->TplParse($file, $clearVars));

    // Error while loading file.
    } else {

      // Error and return false.
      $this->_Error('The template file  <em>'.$name.'</em> couldn\'t load.');
      return false;

    }

  }

  /**
   * Parse the variables in a template (as string).
   *
   * @param string $template Template as string
   * @return string Parsed template
   * @access public
   */
  function TplParse($template, $clearVars=false) {

    // Attribute vars is an array.
    if(isset($this->vars) && is_array($this->vars)) {

      // Go throw each variable.
      foreach($this->vars as $key => $value) {

        // Replace var in the string.
        $template = str_replace($this->varPrefix.$key.$this->varSuffix, $value, $template);

      }

    }

    // Delete all other variables, that are not set.
    if($clearVars) {

      $template = preg_replace('~'.preg_quote($this->varPrefix).'.*'.preg_quote($this->varSuffix).'~U', '', $template);

    }

    // Return parsed template.
    return $template;

  }

  /**
   * Tab the $string in for $number of spaces.
   *
   * @param string $sring A string, mostly html code
   * @return string Tabbed string
   * @access public
   */
  function TabIn($string, $number=-1) {

    // If no number is set (-1) use the class value.
    if($number == -1) {

      $number = $this->tabs;

    }

    // Code is string and not empty.
    if(is_string($string) && $string != '') {

      // Number is bigger then zero.
      if($number > 0) {

        // Tabs.
        $tabs = '';
        for($i = 0; $i <= $number; $i++) $tabs .= ' ';

        // Split in lines.
        $lines = explode("\n", $string);


        // Lines is an array with at least one element.
        if(is_array($lines) && count($lines) > 0) {

          // Go thru every line.
          foreach($lines as $key => $value) {

            $lines[$key] = ' '.$value;

          }

          // Implode again and return.
          return implode("\n", $lines);
          

        // Lines is invalid.
        } else {

          // Error and return false.
          $this->_Error('The value of the <em>code</em> for the tab_in function is invalid.');
          return false;

        }

      // Number is not int or smaller then 1.
      } else {

        // return untabbed code
        return $string;

      }

    // Not a string or empty.
    } else {

      // Error and return false.
      $this->_Error('The value of the <em>code</em> for the tab_in function is invalid.');
      return false;

    }

  }

  /**
   * Works like the trim function, but also for multi line strings.
   *
   * @param string $sring A string, mostly html code
   * @return string Trimed string
   * @access public
   */
  function LineTrim($string) {

    // Split in lines.
    $lines = explode("\n", $string);

    // Lines is an array with at least one element-
    if(is_array($lines) && count($lines) > 0) {

      // Create new lines array.
      $newLines = array();

      // Go thru every line.
      foreach($lines as $key => $value) {

        // Create check value, that is trimed using trim().
        $checkValue = trim($value);

        // If check value not empty, save old line in the new line array.
        if($checkValue != '') {

          $newLines[] = $value;

        }

      }

      // Implode again.
      if(is_array($newLines) && count($newLines) > 0) {

        $string = implode("\n", $newLines);

      } else {

        $string = '';

      }

    }

    // Return the string, maybe untrimed or trimed.
    return $string;

  }

  /**
   * Set a template variable.
   *
   * @param string $key Key/Name of the variable
   * @param string $value Value of the variable
   * @return boolean State of the method
   * @access public
   */
  function SetVar($key, $value) {

    // Key is not empty.
    if($key != '') {

      // Save in vars attribute.
      $this->vars[$key] = $value;
      return true;

    // Bad key.
    } else {

      // Error and return false.
      $this->_Error('The key  <em>'.$key.'</em> is invalid.');
      return false;

    }

  }

  /**
   * Load a language file in the template object.
   *
   * @param string $language Shortname of the language, filename without extension
   * @return string State of method
   * @access public
   */
  function LoadLanguage($language=false) {

    // Use the class value if no language is set.
    if(!$language) {

      $language = $this->language;

    }

    // Load language file.
    if(require($this->aiPath.$this->languagePath.$this->language.'.php')) {

      // Save in the object.
      $this->langVars = $language;
      return true;

    // Can't load.
    } else {

      // Error and return false.
      $this->_Error('Can\'t load  the language file <em>'.$this->ai_path.$this->language_path.$this->language().'.php'.'</em>.');
      return false;

    }

  }

  /**
   * Get and return a language string.
   *
   * @param string $key Name of the returned language string
   * @return string Language value
   * @access public
   */
  function GetLanguage($key) {

    // Key is string and not empty.
    if(is_string($key) && $key != '') {

      // Isset in the language vars.
      if(isset($this->langVars[$key])) {

        // return
        return $this->langVars[$key];

      }

    // not string or empty
    } else {

      // error and return false
      $this->_Error('The value of the language key <em>'.$key.'</em> is invalid.');
      return false;

    }
  }

  /**
   * Set the numbers of space/tabs.
   *
   * @param integer $number Number of spaces/tabs.
   * @return boolean True if $number is Intege. Else false is returned.
   * @access public
   * @see $tabs
   */
  function SetTabs($number) {

    // Number is an Integer.
    if(is_int($number)) {

      $this->tabs = $number;
      return true;

    // Else.
    } else {

      // Error and return false.
      $this->_Error('The value of the <em>number</em> for the tab_in function is invalid.');
      return false;

    }

  }

  /**
   * Unset all temlate varibles.
   *
   * @access public
   */
  function UnsetVars() {

    $this->vars = array();

  }

}

?>