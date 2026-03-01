<?php
class HTML_Form {
    
    private $tag;
    private $xhtml;
	public $input_box = array('text','radio','checkbox','password','date','time','number','email','datetime_local','color','datetime','datetime_local','month','range','tel','url','week','image','file');

    function __construct($xhtml = true) {
        $this->xhtml = $xhtml;
    }
    
    function startForm($action = '#', $method = 'post', $id = '',$name='' , $attr_ar ) {
        $str = "<form action=\"$action\" method=\"$method\" name=\"$name\" ";
        if ( !empty($id) ) {
            $str .= " id=\"$id\"";
        }
        $str .= $attr_ar .'>';
        return $str;
    }
    
   
    function addInput($type,$name,$id,$value,$attribute) {
        $str = "<input type=\"".$type."\" name=\"".$name."\" id=\"".$id."\" value=\"".$value."\"  ".$attribute.">";
		
        return $str;
    }
    
    function addTextarea($name,$id, $value = '', $attr_ar  ) {
        $str = "<textarea name=\"$name\" id=\"$id\" ";
        if ($attr_ar) {
            $str .= $attr_ar ;
        }
        $str .= ">$value</textarea>";
        return $str;
    }
    
    // for attribute refers to id of associated form element
    function addLabelFor($text,$labelFor,$attr_ar = "") {
        $str = "<label for=\"$labelFor\"";
        $str .= $attr_ar ;
        $str .= ">$text</label>";
        return $str;
    }
    
    // from parallel arrays for option values and text
    function addSelectListArrays($name, $option_list, $selected_value = NULL,
            $header = NULL, $attr_ar ) {
        $str = $this->addSelectList($name, $option_list, true, $selected_value, $header, $attr_ar );
        return $str;
    }
    
    // option values and text come from one array (can be assoc)
    // $bVal false if text serves as value (no value attr)
    function addSelectList($name, $option_list, $bVal = true, $selected_value = NULL,
            $header = NULL, $attr_ar) {
        $str = "<select name=\"$name\"";
        if ($attr_ar) {
            $str .=  $attr_ar ;
        }
        $str .= ">\n";
        if ( isset($header) ) {
            $str .= "  <option value=\"\">$header</option>\n";
        }
        foreach ( $option_list as $val => $text ) {
            $str .= $bVal? "  <option value=\"$val\"": "  <option";
            if ( isset($selected_value) && ( $selected_value == $val || $selected_value == $text) ) {
                $str .= $this->xhtml? ' selected="selected"': ' selected';
            }
            $str .= ">$text</option>\n";
        }
        $str .= "</select>";
        return $str;
    }
    
    function endForm() {
        return "</form>";
    }
    
    function startTag($tag, $attr_ar) {
        $this->tag = $tag;
        $str = "<$tag";
        if ($attr_ar) {
            $str .= ' '.$attr_ar ;
        }
        $str .= '>';
        return $str;
    }
    
    function endTag($tag = '') {
        $str = $tag? "</$tag>": "</$this->tag>";
        $this->tag = '';
        return $str;
    }
    
    function addEmptyTag($tag, $attr_ar ) {
        $str = "<$tag";
        if ($attr_ar) {
            $str .= $attr_ar ;
        }
        $str .= $this->xhtml? ' />': '>';
        return $str;
    }  
	

	function js_var($name,$display,$validate,$depends=''){
		$js_file='';
		if(trim($validate)!=''){
			$js_file  ='{';
			$js_file .=' "name" : "'.$name.'",';
			$js_file .=' "rules" : "'.$validate.'"';
			if(trim($display)!=''){
				$js_file .=', "display" : "'.$display.'"';
			}
			if(trim($depends)!=''){
				$js_file .=', "depends" : "'.$depends.'"';
			}
			$js_file .='}';
		}
		return $js_file;
	}

	# HELPERS & UTILITY
	function printr($data) {
		# aids in debugging by not making you have to type all of
		# this nonsense out each time you want to print_r() something
		if($data === 'post') {
			echo '<tt><pre>';print_r($_POST);echo'</pre></tt>';
		}
		elseif($data === 'get') {
			echo '<tt><pre>';print_r($_GET);echo'</pre></tt>';
		} else {
			echo '<tt><pre>';print_r($data);echo'</pre></tt>';
		}
	}	
}

?>