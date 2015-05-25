<?php
#
# Markdown Extra  -  A text-to-HTML conversion tool for web writers
#
# PHP Markdown Extra
# Copyright (c) 2004-2015 Michel Fortin  
# <https://michelf.ca/projects/php-markdown/>
#
# Original Markdown
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>
#
namespace Michelf;


#
# Markdown Extra Parser Class
#

class MarkdownGithub extends \Michelf\Markdown {

	protected $tasklist_level = 0;

	public function __construct() {
		$this->block_gamut += array(
			"doTaskLists"        => 35,//should less than doLists 40
			);
		parent::__construct();
	}


	protected function doTaskLists($text) {
		
	#
	# Form HTML ordered (numbered) and unordered (bulleted) lists.
	#
		$less_than_tab = $this->tab_width - 1;

		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';

		$markers_relist = array(
			$marker_ul_re => $marker_ol_re,
			$marker_ol_re => $marker_ul_re,
			);

		foreach ($markers_relist as $marker_re => $other_marker_re) {
			# Re-usable pattern to match any entirel ul or ol list:
			$whole_list_re = '
				(								# $1 = whole list
				  (								# $2
					([ ]{0,'.$less_than_tab.'})	# $3 = number of spaces
					('.$marker_re.')			# $4 = first list item marker
					[ ]                         # 1 space after marker
					\[[ xX]\]					# [x] [ ] [X] followed by 1 space
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						'.$marker_re.'[ ]+
					  )
					|
					  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						'.$other_marker_re.'[ ]+
					  )
				  )
				)
			'; // mx
			
			# We use a different prefix before nested lists than top-level lists.
			# See extended comment in _ProcessListItems().
		
			if ($this->tasklist_level) {
				$text = preg_replace_callback('{
						^
						'.$whole_list_re.'
					}mx',
					array($this, '_doTaskLists_callback'), $text);
			}
			else {
				$text = preg_replace_callback('{
						(?:(?<=\n)\n|\A\n?) # Must eat the newline
						'.$whole_list_re.'
					}mx',
					array($this, '_doTaskLists_callback'), $text);
			}
		}

		return $text;
	}
	
	protected function _doTaskLists_callback($matches) {
		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";
		$marker_ol_start_re = '[0-9]+';

		$list = $matches[1];
		$list_type = preg_match("/$marker_ul_re/", $matches[4]) ? "ul" : "ol";

		$marker_any_re = ( $list_type == "ul" ? $marker_ul_re : $marker_ol_re );

		$list .= "\n";
		$result = $this->processTaskListItems($list, $marker_any_re);

		$ol_start = 1;
		if ($this->enhanced_ordered_list) {
			# Get the start number for ordered list.
			if ($list_type == 'ol') {
				$ol_start_array = array();
				$ol_start_check = preg_match("/$marker_ol_start_re/", $matches[4], $ol_start_array);
				if ($ol_start_check){
					$ol_start = $ol_start_array[0];
				}
			}
		}

		if ($ol_start > 1 && $list_type == 'ol'){
			$result = $this->hashBlock("<$list_type class=\"task-list\" start=\"$ol_start\">\n" . $result . "</$list_type>");
		} else {
			$result = $this->hashBlock("<$list_type class=\"task-list\">\n" . $result . "</$list_type>");
		}
		return "\n". $result ."\n\n";
	}

	

	protected function processTaskListItems($list_str, $marker_any_re) {
	#
	#	Process the contents of a single ordered or unordered list, splitting it
	#	into individual list items.
	#
		# The $this->tasklist_level global keeps track of when we're inside a list.
		# Each time we enter a list, we increment it; when we leave a list,
		# we decrement. If it's zero, we're not in a list anymore.
		#
		# We do this because when we're not inside a list, we want to treat
		# something like this:
		#
		#		I recommend upgrading to version
		#		8. Oops, now this line is treated
		#		as a sub-list.
		#
		# As a single paragraph, despite the fact that the second line starts
		# with a digit-period-space sequence.
		#
		# Whereas when we're inside a list (or sub-list), that line will be
		# treated as the start of a sub-list. What a kludge, huh? This is
		# an aspect of Markdown's syntax that's hard to parse perfectly
		# without resorting to mind-reading. Perhaps the solution is to
		# change the syntax rules such that sub-lists must start with a
		# starting cardinal number; e.g. "1." or "a.".
		
		$this->tasklist_level++;

		# trim trailing blank lines:
		$list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

		$list_str = preg_replace_callback('{
			(\n)?							# leading line = $1
			(^[ ]*)							# leading whitespace = $2
			('.$marker_any_re.'				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 ('.$marker_any_re.') (?:[ ]+|(?=\n))))
			}xm',
			array($this, '_processTaskListItems_callback'), $list_str);

		$this->tasklist_level--;
		return $list_str;
	}
	
	
	protected function _processTaskListItems_callback($matches) {
		$item = $matches[4];
		$leading_line =& $matches[1];
		$leading_space =& $matches[2];
		$marker_space = $matches[3];
		$tailing_blank_line =& $matches[5];
		
		
		if ($leading_line || $tailing_blank_line || 
			preg_match('/\n{2,}/', $item))
		{
			# Replace marker with the appropriate whitespace indentation
			$item = $leading_space . str_repeat(' ', strlen($marker_space)) . $item;
			$item = $this->runBlockGamut($this->outdent($item)."\n");
		}
		else {
			# Recursion for sub-lists:
			$item = $this->doTaskLists($this->outdent($item));
			$item = preg_replace('/\n+$/', '', $item);
			$item = $this->runSpanGamut($item);
		}
		
		$checked = substr($item,1,1);
		$item = substr($item,3);
		if($checked==='x' || $checked==='X'){
			return "<li class=\"task-list-item\"><input type=\"checkbox\" checked=\"checked\"/> " . $item . "</li>\n";
		}
		return "<li class=\"task-list-item\"><input type=\"checkbox\" /> " . $item . "</li>\n";
	}

}