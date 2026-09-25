<?php
$content = file_get_contents('tmp_grades.blade.php');
file_put_contents('resources/views/student/grades/index.blade.php', $content);
echo "copied";