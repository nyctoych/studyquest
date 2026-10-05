<?php
$brainDir = 'C:/Users/Araw Family/.gemini/antigravity-ide/brain';
$convDirs = scandir($brainDir);

$conversations = [];
foreach ($convDirs as $d) {
    if ($d === '.' || $d === '..' || !is_dir("$brainDir/$d")) continue;
    $time = filemtime("$brainDir/$d");
    $conversations[$d] = $time;
}
arsort($conversations);

echo "Found " . count($conversations) . " conversations in brain:\n";

foreach ($conversations as $id => $time) {
    $dateStr = date('Y-m-d H:i:s', $time);
    $transcriptFile = "$brainDir/$id/.system_generated/logs/transcript.jsonl";
    if (!file_exists($transcriptFile)) continue;
    
    // We only care about Sept 28 to Oct 5
    if (strtotime($dateStr) < strtotime('2026-09-28 00:00:00')) continue;
    
    echo "\n======================================================\n";
    echo "CONVERSATION: $id ($dateStr)\n";
    echo "======================================================\n";
    
    $f = fopen($transcriptFile, 'r');
    $userInputs = [];
    $toolActions = [];
    $imageGens = [];
    
    while (($line = fgets($f)) !== false) {
        $data = json_decode($line, true);
        if (!$data) continue;
        if (($data['type'] ?? '') === 'USER_INPUT') {
            $content = $data['content'] ?? '';
            // Trim and summarize
            $userInputs[] = mb_substr(trim(preg_replace('/\s+/', ' ', $content)), 0, 160);
        }
        if (!empty($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                $name = $tc['name'] ?? '';
                if ($name === 'generate_image') {
                    $imageGens[] = ($tc['args']['ImageName'] ?? '') . ': ' . ($tc['args']['Prompt'] ?? '');
                }
                if ($name === 'write_to_file' || $name === 'replace_file_content') {
                    $target = basename($tc['args']['TargetFile'] ?? '');
                    $toolActions[$target] = ($toolActions[$target] ?? 0) + 1;
                }
            }
        }
    }
    fclose($f);
    
    echo "User Prompts (" . count($userInputs) . "):\n";
    foreach (array_slice($userInputs, 0, 8) as $up) {
        echo "  - $up\n";
    }
    if (count($userInputs) > 8) echo "  ... (" . (count($userInputs) - 8) . " more prompts)\n";
    
    if (!empty($imageGens)) {
        echo "Generated Images (" . count($imageGens) . "):\n";
        foreach ($imageGens as $ig) echo "  * $ig\n";
    }
    
    if (!empty($toolActions)) {
        echo "Modified Files: " . implode(', ', array_keys(array_slice($toolActions, 0, 15))) . "\n";
    }
}
