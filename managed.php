<?php
ini_set("memory_limit", "16M");
$buffer = "";
while (true) {
    $buffer .= str_repeat("A", 1024 * 1024);
}
?>
