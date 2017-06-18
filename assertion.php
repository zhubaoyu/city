<?php
// Active assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

// Create a handler function
function assert_handler($file, $line, $code, $desc=null)
{
    echo "<hr />Assertion Failed:
        File '$file'<br />
        Line '$line'<br />
        Code '$code'<br />
        Desc '$desc'<br /><hr />";
}

// Set up the callback
assert_options(ASSERT_CALLBACK, 'assert_handler');
