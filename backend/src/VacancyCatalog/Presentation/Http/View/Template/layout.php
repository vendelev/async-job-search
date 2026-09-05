<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var \Closure(string): string $text */

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= $text($title) ?></title>
</head>
<body><?= $content ?></body>
</html>
