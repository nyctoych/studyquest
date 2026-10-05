<?php
$convs = [
  '669a76c5-0f1e-425c-8ccb-bd9663a28f4b',
  '23d0018a-2f4c-4124-a3f0-319eebcdd05f',
  '23c42546-83bd-4980-a517-e4f0329f373d',
  'd58219a2-c8d0-47f0-ae98-a6e13a149458',
  'a5b739fd-56a7-42b5-a3e7-a9333a7dac9b',
  'bc44ae9e-02f5-4fb4-864e-0a7c34c63709',
  'b3e21d50-5af0-4c9e-b7ce-4f1d1c67cb73'
];

foreach ($convs as $cid) {
    $tfile = "C:/Users/Araw Family/.gemini/antigravity-ide/brain/$cid/.system_generated/logs/transcript.jsonl";
    if (!file_exists($tfile)) continue;
    echo "====================================================================\n";
    echo "CONVERSATION ID: $cid\n";
    echo "====================================================================\n";
    $f = fopen($tfile, 'r');
    $step = 0;
    while (($line = fgets($f)) !== false) {
        $row = json_decode($line, true);
        if (!$row) continue;
        if (($row['type'] ?? '') === 'USER_INPUT') {
            $step++;
            $txt = $row['content'] ?? '';
            // Strip additional metadata tags
            $txt = preg_replace('/<ADDITIONAL_METADATA>.*?<\/ADDITIONAL_METADATA>/s', '', $txt);
            $txt = preg_replace('/<USER_SETTINGS_CHANGE>.*?<\/USER_SETTINGS_CHANGE>/s', '', $txt);
            $txt = trim(strip_tags($txt));
            echo "--- [USER PROMPT $step] ---\n";
            echo substr($txt, 0, 400) . "\n";
            if (strlen($txt) > 400) echo "... [length: " . strlen($txt) . " chars]\n";
        }
    }
    fclose($f);
}
