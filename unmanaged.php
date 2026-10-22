<?php
posix_kill(getmypid(), 11); // Triggers an instant SIGSEGV segmentation fault
?>
