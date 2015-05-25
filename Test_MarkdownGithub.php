<?php

include_once('./PHPMarkdown/MarkdownGithub.inc.php');

use \Michelf\MarkdownGithub;

$my_text = "#Github flavored Markdown Test 

## Task list

- [x] task 1
	- [x] sub-task 1
	- [x] sub-task 2 
- [ ] task 2
- [ ] task 3
";

echo  MarkdownGithub::defaultTransform($my_text);
