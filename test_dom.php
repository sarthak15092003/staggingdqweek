<?php
$html = file_get_contents("C:\Users\HI\.gemini\antigravity-ide\brain\faf78360-eb3a-4961-b0ca-e79a767312d6\.system_generated\steps\2374\content.md");
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
$nodes = $xpath->query("//div[@data-elementor-type=\"wp-post\"]");
if ($nodes->length > 0) {
    echo "Found Elementor Div length: " . strlen($dom->saveHTML($nodes->item(0))) . "\n";
}
$links = $xpath->query("//link[contains(@id, \"elementor\")]");
echo "Found Elementor Links: " . $links->length . "\n";
?>
