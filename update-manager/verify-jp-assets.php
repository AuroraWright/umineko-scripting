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
// Hidden Grimoire loads and the inaccessible Episode 1 Omake chapter card
// rely on this transparent 1px PNG.
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
			if (in_array(assetAliasName($line), ['r_grim', 'grim_caption', 'r_click_chapters_1_omake'], true)) {
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

$japaneseHeader = file_get_contents($root . '/script/jp/header.txt');
if ($japaneseHeader === false || preg_match('/^numalias criminal_header_y,160$/m', $japaneseHeader) !== 1) {
	throw new RuntimeException('Japanese Bern culprit heading does not match the shared layout');
}
if (preg_match('/^numalias criminal_button_y,883$/m', $japaneseHeader) !== 1) {
	throw new RuntimeException('Japanese Bern culprit button overlaps the bottom profile row');
}
$sharedScript = file_get_contents($root . '/script/umi_ftr.txt');
if ($sharedScript === false || !str_contains(
	$sharedScript,
	'if localisation == "jp" scrollable_cfg textmargintop,95,0 : scrollable_cfg lastmargin,95,15'
)) {
	throw new RuntimeException('Bern hint scrollable lacks the Japanese layout override');
}

// The main controls are transcribed from murderer.txa, the hints are choice
// strings embedded in the PS3 Chiru SNR, and the chapter/topic selectors are
// transcribed from PS3 gameplay captures. Keep generated menu text from
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
	'ep8_9_page_1' => '第一の晩',
	'ep8_9_page_2' => '第二の晩',
	'ep8_9_page_3' => '第四の晩',
	'ep8_9_page_4' => '第五・六の晩',
	'ep8_9_page_5' => '第七の晩',
	'ep8_9_page_6' => '第八の晩',
	'ep8_9_page_1_1' => '６人の死体',
	'ep8_9_page_1_2' => '検死',
	'ep8_9_page_1_3' => '現場は密室か',
	'ep8_9_page_1_4' => '源次のマスターキー',
	'ep8_9_page_1_5' => 'マスターキーについて',
	'ep8_9_page_2_1' => '夏妃と蔵臼の部屋',
	'ep8_9_page_2_2' => '検死と状況',
	'ep8_9_page_2_3' => '使用人のアリバイ',
	'ep8_9_page_2_4' => '現場の保全',
	'ep8_9_page_2_5' => '屋敷の構造',
	'ep8_9_page_2_6' => '第一の晩の犯人',
	'ep8_9_page_2_7' => '部屋の封印について',
	'ep8_9_page_3_1' => '消えた紗音と嘉音',
	'ep8_9_page_3_2' => 'アリバイ',
	'ep8_9_page_3_3' => '紗音のマスターキー',
	'ep8_9_page_3_4' => 'マスターキーと嘉音',
	'ep8_9_page_4_1' => '郷田と熊沢の遺体',
	'ep8_9_page_4_2' => 'アリバイ',
	'ep8_9_page_4_3' => '現場の保全は？',
	'ep8_9_page_5_1' => '南條の遺体',
	'ep8_9_page_5_2' => '４人の中に犯人が？',
	'ep8_9_page_6_1' => '朱志香の遺体',
	'ep8_9_page_6_2' => '誰が殺した？',
];
$officialBernHints = [
	'信用できる紫発言を探せ。', '絶対に犯人でないのは誰？', 'ト書きは真実を語る。', '南條と朱志香はシロ！',
	'確実な死者をどんどん探せ。', '９人の無実。', 'マスターキーが使えない？', '赤き真実に違和感？',
	'確実に６人を殺した？', '犯人は閉じ込められた？', '共犯の存在。', '紗音は譲治以外の誰にでも殺せる。',
	'死んだフリをした犯人がいる。そしてもう１人犯人がいる。', 'さらにもう１人犯人がいる？', '譲治と真里亞はニワトリとタマゴ。',
	'真里亞犯人説検証。', '譲治犯人説検証。', '譲治の紫発言。', '大人２人、子供１人。',
	'犯人の３人は家族。', '子供の犯人は戦人。',
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
foreach (array_merge(['ep8_9_hint_unknown'], array_map(
	static fn (int $hint): string => 'ep8_9_hint_' . $hint,
	range(1, 21)
)) as $name) {
	if (preg_match('/^stralias ' . preg_quote($name, '/') . ',"\{p:36:.*\}"$/mu', $japaneseMenu) !== 1) {
		throw new RuntimeException("Bern hint does not match the chapter-topic text size: $name");
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
	'犯人は全ての殺人を、自らの手で直接行う。',
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
if (!str_contains($japaneseMenu, 'stralias ep8_9_rules,":s;#FFFFFF`{p:34:')) {
	throw new RuntimeException('Bern puzzle rules do not use the PS3-sized Japanese preset');
}
$japaneseCode = file_get_contents($root . '/script/jp/code.txt');
if ($japaneseCode === false || !str_contains(
	$japaneseCode,
	'preset_define 34,1,55,#FFFFFF,0,0,0,1,8,#000000,0,0,0,#000000,2,55,1590'
)) {
	throw new RuntimeException('Missing or changed PS3-sized Bern puzzle rules preset');
}
if (!str_contains(
	$japaneseCode,
	'preset_define 35,1,3,#FFFFFF,0,0,0,0,0,#000000,0,0,0,#000000,0,3,1590'
)) {
	throw new RuntimeException('Missing or changed Bern puzzle rule spacer preset');
}
if (!str_contains(
	$japaneseCode,
	'preset_define 36,2,50,#FFFFFF,1,0,0,1,10,#000000,0,0,0,#000000,3,50,1500'
)) {
	throw new RuntimeException('Missing or changed Bern hint-row preset');
}
if (substr_count($japaneseMenu, '{n}{p:35:　}{n}') !== 10) {
	throw new RuntimeException('Bern puzzle rules do not contain the expected vertical spacers');
}
foreach (['ep8_9_page_1_5_t', 'ep8_9_page_2_5_t', 'ep8_9_page_2_6_t', 'ep8_9_page_2_7_t', 'ep8_9_page_3_4_t'] as $name) {
	if (preg_match('/^stralias ' . preg_quote($name, '/') . ',".*。\}+"$/mu', $japaneseMenu) !== 1) {
		throw new RuntimeException("Bern puzzle red-section statement lacks final punctuation: $name");
	}
}
$bernSpacingChecks = [
	// The PS3 capture presents the interior-lock clarification as its own red
	// statement rather than a continuation of the preceding one.
	'マスターキーでしか行えない。}{n}{n}{p:1:もちろん、部屋の内側からは',
	// Chapter view: blank line between speaker/statement blocks, but the
	// statement remains directly below its character name.
	'」{n}{n}熊沢{n}「',
	// Character view: blank line between statements, but the first statement
	// remains directly below its night heading.
	'■第一の晩{n}「それで後は大騒ぎだ。',
	'集まった}」{n}{n}「',
	// Red statements have no speaker heading, so the blank line goes directly
	// between their complete statement blocks.
	'施錠、開錠が可能。}{n}{n}{p:1:マスターキー以外',
];
foreach ($bernSpacingChecks as $spacing) {
	if (!str_contains($japaneseMenu, $spacing)) {
		throw new RuntimeException('Bern puzzle statement spacing does not match the Japanese layout');
	}
}
$bernStart = strpos($japaneseMenu, ';bern quiz');
$bernEnd = $bernStart === false ? false : strpos($japaneseMenu, '; trophies', $bernStart);
if ($bernStart === false || $bernEnd === false) {
	throw new RuntimeException('Unable to locate the generated Bern puzzle aliases');
}
$bernMenu = substr($japaneseMenu, $bernStart, $bernEnd - $bernStart);
if (preg_match('/(?:■[^{}]+|(?:蔵臼|夏妃|絵羽|秀吉|留弗夫|霧江|楼座|戦人|朱志香|譲治|真里亞|源次|紗音|嘉音|郷田|熊沢|南條))\{n\}\{n\}「/u', $bernMenu) === 1) {
	throw new RuntimeException('Bern puzzle has a blank line directly below a heading');
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
	'jump_hint_text' => 'この台詞の場面へ移動しますか？',
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

// Byte hashes below describe the final files after the documented
// `oxipng -r -o 2 --nx graphics_jp` pass.
$officialMemoryHashes = [
	'goa_memory1.png' => '69778b0f05413f73e4e4cc1d8118d7cbf11997a9c037acad43af79e1224b984c',
	'goa_memory2.png' => 'e2fe437ce0cdd71f49a8e2704e61e280b7976ccc2de06a9c5f495287d5853781',
	'goa_memory3.png' => 'bf806af61568aeaede05181f62495696aa6e0ae8f39950a41787429dc81039d4',
	'goa_memory4.png' => 'b0ef62d9aa524b251b312e1def46787a6189738257a6cc35eb748c911ac02dd8',
	'kakera/kakera_memory1.png' => '9a2317aad635170607c46e49457db4b9a01aa284691884b86d45da698e75e15d',
	'kakera/kakera_memory2.png' => '619794223df8f9dd0af46a79012ead7dfb9deff00adbb256fadfdbef59a5f375',
	'kakera/kakera_memory3.png' => 'b53ebe6f4ab849003af52f3ed088d048eaf6d509403c4b6a46a2871c1cd516d5',
	'kakera/kakera_memory4.png' => '0ebb575c152cc865dcb4aac9ba8868d943a17152b884e018453138ebffd074e3',
	'kakera/kakera_memory5.png' => 'b3d7ebeb1011b135a1702c7b365f85afd67737bf16fc9d5b6bfc6cbc78068d9f',
	'kakera/kakera_memory6.png' => '4bcbc40494c514f28e4ff5e35ae403bd02d4b22d2955d58602996cd22127dbd5',
	'kakera/kakera_memory7.png' => '68bbf8ab276e0323b690fcedf290c232270837b73e1b70407b4ad8c1bfc4bd6b',
	'kakera/kakera_memory8.png' => 'ee9553ac38216fa3aae00941429e405a6593abb8d147211697f4ada332b1e53b',
	'kakera/kakera_memory9.png' => '22de2f925b9566710a7a9365f88a4aca1329f10af4c20887b60a71b552d9a5fa',
	'kakera/kakera_memory10.png' => '5c0e24f8026a413765d5885eab24b30f60f6fceb81988bece2bbe3631836bf1a',
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
if ($activeFiles !== $menuReferences || count($activeFiles) !== 297) {
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
	'quiz/ask_auntie_eva.png' => 'ce7bbdc1254e49236eef1d5129b4317a943ca13cba7fd7431443f9d29cb58dc5',
	'quiz/m1.png' => '55f1b7c1992ddc928a811d7eb9265b77dd24f2096ed22be58064dbfe6a4dd3b9',
	'quiz/m2.png' => '4905f0cd195a36882f93b3fe0d92facfe01bda218b67f803414d04b8419feac7',
	'save/saveload_area.png' => '7be3cee5c50b138b933f37fb44811e0f75481d29fd619918c582d766b07fa054',
	'save/saveload_area_5.png' => '24f5fe51da0155f87bd6136ace9b74143ee440f48a0d2877a32228423baab2e0',
	'quiz2/quiz2_front_2.png' => '084ec7ee4380fa73ace327f7a83e25592cc6de7ab2fbe4643a5fea943ed306ca',
];
foreach ($officialGraphicHashes as $relative => $expectedHash) {
	if (hash_file('sha256', $activeRoot . '/' . $relative) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 menu graphic $relative");
	}
}

$generatedGraphicHashes = [
	'trophy/trophy_caption.png' => 'b0aa98d5c090e32b166a599a0bc5e016960ce9f494d6f16d37279187199d0a3c',
	'SystemBtn/yes.png' => '8cfbbd7639ea469af9451b744aad182c7d6a697061ecdf57bd45537b986539e2',
	'SystemBtn/no.png' => 'e7bd45ee20e8921812d2be30550460075c59f9f3eb8e5d80316ef86688669bd3',
	'SystemBtn/title_bg.png' => '86e9074f83fc4fc8db05dabd3f3a88669b43d4fa167c3de0f8ea020ace96e70d',
	'title_menu/yes.png' => '8cfbbd7639ea469af9451b744aad182c7d6a697061ecdf57bd45537b986539e2',
	'title_menu/no.png' => 'e7bd45ee20e8921812d2be30550460075c59f9f3eb8e5d80316ef86688669bd3',
	'jump/EP1.png' => 'bcd0b2a4dab26a9343d86a54d828c7a7051d1f3d4e96d30027379535e72f037e',
	'title/chiru/title1_text_ep1_4.png' => 'fd546ebcacd408227a1125d76a04e1a859d212a212b3325d6a847c68d5af5d3d',
	'title/title1_text_ep5_8.png' => '4f251e740ac527b3733dfa1526a3844e73736dc7a99dfc5d2b15c2db9754caca',
	'title/title1_text_exit.png' => 'db09fae66d79290cea940776b66fcd96a09fd1e46c65896bf73ce2f6740496d9',
	'title/title1_text_unlock.png' => 'b32392f2142e1f7d6ba9a4cad1b876284d362b1dd77574f1b4160171881266f3',
	'title/title1_text_warning.png' => 'f8fb0d9767e9717a005867ee9b6e0ef56c33c6b2f278ebf574a83b8f81d76a9e',
	'title/title1_text_web.png' => '77874d6d074c65349512d88337dedad9b0ac1bcd3a19495eb9074c2657f818f2',
	'title_menu/unlock_kaku_bg.png' => '32918d662645f8cdde5817eb382ca6d84584456add7651a534eb1e4cd957936d',
];
foreach ($generatedGraphicHashes as $relative => $expectedHash) {
	if (hash_file('sha256', $activeRoot . '/' . $relative) !== $expectedHash) {
		throw new RuntimeException("Unexpected generated Japanese menu graphic $relative");
	}
}

$cinemaLogoHashes = [
	'cinema_logo_jp.png' => '647bf76e821a8775758313a1f90bde290247343f0d19a01b096ab9f5c04d982d',
	'cinema_logo2_jp.png' => 'db017c52bf95b981feac95e69c1937ede7335028e0b58c430b587bb0733d0dd6',
];
foreach ($cinemaLogoHashes as $filename => $expectedHash) {
	if (hash_file('sha256', $root . '/graphics_jp/system/logo/' . $filename) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 cinema logo $filename");
	}
}

$messageWindowHashes = [
	'msgwnd_ep5_jp.png' => '3d5f6a4a229583c25a48767ba335dc4e944a9ca83d894bfa7d643286fa0379ab',
];
if (is_file($root . '/graphics_jp/system/wnd/msgwnd_jp.png')) {
	throw new RuntimeException('Redundant Japanese Rondo message-window overlay is present');
}
foreach ($messageWindowHashes as $filename => $expectedHash) {
	$path = $root . '/graphics_jp/system/wnd/' . $filename;
	if (!is_file($path) || hash_file('sha256', $path) !== $expectedHash) {
		throw new RuntimeException("Unexpected Japanese PS3 message window $filename");
	}
}

echo "Verified $assetCount Japanese asset references, 14 direct PS3 memory screens, one overlaid PS3 message window, 297 active menu graphics, localized port controls, no empty menu directories, no soft subtitles, 75 trophies, credits, and hidden Grimoire navigation" . PHP_EOL;
