<?php
require __DIR__ . '/update-manager.php';

function checkNavigation($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

$source = file_get_contents(dirname(__DIR__).'/script/umi_ftr.txt');
$filtered = hideJapaneseGrimoireButtons($source);
$before = explode("\n", $source);
$after = explode("\n", $filtered);
checkNavigation(count($before) === count($after), 'Unexpected structural change');
$removed = 0;
foreach ($before as $i => $line) {
    if ($line === $after[$i]) continue;
    if (trim($after[$i]) === '') {
        checkNavigation(str_contains($line, 'lsp r_grim_lsp,r_grim,') || trim($line) === 'spbtn r_grim_lsp,r_grim_lsp', 'Deleted non-Grimoire-button code');
        $removed++;
    } else {
        checkNavigation(false, 'Unexpected rewrite of shared code');
    }
}
checkNavigation($removed === 2, 'Unexpected navigation change count');
checkNavigation(strpos($filtered, "\n\tspbtn r_grim_lsp,r_grim_lsp") === false, 'Grimoire switch active');
checkNavigation(preg_match('/^\h*spbtn r_tips_lsp2,r_tips_lsp2\h*$/m', $filtered) === 1, 'In-game Tips entry changed');
checkNavigation(strpos($filtered, 'if %UMINEKOEND >= 90 lsp t_btn_ep4_tips_lsp,t_btn_ep8,1562,668') !== false, 'Character menu changed');
// Both the Tips and character submenus retain their Episode 8 button artwork.
checkNavigation(substr_count($filtered, 'if %UMINEKOEND >= 90 lsp t_btn_ep4_tips_lsp,t_btn_ep8,1562,668') === 2, 'Episode 8 title button missing');
checkNavigation(str_contains($filtered, 'if %UMINEKOEND_TIPS_FLG = 8 lsp t_btn_ep4_tips_lsp,t_btn_ep8_new,1562,668'), 'Episode 8 new marker missing');
echo "Verified only the Grimoire switch is hidden; Episode 8 Tips navigation and all other shared code preserved\n";

// Keep every shared title/body alias, including unused slots, loadable and blank.
$root = dirname(__DIR__);
preg_match_all('/^stralias ((?:r_grim[1-8]_\d+|grim_[1-8]_\d+_\d+)),.*$/m',
    file_get_contents($root.'/script/en/menu.txt'), $expected);
$menu = file_get_contents($root.'/script/jp/menu.txt');
preg_match_all('/^stralias ((?:r_grim[1-8]_\d+|grim_[1-8]_\d+_\d+)),.*$/m', $menu, $actual);
checkNavigation($actual[1] === $expected[1], 'Grimoire text aliases changed');
foreach ($actual[1] as $i => $alias) {
    $sprite = str_starts_with($alias, 'r_grim')
        ? ':s;#C7C7C7#FFFFFF`{p:13: }' : ':s;#FFFFFF`{p:5: }';
    checkNavigation($actual[0][$i] === 'stralias '.$alias.',"'.$sprite.'"',
        'Grimoire text is not a blank sprite: '.$alias);
}
echo 'Verified '.count($actual[1])." blank Grimoire text aliases\n";
