# PHPMarkdown

The famous Michelf php-markdown's derivation (https://michelf.ca/projects/php-markdown/)

Version 1.5.0 - Mar 1 2015

by Michel Fortin
<http://www.michelf.com/>

based on work by John Gruber
<http://daringfireball.net/>

# This fork
This derivation add 2 files:

1. MarkdownGithub.inc.php
2. MarkdownGithub.php

These 2 files are the Github flavored Markdown parser.

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

# ToDo

Read here <https://help.github.com/articles/github-flavored-markdown/>.

Add more github flavored markdown parser.

