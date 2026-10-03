<?php

/*
 * Verify that the Japanese locale follows the English asset declarations while
 * resolving locale-specific images from graphics_jp/locale_jp.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$localeFiles = [
	'header.txt',
	'menu.txt',
	'caches.txt',
	'code.txt',
	'credits.txt',
	'prefs.txt',
];

function assetLines(string $path): array
{
	$lines = file($path, FILE_IGNORE_NEW_LINES);
	if ($lines === false) {
		throw new RuntimeException("Unable to read $path");
	}

	return array_values(array_filter(
		$lines,
		static fn (string $line): bool => preg_match('/(?:graphics(?:_jp)?|video|legacy)[\\\\\/]/i', $line) === 1,
	));
}

function assetAliasName(string $line): ?string
{
	return preg_match('/^stralias ([^,]+),/', $line, $matches) === 1 ? $matches[1] : null;
}

$deadAssetAliases = [
	'saveload_save_button_b',
	'saveload_load_button',
	'saveload_save_button',
	'saveload_load_button_b',
	'end_all00_subs',
	'setting_on',
	'setting_on1',
	'setting_off',
	'setting_off1',
];
for ($episode = 2; $episode <= 8; $episode++) {
	$deadAssetAliases[] = 'r_click_chapters_' . $episode . '_omake';
}

$assetCount = 0;
// Both hidden Grimoire image loads rely on this transparent 1px PNG.
if (!is_file($root . '/graphics_jp/menu_jp/empty.png') ||
	hash_file('sha256', $root . '/graphics_jp/menu_jp/empty.png') !==
	'11b9c95a68e295dddd0ea924647536578ce285b2c8469a223c01df1ff3166af1') {
	throw new RuntimeException('Missing or changed transparent Grimoire image stub');
}
foreach ($localeFiles as $file) {
	$englishPath = $root . '/script/en/' . $file;
	$japanesePath = $root . '/script/jp/' . $file;
	$englishAssets = assetLines($englishPath);
	$englishAssets = array_values(array_filter(
		$englishAssets,
		static fn (string $line): bool => !in_array(assetAliasName($line), $deadAssetAliases, true),
	));
	if ($file === 'code.txt') {
		$englishAssets = array_values(array_filter(
			$englishAssets,
			static fn (string $line): bool => preg_match('/(?:video|legacy)\\\\sub\\\\.*\.ass/i', $line) !== 1,
		));
	}
	$japaneseAssets = assetLines($japanesePath);
	$expectedAssets = array_map(
		static function (string $line): string {
			if (in_array(assetAliasName($line), ['r_grim', 'grim_caption'], true)) {
				return 'stralias ' . assetAliasName($line) . ',":a;graphics\\menu_jp\\empty.png"';
			}
			$line = str_replace(
				['msgwnd_en.png', 'msgwnd_ep5_en.png', 'cinema_logo_en.png', 'cinema_logo2_en.png'],
				['msgwnd_jp.png', 'msgwnd_ep5_jp.png', 'cinema_logo_jp.png', 'cinema_logo2_jp.png'],
				$line
			);
			$line = preg_replace(
				'/graphics[\\\\\/]locale(?:_en)?[\\\\\/]/i',
				'graphics\\locale_jp\\',
				$line
			);
			return preg_replace(
				'/graphics[\\\\\/]menu_en[\\\\\/]/i',
				'graphics\\menu_jp\\',
				$line
			);
		},
		$englishAssets,
	);
	if ($file === 'caches.txt') {
		// Japanese memory sources are complete PS3 screenshots, including their
		// text and cursor. It needs neither the English dialogue window nor its
		// two overlay cursors.
		$expectedAssets = [];
	}

	sort($japaneseAssets);
	sort($expectedAssets);
	if ($japaneseAssets !== $expectedAssets) {
		$limit = max(count($expectedAssets), count($japaneseAssets));
		for ($line = 0; $line < $limit; $line++) {
			$expected = $expectedAssets[$line] ?? '<missing>';
			$japanese = $japaneseAssets[$line] ?? '<missing>';
			if ($expected !== $japanese) {
				throw new RuntimeException(
					"Unexpected Japanese asset reference in $file:\n" .
					"Expected: $expected\nJapanese: $japanese"
				);
			}
		}
	}

	$assetCount += count($japaneseAssets);
}

$missing = [];
foreach ($localeFiles as $file) {
	foreach (assetLines($root . '/script/jp/' . $file) as $line) {
		if (preg_match('/graphics[\\\\\/](locale_jp|menu_jp)[\\\\\/]([^";]+\.png)/i', $line, $matches) !== 1) {
			continue;
		}
		$directory = $matches[1];
		$relative = str_replace('\\', '/', $matches[2]);
		$path = $root . '/graphics_jp/' . $directory . '/' . $relative;
		if (!is_file($path)) {
			$missing[] = $directory . '/' . $relative;
		}
	}
}
if ($missing !== []) {
	throw new RuntimeException(
		"Missing Japanese locale graphics:\n" . implode("\n", array_unique($missing))
	);
}

$menuTextPath = $root . '/update-manager/jp-menu-text.json';
$menuTextRaw = file_get_contents($menuTextPath);
if ($menuTextRaw === false) {
	throw new RuntimeException("Unable to read $menuTextPath");
}
try {
	$menuText = json_decode($menuTextRaw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
	throw new RuntimeException("Unable to decode $menuTextPath", 0, $exception);
}
if (
	!is_array($menuText)
	|| ($menuText['tips_count'] ?? null) !== 34
	|| ($menuText['tip_title_alias_count'] ?? null) !== 34
	|| ($menuText['character_alias_count'] ?? null) !== 443
	|| !is_array($menuText['tip_titles'] ?? null)
	|| count($menuText['tip_titles']) !== 34
	|| !is_array($menuText['aliases'] ?? null)
	|| count($menuText['aliases']) !== 477
) {
	throw new RuntimeException("Unexpected Japanese PS3 menu-text inventory in $menuTextPath");
}

$japaneseMenuPath = $root . '/script/jp/menu.txt';
$japaneseMenu = file_get_contents($japaneseMenuPath);
if ($japaneseMenu === false) {
	throw new RuntimeException("Unable to read $japaneseMenuPath");
}
foreach ($menuText['tip_titles'] as $name => $title) {
	if (!is_string($name) || !is_string($title)) {
		throw new RuntimeException("Invalid Japanese PS3 Tips title in $menuTextPath");
	}
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',".*' . preg_quote($title, '/') . '.*"$/mu';
	if (preg_match($pattern, $japaneseMenu) !== 1) {
		throw new RuntimeException("Unexpected Japanese PS3 Tips title for alias $name");
	}
}
foreach ($menuText['aliases'] as $name => $expected) {
	if (!is_string($name) || !is_string($expected)) {
		throw new RuntimeException("Invalid Japanese PS3 menu alias in $menuTextPath");
	}
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',"(.*)"$/m';
	$count = preg_match_all($pattern, $japaneseMenu, $matches);
	if ($count !== 1 || $matches[1][0] !== $expected) {
		throw new RuntimeException("Unexpected Japanese PS3 menu text for alias $name");
	}
}

$settingChoiceWidths = [
	'set_on' => 110, 'set_on2' => 110, 'set_off' => 110, 'set_off2' => 110,
	'set_display_mode_win' => 330, 'set_display_mode_win_on' => 330,
	'set_display_mode_full' => 320, 'set_display_mode_full_on' => 320,
	'set_display_mode_auto' => 150, 'set_display_mode_auto_on' => 150,
	'set_interface_kb_mouse' => 375, 'set_interface_kb_mouse_on' => 375,
	'set_interface_dualshock' => 300, 'set_interface_dualshock_on' => 300,
];
foreach ($settingChoiceWidths as $name => $width) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',".*\{w:' . $width . ':\}\{a:c:\}\{fit\}.*"$/m';
	if (preg_match($pattern, $japaneseMenu) !== 1) {
		throw new RuntimeException("Japanese settings choice lacks its non-overlapping column: $name");
	}
}

foreach ([
	'settings_base_off3' => 1037,
	'settings_base_off4' => 1429,
	'settings_base_off5' => 895,
	'settings_base_off6' => 1242,
	'settings_base_off7' => 1579,
] as $name => $offset) {
	if (preg_match('/^numalias ' . preg_quote($name, '/') . ',' . $offset . '$/m', $japaneseMenu) !== 1) {
		throw new RuntimeException("Japanese input-method column has the wrong offset: $name");
	}
}

// These labels are transcribed from murderer.txa, while the hints are the
// choice strings embedded in the PS3 Chiru SNR. Keep generated menu text from
// drifting back to translated paraphrases.
$officialBernPuzzleAliases = [
	'ep8_9_menu_1' => '物語を再読',
	'ep8_9_menu_2' => 'ルールの表示',
	'ep8_9_menu_3' => '紫発言確認（人物指定）',
	'ep8_9_menu_4' => '紫発言確認（各章指定）',
	'ep8_9_menu_5' => 'ヒント',
	'ep8_9_menu_6' => '犯人特定',
	'ep8_9_criminal' => '犯人を選択してください',
	'ep8_9_criminal_2' => '犯人特定',
];
$officialBernHints = [
	'信用できる紫発言を探せ', '絶対に犯人でないのは誰？', 'ト書きは真実を語る', '南條と朱志香はシロ！',
	'確実な死者をどんどん探せ', '９人の無実', 'マスターキーが使えない？', '赤き真実に違和感？',
	'確実に６人を殺した？', '犯人は閉じ込められた？', '共犯の存在', '紗音は譲治以外の誰にでも殺せる',
	'死んだフリをした犯人がいる。～', 'さらにもう１人犯人がいる？', '譲治と真里亞はニワトリとタマゴ',
	'真里亞犯人説検証', '譲治犯人説検証', '譲治の紫発言', '大人２人、子供１人。',
	'犯人の３人は家族。', '子供の犯人は戦人',
];
foreach ($officialBernHints as $index => $text) {
	$officialBernPuzzleAliases['ep8_9_hint_' . ($index + 1)] = $text;
}
foreach ($officialBernPuzzleAliases as $name => $text) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',".*' . preg_quote($text, '/') . '.*"$/mu';
	if (preg_match($pattern, $japaneseMenu) !== 1) {
		throw new RuntimeException("Bern puzzle alias does not match the official PS3 text: $name");
	}
}
$officialBernAnswerAliases = [
	'ep8_9_criminal' => ':s;#FFFFFF`{p:0:犯人を選択してください}',
	'ep8_9_criminal_2' => ':s;#FFFFFF`{p:0:犯人特定}',
];
foreach ($officialBernAnswerAliases as $name => $expected) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',"(.*)"$/m';
	$count = preg_match_all($pattern, $japaneseMenu, $matches);
	if ($count !== 1 || $matches[1][0] !== $expected) {
		throw new RuntimeException("Unexpected Japanese Bern answer alias $name");
	}
}
$officialBernRuleText = [
	'犯人の定義とは、殺人者のことである。',
	'犯人はウソをつく可能性がある。',
	'犯人は殺人以前にもウソをつく可能性がある。',
	'犯人でない人物は、真実のみを語る。',
	'犯人でない人物は、犯人に協力しない。',
	'犯人は全ての殺人を、自らの手で直接行なう。',
	'犯人が死ぬことはない。',
	'犯人は登場人物の中にいる。',
	'紫の発言は、赤き真実と同じ価値がある。',
	'ただし、犯人のみ、紫の発言でウソがつける。',
	'セリフでないト書き部分に、ウソは存在しない。',
];
foreach ($officialBernRuleText as $text) {
	if (!str_contains($japaneseMenu, $text)) {
		throw new RuntimeException("Bern puzzle rules do not match the official PS3 wording: $text");
	}
}

$officialPs3UiText = [
	'ep8_choose_cake' => 'ケーキを選んでください',
	'set_bgm_volume' => 'BGM 音量',
	'set_effect_volume' => '効果音 音量',
	'set_voice_volume' => '音声 音量',
	'set_text_speed' => 'メッセージ表示速度',
	'set_automode_speed' => 'オートモード速度',
	'set_textbox_window' => 'ウィンドウタイプ',
	'name_en3' => '寿ゆかり',
];
foreach ($officialPs3UiText as $name => $text) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',".*' . preg_quote($text, '/') . '.*"$/mu';
	if (preg_match($pattern, $japaneseMenu) !== 1) {
		throw new RuntimeException("Japanese UI alias does not use the available PS3 wording: $name");
	}
}

$japanesePortUiText = [
	'log_prev' => '前のチャプター',
	'log_next' => '次のチャプター',
	'log_hint_text' => '台詞の音声を再生するには',
	'tips_hint_text' => '文章の横に矢印がある場合は',
	'reset_hint_text' => 'ゲームをリセットしますか？',
	'jump_hint_text' => 'この台詞へ移動しますか？',
	'action_yes' => 'はい',
	'action_no' => 'いいえ',
	'save_btn' => 'セーブ',
	'save_btn_b' => 'セーブ',
	'load_btn' => 'ロード',
	'load_btn_b' => 'ロード',
];
foreach ($japanesePortUiText as $name => $text) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',".*' . preg_quote($text, '/') . '.*"$/mu';
	if (preg_match($pattern, $japaneseMenu) !== 1) {
		throw new RuntimeException("Japanese port UI alias was not localized: $name");
	}
}

$japaneseCachesPath = $root . '/script/jp/caches.txt';
$japaneseCaches = file_get_contents($japaneseCachesPath);
if ($japaneseCaches === false) {
	throw new RuntimeException("Unable to read $japaneseCachesPath");
}
if (
	str_contains($japaneseCaches, 'd2 ')
	|| str_contains($japaneseCaches, 'text_speed_t')
	|| str_contains($japaneseCaches, 'cursor0.png')
	|| str_contains($japaneseCaches, 'cursor1.png')
) {
	throw new RuntimeException('Japanese cache routine overlays text on complete PS3 memory screens');
}
foreach (['goa' => range(1, 4), 'kakera' => range(1, 10)] as $prefix => $numbers) {
	foreach ($numbers as $number) {
		$needle = "lsp s0_3,{$prefix}_memory{$number}_src,0,0";
		if (substr_count($japaneseCaches, $needle) !== 1) {
			throw new RuntimeException("Missing direct PS3 cache source: $needle");
		}
	}
}

$officialMemoryHashes = [
	'goa_memory1.png' => 'c82daa06264383ea1d51a832a2acb004c5a194880174161bd3319d93f8e66a07',
	'goa_memory2.png' => '16c177781c98a30db45a5801902fe324ebe37c340d70dde62bd60531e5d6c0b0',
	'goa_memory3.png' => '84982ec702e89fb07fcab6389e4be970f18c3d2a6a7de2ed5793fd40f32f4c05',
	'goa_memory4.png' => '83c3f53a27ff43b405857f83ba80d07686255715b3aab0168fac4c0da5584138',
	'kakera/kakera_memory1.png' => 'a312437eaa8ce8f090bc8cc3cc7cce3924c121e89a75c6e2c662b362c8b66a33',
	'kakera/kakera_memory2.png' => '732b3b02f1ef4d95406543d3bfeb6d3adf8173eb5eabf70820ee7c506884de11',
	'kakera/kakera_memory3.png' => 'cf8916b89e31b3c4f2095a814f33fab48fd50ef48eeb043e9f4b857fa250202d',
	'kakera/kakera_memory4.png' => '1731d44fc5726a2f8969f267a7f4337ec173eed590c4c7654182fc0c244bea5c',
	'kakera/kakera_memory5.png' => 'c4ce311c607466a940ec2de383b97d62f6493d52421d2bc5f3b008f86570c3db',
	'kakera/kakera_memory6.png' => 'e60924ec73da7740bee11f2f66ee58c5134e8bfcd8b7cdc33d90d639ba80f488',
	'kakera/kakera_memory7.png' => '1c0061597e1d6447aa702d8fb09e825f306e3d3f93ce232e30656cc6fed1db8b',
	'kakera/kakera_memory8.png' => '9a80e8a86273ed1e40a37723dd71479f0d7f905e563c79e8f52a2fdd332de2e3',
	'kakera/kakera_memory9.png' => '4147b181d4898f0b22348ec023d6b699878abf738659e2de7b85ef3aec8c4b10',
	'kakera/kakera_memory10.png' => 'c284403d94ad1a8fd917173523c7dc7fb11af96cc3641303d44c83ab042f053a',
];
foreach ($officialMemoryHashes as $relative => $expectedHash) {
	$path = $root . '/graphics_jp/locale_jp/' . $relative;
	if (!is_file($path) || hash_file('sha256', $path) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 memory screen $relative");
	}
}

preg_match_all('/^stralias trophy_text_(\d{2}),"\{p:18:([^ト]+)トロフィー/mu', $japaneseMenu, $trophyMatches, PREG_SET_ORDER);
if (count($trophyMatches) !== 75) {
	throw new RuntimeException('Expected 75 official Japanese PS3 trophy aliases');
}
$ranks = ['rondo' => [], 'chiru' => []];
foreach ($trophyMatches as $trophy) {
	$set = (int) $trophy[1] < 50 ? 'rondo' : 'chiru';
	$ranks[$set][$trophy[2]] = ($ranks[$set][$trophy[2]] ?? 0) + 1;
}
$expectedRanks = [
	'rondo' => ['プラチナ' => 1, 'ブロンズ' => 10, 'シルバー' => 11, 'ゴールド' => 6],
	'chiru' => ['プラチナ' => 1, 'ブロンズ' => 30, 'シルバー' => 14, 'ゴールド' => 2],
];
if ($ranks !== $expectedRanks) {
	throw new RuntimeException('Japanese trophy ranks do not match the official PS3 sets');
}
foreach (['Witchハンター', '楼座無双', '黄金の魔女の葬送曲', '金蔵からの贈り物「タワシ」'] as $officialTitle) {
	if (!str_contains($japaneseMenu, $officialTitle)) {
		throw new RuntimeException("Missing official Japanese trophy $officialTitle");
	}
}

$japaneseCredits = file_get_contents($root . '/script/jp/credits.txt');
if ($japaneseCredits === false || preg_match('/Translation|Translator|The Witch Hunt/', $japaneseCredits) === 1) {
	throw new RuntimeException('Japanese credits still contain translation credits');
}

$softSubtitleSource = '';
foreach (['menu.txt', 'code.txt', 'prefs.txt'] as $file) {
	$softSubtitleSource .= file_get_contents($root . '/script/jp/' . $file);
}
if (preg_match('/(?:video|legacy)\\\\sub\\\\|\.ass"|sub_get_|set_song_subtitles|end_all00_subs|op_ed_song_subtitles/i', $softSubtitleSource) === 1) {
	throw new RuntimeException('Japanese locale still contains video soft subtitles');
}

$menuReferences = [];
preg_match_all('/graphics[\\\\\/]menu_jp[\\\\\/]([^"`;]+?\.png)/i', $japaneseMenu, $menuMatches);
foreach ($menuMatches[1] as $relative) {
	$menuReferences[str_replace('\\', '/', $relative)] = true;
}
// These controls are referenced by the shared footer, whose menu_en paths are
// localized to menu_jp while the final Japanese script is assembled.
foreach (['config/config_left.png', 'config/config_right.png'] as $sharedFooterGraphic) {
	$menuReferences[$sharedFooterGraphic] = true;
}
// quiz_letter is a filename prefix completed dynamically by the shared footer.
for ($number = 1; $number <= 6; $number++) {
	$menuReferences['quiz/quizbtn' . $number . '.png'] = true;
}
$activeFiles = [];
$activeRoot = $root . '/graphics_jp/menu_jp';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($activeRoot)) as $file) {
	if ($file->isFile() && strtolower($file->getExtension()) === 'png') {
		$relative = str_replace('\\', '/', substr($file->getPathname(), strlen($activeRoot) + 1));
		$activeFiles[$relative] = true;
	}
}
ksort($menuReferences);
ksort($activeFiles);
if ($activeFiles !== $menuReferences || count($activeFiles) !== 298) {
	throw new RuntimeException('Active Japanese menu graphics do not match script references');
}
$emptyDirectories = [];
foreach (new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($activeRoot, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::CHILD_FIRST
) as $file) {
	if ($file->isDir() && (new FilesystemIterator($file->getPathname()))->valid() === false) {
		$emptyDirectories[] = substr($file->getPathname(), strlen($activeRoot) + 1);
	}
}
if ($emptyDirectories !== []) {
	throw new RuntimeException('Empty directories in active Japanese menu tree: ' . implode(', ', $emptyDirectories));
}

$officialGraphicHashes = [
	'quiz/ask_auntie_eva.png' => 'db733aa3cc8f338be5d2889c7e1090fd2999ce05851b15e9a5bd8a3925f6f7eb',
	'quiz/m1.png' => 'ed99f57d709f1c21de02bfd000b976a03cb54a65eaa8d8e21af7a623fc70f3f9',
	'quiz/m2.png' => '74cb64db713c932c53f7049a141364cfedefb048bebfa2053ec9ff93d375e207',
	'save/saveload_area.png' => '661b6edac50a637db8dd211b7861cd937cfef2e61fbeb5e09e233a1365624a83',
	'save/saveload_area_5.png' => 'a5b5ee3cb48a3557b4863c85cd4125631f1b6f706a03b7aa64ec0ded0da7a1c3',
	'quiz2/quiz2_front_2.png' => '795fd6e8391169225fb1d0aacf1e62a7a9109d0df6bd758fee90bae2c4563130',
];
foreach ($officialGraphicHashes as $relative => $expectedHash) {
	if (hash_file('sha256', $activeRoot . '/' . $relative) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 menu graphic $relative");
	}
}

$generatedGraphicHashes = [
	'trophy/trophy_caption.png' => '10f46825ce02e4393c20b8e746cc53e9f6ccc8efb34016999889bc54e5c50f9d',
	'SystemBtn/yes.png' => '4da7cc2cad14ffcad3a0b935d8560ed6f48b5a2329880dcab21ce2c7eadb90a9',
	'SystemBtn/no.png' => '57a68b229b2f2ccae72961a606442a2b6f7cd274ed8ecba750728da691b1c88b',
	'SystemBtn/title_bg.png' => 'eb701937cf4240077e1e641543cd0fd54fb624e97bd9e97ab65874b8b355597e',
	'title_menu/yes.png' => '4da7cc2cad14ffcad3a0b935d8560ed6f48b5a2329880dcab21ce2c7eadb90a9',
	'title_menu/no.png' => '57a68b229b2f2ccae72961a606442a2b6f7cd274ed8ecba750728da691b1c88b',
	'jump/EP1.png' => 'd207ccb55a1e0c6cdf796a3306971f010cd311f7c4110c0e7e29ab93135a118b',
	'title/chiru/title1_text_ep1_4.png' => '82012599ef402f3ceb93d29f0a36e7784777cf8cbf0ffe29a49acfad293717e6',
	'title/title1_text_ep5_8.png' => '5f13bfe7a705cfc9f9a16b41c85358f0d50edd828afcb4261b45cd1483fb536a',
	'title/title1_text_exit.png' => 'f42fad061a2aff475659367b0ee5969d6108bfb59946a84106bfa6e0a72a16da',
	'title/title1_text_unlock.png' => 'd08c64c70ead4f2810729fa90bb6352654b51b99c99ca808db68fe5c700e7407',
	'title/title1_text_warning.png' => '52cdab4b2f99c81b906ddf566a5571bcb166765fc172e6e100ad29c57ea7e54d',
	'title/title1_text_web.png' => 'a9a3ebe979056de5e5c1fee89f44a8084399ed54d3298c742b3c8fc989363540',
	'title_menu/unlock_kaku_bg.png' => '4073a12236e28ad8d9636c008a32155e8c4a60df1a926d12dbf70d7750b4af26',
];
foreach ($generatedGraphicHashes as $relative => $expectedHash) {
	if (hash_file('sha256', $activeRoot . '/' . $relative) !== $expectedHash) {
		throw new RuntimeException("Unexpected generated Japanese menu graphic $relative");
	}
}

$cinemaLogoHashes = [
	'cinema_logo_jp.png' => 'a27b8dccb5bae04de7aee327a1de7fe26994e2d46dd026fdd599d14b6fe010b9',
	'cinema_logo2_jp.png' => 'ed9536bcc172a39a2d3cc06d285e9d66c3970e69db9319cfd39e3aa434cf71be',
];
foreach ($cinemaLogoHashes as $filename => $expectedHash) {
	if (hash_file('sha256', $root . '/graphics_jp/system/logo/' . $filename) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 cinema logo $filename");
	}
}

$messageWindowHashes = [
	'msgwnd_jp.png' => '1171c86caf191b223a6e87bca197dc8a3a8e9ca3c1575773e394762254c86a57',
	'msgwnd_ep5_jp.png' => 'ff6a7d330059175f7aa85be5b24b67397d1a993b40de1fa3b47c70b5722475c7',
];
foreach ($messageWindowHashes as $filename => $expectedHash) {
	$path = $root . '/graphics_jp/system/wnd/' . $filename;
	if (!is_file($path) || hash_file('sha256', $path) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 message window $filename");
	}
}

echo "Verified $assetCount Japanese asset references, 14 direct PS3 memory screens, two PS3 message windows, 298 active menu graphics, localized port controls, no empty menu directories, no soft subtitles, 75 trophies, credits, and hidden Grimoire navigation" . PHP_EOL;
