# PHPMarkdown

The famous Michelf php-markdown's derivation (https://michelf.ca/projects/php-markdown/)

Version 1.5.0 - Mar 1 2015

by Michel Fortin
<http://www.michelf.com/>

based on work by John Gruber
<http://daringfireball.net/>

# This fork
This derivation add github task list parser into the MarkdownExtra.php file.

- [x] task1
- [ ] task2
- [ ] task3

## How to use

```php
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

$html = MarkdownGithub::defaultTransform($my_text);
echo $html;

?>
```