<?php
echo "<h2>PHP Extensions Check</h2>";

echo "<p>GD Extension: ";
if (extension_loaded('gd')) {
    echo "<span style='color: green;'>✅ Enabled</span>";
} else {
    echo "<span style='color: red;'>❌ Not Enabled</span>";
}
echo "</p>";

echo "<p>ZIP Extension: ";
if (extension_loaded('zip')) {
    echo "<span style='color: green;'>✅ Enabled</span>";
} else {
    echo "<span style='color: red;'>❌ Not Enabled</span>";
}
echo "</p>";

echo "<p>PHPWord: ";
if (file_exists('vendor/autoload.php')) {
    echo "<span style='color: green;'>✅ Installed</span>";
} else {
    echo "<span style='color: red;'>❌ Not Found</span>";
}
echo "</p>";
?>