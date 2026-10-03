<?php

/*
 * Rebuild the parts of script/jp/menu.txt whose Japanese text already exists
 * in the aligned story files. Run from any directory with:
 *
 *   php update-manager/generate-jp-menu.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$storyPath = $root . '/story/ep8/jp/umi8_9.txt';
$englishMenuPath = $root . '/script/en/menu.txt';
$menuPath = $root . '/script/jp/menu.txt';
$ps3MenuTextPath = $root . '/update-manager/jp-menu-text.json';
$snrXmlPaths = [
	$root . '/snr/output/script_rondo.xml',
	$root . '/snr/output/script_chiru.xml',
];

$rawLines = file($storyPath, FILE_IGNORE_NEW_LINES);
if ($rawLines === false) {
	throw new RuntimeException("Unable to read $storyPath");
}

$story = [];
foreach ($rawLines as $index => $line) {
	if (!str_starts_with($line, '`') || !str_ends_with($line, '`')) {
		throw new RuntimeException('Malformed story line ' . ($index + 1));
	}
	$story[$index + 1] = substr($line, 1, -1);
}

function storyText(array $story, int $first, ?int $last = null): string
{
	$last ??= $first;
	$text = '';
	for ($line = $first; $line <= $last; $line++) {
		if (!array_key_exists($line, $story)) {
			throw new RuntimeException("Missing story line $line");
		}
		$text .= $story[$line];
	}
	return $text;
}

function storyEntry(array $story, string $speaker, int $first, ?int $last = null): string
{
	return $speaker . '{n}' . storyText($story, $first, $last);
}

function storySection(array $story, string $heading, array $ranges): string
{
	$quotes = [];
	foreach ($ranges as $range) {
		$quotes[] = storyText($story, $range[0], $range[1] ?? null);
	}
	return '■' . $heading . '{n}' . implode('{n}{n}', $quotes);
}

function stripDialogueQuotes(string $text): string
{
	if (str_starts_with($text, '「')) {
		$text = substr($text, strlen('「'));
	}
	if (str_ends_with($text, '」')) {
		$text = substr($text, 0, -strlen('」'));
	}
	return $text;
}

function menuAlias(string $menu, string $name, string $path): string
{
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',"(.*)"(?:\s*;.*)?$/m';
	$count = preg_match_all($pattern, $menu, $matches);
	if ($count !== 1) {
		throw new RuntimeException("Expected exactly one alias named $name in $path");
	}
	return $matches[1][0];
}

$aliases = [];
$aliases['msgwnd_wnd'] = 'graphics\system\wnd\msgwnd_jp.png';
$aliases['msgwnd_wnd_ep5'] = 'graphics\system\wnd\msgwnd_ep5_jp.png';
$englishMenu = file_get_contents($englishMenuPath);
if ($englishMenu === false) {
	throw new RuntimeException("Unable to read $englishMenuPath");
}

// The two galleries mirror the official Japanese PS3 trophy sets (Rondo:
// NPWR01598, Chiru: NPWR02896). Preserve their PSN text and ranks.
$trophies = [
	'00' => ['プラチナ', 'Witchハンター', '全てのトロフィーを集める。'],
	'01' => ['ブロンズ', 'ようこそ、六軒島へ', 'Episode1を開始する。'],
	'02' => ['ブロンズ', 'Episode1 Tipsハンター', 'Episode1のTipsを全て閲覧する。'],
	'03' => ['ブロンズ', 'Episode1 Charactersハンター', 'Episode1のCharactersを全て閲覧する。'],
	'04' => ['ブロンズ', 'Episode2 Tipsハンター', 'Episode2のTipsを全て閲覧する。'],
	'05' => ['ブロンズ', 'Episode2 Charactersハンター', 'Episode2のCharactersを全て閲覧する。'],
	'06' => ['ブロンズ', 'Episode3 Tipsハンター', 'Episode3のTipsを全て閲覧する。'],
	'07' => ['ブロンズ', 'Episode3 Charactersハンター', 'Episode3のCharactersを全て閲覧する。'],
	'08' => ['ブロンズ', 'Episode4 Tipsハンター', 'Episode4のTipsを全て閲覧する。'],
	'09' => ['ブロンズ', 'Episode4 Charactersハンター', 'Episode4のCharactersを全て閲覧する。'],
	'10' => ['ブロンズ', '設定変更', 'Configで設定を変更する。'],
];
for ($episode = 1, $id = 11; $episode <= 4; $episode++) {
	$trophies[sprintf('%02d', $id++)] = ['シルバー', "Episode$episode お茶会", "Episode$episode お茶会を最後まで読む。"]; 
	$trophies[sprintf('%02d', $id++)] = ['シルバー', "Episode$episode 裏お茶会", "Episode$episode 裏お茶会(????)を最後まで読む。"]; 
}
$trophies += [
	'19' => ['シルバー', 'とある力の積み重ね', 'プレイ時間が50時間を超える。 しかし157680時間には到底及ばない。'],
	'20' => ['シルバー', 'クリック回数がこんなにおおいわけがない', 'メッセージ送りが30000回を超える。 金蔵さんならやりかねませんな。'],
	'21' => ['シルバー', '楼座無双', '黄金の夢を「うをおおおおおおおおおおオオオォオオオォオッ!!!」と見せてもらう。'],
	'22' => ['ゴールド', '黄金の魔女の伝説', 'Episode1を最後まで読む。'],
	'23' => ['ゴールド', '黄金の魔女の手番', 'Episode2を最後まで読む。'],
	'24' => ['ゴールド', '黄金の魔女の晩餐', 'Episode3を最後まで読む。'],
	'25' => ['ゴールド', '黄金の魔女の同盟', 'Episode4を最後まで読む。'],
	'26' => ['ゴールド', 'CGハンター', 'Picture Boxの達成率が100%になる。隠された扉はプラチナの称号を得たときに開かれるだろう……。'],
	'27' => ['ゴールド', 'Musicハンター', 'Music Boxの達成率が100%になる。'],
	'50' => ['プラチナ', 'Witchハンター', '全てのトロフィーを集める。'],
	'51' => ['ブロンズ', 'お帰りなさいませ、六軒島へ。', 'Episode5を開始する。'],
	'52' => ['ブロンズ', 'Episode5 Tipsハンター', 'Episode5のTipsを全て閲覧する。'],
	'53' => ['ブロンズ', 'Episode5 Charactersハンター', 'Episode5のCharactersを全て閲覧する。'],
	'54' => ['ブロンズ', 'Episode6 Tipsハンター', 'Episode6のTipsを全て閲覧する。'],
	'55' => ['ブロンズ', 'Episode6 Charactersハンター', 'Episode6のCharactersを全て閲覧する。'],
	'56' => ['ブロンズ', 'Episode7 Tipsハンター', 'Episode7のTipsを全て閲覧する。'],
	'57' => ['ブロンズ', 'Episode7 Charactersハンター', 'Episode7のCharactersを全て閲覧する。'],
	'58' => ['ブロンズ', 'Episode8 Charactersハンター', 'Episode8のCharactersを全て閲覧する。'],
	'59' => ['ブロンズ', '設定変更', 'Configで設定を変更する。'],
];
$gifts = ['タワシ', '鯖缶', '片翼の紋章の低反発まくら', '旧式スクール水着（白）', '完熟マンゴー', '門松', 'エンジェルモートの制服', '金蔵のブロマイド写真', '金蔵の声の笑い袋', '片翼の鷲の鯉のぼり', '黄金の薔薇のブーケ', '代々伝わるオルゴール', '量産型さくたろうのぬいぐるみ', '紋章入りのティーセット', '紋章入りの懐中時計', 'ベアトリーチェのコサージュ', '戦人のぬいぐるみ', 'ベアトリーチェのぬいぐるみ'];
foreach ($gifts as $offset => $gift) {
	$trophies[(string) (60 + $offset)] = ['ブロンズ', "金蔵からの贈り物「{$gift}」", "金蔵から「{$gift}」をプレゼントされた。"]; 
}
$trophies += [
	'78' => ['ブロンズ', 'ノーヒント', '全クイズをノーヒントで解いた。'],
	'79' => ['ブロンズ', '銀幕の向こうのできごと', '金蔵からのプレゼントを全て（全18種）捨てた。'],
	'80' => ['ブロンズ', 'Episode5 お茶会', 'Episode5 お茶会を最後まで読む。'],
	'81' => ['シルバー', 'Episode5 裏お茶会', 'Episode5 裏お茶会(????)を最後まで読む。'],
	'82' => ['シルバー', 'Episode6 お茶会', 'Episode6 お茶会を最後まで読む。'],
	'83' => ['シルバー', 'Episode6 裏お茶会', 'Episode6 裏お茶会(????)を最後まで読む。'],
	'84' => ['シルバー', 'Episode7 お茶会', 'Episode7 お茶会を最後まで読む。'],
	'85' => ['シルバー', 'Episode7 裏お茶会', 'Episode7 裏お茶会(????)を最後まで読む。'],
	'86' => ['シルバー', 'Episode8 お茶会', 'Episode8 お茶会を最後まで読む。'],
	'87' => ['シルバー', 'Episode8 裏お茶会', 'Episode8 裏お茶会(????)を最後まで読む。'],
	'88' => ['シルバー', 'とある力の積み重ね', 'プレイ時間が50時間を超える。 しかし157680時間には到底及ばない。'],
	'89' => ['シルバー', 'クリック回数がこんなにおおいわけがない', 'メッセージ送りが30000回を超える。 金蔵さんならやりかねませんな。'],
	'90' => ['シルバー', '黄金の魔女の終わり', 'Episode5を最後まで読む'],
	'91' => ['シルバー', '黄金の魔女の夜明け', 'Episode6を最後まで読む。'],
	'92' => ['シルバー', '黄金の魔女の葬送曲', 'Episode7を最後まで読む。'],
	'93' => ['シルバー', '黄金の魔女の黄昏 ～手品～', 'Episode8の手品ルートを最後まで読む'],
	'94' => ['シルバー', '黄金の魔女の黄昏 ～魔法～', 'Episode8の魔法ルートを最後まで読む'],
	'95' => ['ゴールド', 'CGハンター', 'Picture Boxの達成率が100%になる。 隠された扉はプラチナの称号を得たときに開かれるだろう……。'],
	'96' => ['ゴールド', 'Musicハンター', 'Music Boxの達成率が100%になる。'],
];
if (count($trophies) !== 75) {
	throw new RuntimeException('Expected exactly 75 official PS3 trophies');
}
$aliases['trophy_text_locked'] = '{p:18:トロフィーはロックされています}';
$aliases['trophy_text_new'] = ':s;#FFFFFF`{p:11:{w:846:}{a:c:}{fit}トロフィーメニューに新しいトロフィーが追加されました！}`';
foreach ($trophies as $id => [$rank, $title, $description]) {
	$aliases['trophy_text_' . $id] = "{p:18:{$rank}トロフィー{n}$title{n}$description}";
}

// The PS3 script stores the canonical Japanese chapter captions.  Keep only
// the eight main episodes: tea parties and hidden tea parties are represented
// as separate EPISODE sections in the SNR too.
$chapterSections = [];
$episodeTitles = [];
$currentEpisode = null;
foreach ($snrXmlPaths as $xmlPath) {
	$xml = file_get_contents($xmlPath);
	if ($xml === false) {
		throw new RuntimeException("Unable to read $xmlPath");
	}
	preg_match_all(
		'/<ins type="SECTION_START"[^>]*section_type="(EPISODE|CHAPTER)" section="([^"]*)"/',
		$xml,
		$sections,
		PREG_SET_ORDER
	);
	foreach ($sections as $section) {
		$type = $section[1];
		$text = html_entity_decode($section[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
		if ($type === 'EPISODE') {
			$currentEpisode = null;
			if (preg_match('/^Episode([1-8])\b/', $text, $episodeMatch) === 1) {
				$candidate = (int) $episodeMatch[1];
				if (!array_key_exists($candidate, $chapterSections)) {
					$currentEpisode = $candidate;
					$chapterSections[$candidate] = [];
					$episodeTitles[$candidate] = $text;
				}
			}
			continue;
		}
		if ($currentEpisode !== null) {
			$chapterSections[$currentEpisode][] = $text;
		}
	}
}

$expectedChapterCounts = [1 => 18, 2 => 19, 3 => 19, 4 => 20, 5 => 16, 6 => 19, 7 => 19, 8 => 17];
foreach ($expectedChapterCounts as $episode => $expectedCount) {
	$sections = $chapterSections[$episode] ?? [];
	if (count($sections) !== $expectedCount) {
		throw new RuntimeException("Expected $expectedCount Episode $episode chapter sections, found " . count($sections));
	}
	foreach ($sections as $index => $section) {
		$alias = 'date_scenario_' . $episode . '_' . ($index === 0 ? 'op' : $index);
		$date = '';
		if (preg_match('/^(?:(?:1986 )?\d{1,2}\/\d{1,2}(?:（[^）]+）)?\s*(?:\d{1,2}:\d{2}|--:--)|1986 \d{1,2}\/\d{1,2})/u', $section, $dateMatch) === 1) {
			$date = $dateMatch[0];
		}
		$aliases[$alias] = $date;
		$title = trim(substr($section, strlen($date)));
		$aliases['scenario_' . $episode . '_' . ($index === 0 ? 'op' : $index)] = $title;
		$saveSuffix = $index === 0 ? 'op' : sprintf('%02d', $index);
		$aliases['save_' . $episode . '_' . $saveSuffix] = ':s;#FFFFFF`{p:30:'
			. preg_replace('/^Episode/', 'Episode ', $episodeTitles[$episode])
			. '.{n}' . $section . '}`';
	}
	$episodeHeading = ':s;#FFFFFF`{p:30:' . preg_replace('/^Episode/', 'Episode ', $episodeTitles[$episode]) . '.{n}';
	$aliases['save_' . $episode . '_tea'] = $episodeHeading . 'お茶会}`';
	$aliases['save_' . $episode . '_ura'] = $episodeHeading . '????}`';
}
$aliases['save_1_xx'] = ':s;#FFFFFF`{p:30:' . preg_replace('/^Episode/', 'Episode ', $episodeTitles[1]) . '.{n}おまけ1}`';
$aliases['date_scenario_episode'] = 'エピソード';
$aliases['date_scenario_op_chapter'] = '序章';
for ($chapter = 1; $chapter <= 25; $chapter++) {
	$aliases['date_scenario_' . $chapter . '_chapter'] = '第' . $chapter . '章';
}

$ps3MenuTextRaw = file_get_contents($ps3MenuTextPath);
if ($ps3MenuTextRaw === false) {
	throw new RuntimeException("Unable to read $ps3MenuTextPath");
}
try {
	$ps3MenuText = json_decode($ps3MenuTextRaw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
	throw new RuntimeException("Unable to decode $ps3MenuTextPath", 0, $exception);
}
if (
	!is_array($ps3MenuText)
	|| ($ps3MenuText['tips_count'] ?? null) !== 34
	|| ($ps3MenuText['tip_title_alias_count'] ?? null) !== 34
	|| ($ps3MenuText['character_alias_count'] ?? null) !== 443
	|| !is_array($ps3MenuText['tip_titles'] ?? null)
	|| count($ps3MenuText['tip_titles']) !== 34
	|| !is_array($ps3MenuText['aliases'] ?? null)
	|| count($ps3MenuText['aliases']) !== 477
) {
	throw new RuntimeException("Unexpected Japanese PS3 menu-text inventory in $ps3MenuTextPath");
}
foreach ($ps3MenuText['tip_titles'] as $name => $title) {
	if (!is_string($name) || !is_string($title)) {
		throw new RuntimeException("Invalid Japanese PS3 Tips title in $ps3MenuTextPath");
	}
	$aliases[$name] = ':s;#C7C7C7#FFFFFF`{p:13:{fit}{w:700:}' . $title . '}';
}
// The three Episode 8 placeholders are port additions and have no PS3 Tips
// records. Retain their established identifiers without presenting them as
// extracted text.
foreach (['81', '82', '83'] as $index => $title) {
	$aliases['r_tips_8_' . ($index + 1)] = ':s;#C7C7C7#FFFFFF`{p:13:{fit}{w:700:}' . $title . '}';
}
foreach ($ps3MenuText['aliases'] as $name => $value) {
	if (!is_string($name) || !is_string($value)) {
		throw new RuntimeException("Invalid Japanese PS3 menu alias in $ps3MenuTextPath");
	}
	$aliases[$name] = $value;
}

$aliases += [
	'r_resu' => ':s;#FFFFFF#FF0000`{p:12:復活}',
	'r_exec' => ':s;#FFFFFF#FF0000`{p:12:処刑}',
	'r_people' => ':s;#FFFFFF#FF0000`{p:12:人間側}',
	'r_people_select' => ':s;#FF0000`{p:12:人間側}',
	'r_witches' => ':s;#FFFFFF#FF0000`{p:12:魔女側}',
	'r_witches_select' => ':s;#FF0000`{p:12:魔女側}',
	'r_ange' => ':s;#FFFFFF#FF0000`{p:12:縁寿側}',
	'r_ange_select' => ':s;#FF0000`{p:12:縁寿側}',
	'r_change' => ':s;#FFFFFF#FF0000`{p:12:変更}',
	'use_magic1' => ':s;#FFFFFF`{p:15:魔法を使い、時間を先へ進めることができます。}',
	'use_magic2' => ':s;#C7C7C7#FFFFFF`{p:15:魔法を使う}',
	'use_magic3' => ':s;#C7C7C7#FFFFFF`{p:15:魔法を使わない}',
	'show_dream1' => ':s;#FFFFFF`{p:15:黄金の夢を思い出しますか？}',
	'show_dream2' => ':s;#C7C7C7#FFFFFF`{p:15:はい}',
	'show_dream3' => ':s;#C7C7C7#FFFFFF`{p:15:あとで}',
	'show_dream4' => ':s;#C7C7C7#FFFFFF`{p:15:疑わずに戦う}',
	'work_in_progress' => ':s;#FFFFFF`{p:8:このエピソード／チャプターは開発中です！}',
	'rmenu_btn_Exit_text' => ':s;#C7C7C7#FF0000`{p:11:メインメニューへ戻る}',
	'sess_real_world' => '現実の時刻',
	'sess_total_clicks' => '総クリック数',
	'sess_total_time' => '総プレイ時間',
	'sess_current_track' => '再生中の曲',
];

$settingLabels = [
	'set_restarted_apply' => ['The game needs to be restarted to apply the changes.', '変更を適用するにはゲームの再起動が必要です。'],
	'set_on' => ['On', 'オン'], 'set_on2' => ['On', 'オン'],
	'set_off' => ['Off', 'オフ'], 'set_off2' => ['Off', 'オフ'],
	'set_game_language' => ['Game language', 'ゲーム言語'],
	'set_game_language_ru' => ['Russian', 'ロシア語'], 'set_game_language_ru_on' => ['Russian', 'ロシア語'],
	'set_game_language_en' => ['English', '英語'], 'set_game_language_en_on' => ['English', '英語'],
	'set_bgm_title_language' => ['BGM title language', 'BGMタイトル言語'],
	'set_bgm_title_language_en' => ['English', '英語'], 'set_bgm_title_language_tl' => ['Russian', 'ロシア語'],
	'set_bgm_title_language_jp' => ['Japanese', '日本語'], 'set_bgm_title_language_rom' => ['Romanized', 'ローマ字'],
	'set_window_size' => ['Window size', 'ウィンドウサイズ'], 'set_window_size_custom' => ['Custom', 'カスタム'],
	'set_display_mode' => ['Display mode', '表示モード'],
	'set_display_mode_win' => ['Windowed', 'ウィンドウ'], 'set_display_mode_win_on' => ['Windowed', 'ウィンドウ'],
	'set_display_mode_full' => ['Fullscreen', 'フルスクリーン'], 'set_display_mode_full_on' => ['Fullscreen', 'フルスクリーン'],
	'set_display_mode_auto' => ['Auto', '自動'], 'set_display_mode_auto_on' => ['Auto', '自動'],
	'set_video_resolution' => ['Video resolution ', '動画解像度 '], 'set_interface' => ['Interface', '操作方法'],
	'set_interface_kb_mouse' => ['KB+Mouse', 'キーボード＋マウス'], 'set_interface_kb_mouse_on' => ['KB+Mouse', 'キーボード＋マウス'],
	'set_object_caching' => ['Object caching', 'オブジェクトキャッシュ'], 'set_render' => ['Preferred renderer', '優先レンダラー'],
	'set_render_1' => ['Error', 'エラー'], 'set_bgm_volume' => ['BGM volume', 'BGM 音量'],
	'set_effect_volume' => ['Effect volume', '効果音 音量'], 'set_voice_volume' => ['Voice volume', '音声 音量'],
	'set_rumble_strength' => ['Rumble strength', '振動の強さ'], 'set_text_speed' => ['Text speed', 'メッセージ表示速度'],
	'set_automode_speed' => ['Automode speed', 'オートモード速度'], 'set_textbox_window' => ['Textbox window', 'ウィンドウタイプ'],
	'set_alchemist_wording_edits' => ['Alchemist wording edits', 'アルケミスト版表現'],
	'set_lip_synchronization' => ['Lip synchronization', 'リップシンク'],
	'set_bgm_chapter_display' => ['BGM/Chapter display', 'BGM／チャプター表示'],
	'set_back' => ['Back', '戻る'], 'set_back_s' => ['Back', '戻る'], 'set_next' => ['Next', '次へ'], 'set_next_s' => ['Next', '次へ'],
	'set_exit' => ['Exit', '終了'], 'set_ctrl' => ['Controls', '操作説明'], 'set_reset' => ['Reset game', 'ゲームをリセット'],
];
foreach ($settingLabels as $name => [$english, $japanese]) {
	$aliases[$name] = str_replace($english, $japanese, menuAlias($englishMenu, $name, $englishMenuPath));
}

// These controls belong to the PC port rather than the PS3 interface, so no
// original console strings exist for them. Keep their Japanese wording here
// instead of inheriting the English locale's visible text.
$portUiLabels = [
	'log_prev' => ['Previous chapter', '前のチャプター'],
	'log_next' => ['Next chapter', '次のチャプター'],
	'log_hint_text' => [
		"To listen to the line's voice, tap on it with one finger, left-click, or press Enter.{n}To jump to a line in-game, tap on it with three fingers, middle-click, or press Z.{n}To quickly scroll the lines, use Page Up/Page Down.",
		'台詞の音声を再生するには、1本指でタップ、左クリック、またはEnterキーを押してください。{n}ゲーム内の台詞へ移動するには、3本指でタップ、中クリック、またはZキーを押してください。{n}履歴をすばやくスクロールするには、Page Up／Page Downキーを使用してください。',
	],
	'tips_hint_text' => [
		'If there are arrows next to the text, you can scroll it using the mouse wheel or two-finger swipe gesture.',
		'文章の横に矢印がある場合は、マウスホイールまたは2本指のスワイプでスクロールできます。',
	],
	'reset_hint_text' => [
		'Would you like to reset the game?{n}This will remove all your save data including the trophies.',
		'ゲームをリセットしますか？{n}トロフィーを含むすべてのセーブデータが削除されます。',
	],
	'jump_hint_text' => [
		'Would you like to jump to this line?{n}This might take time, and you may wish to save first.',
		'この台詞へ移動しますか？{n}時間がかかる場合があります。先にセーブすることをおすすめします。',
	],
	'action_yes' => ['Yes', 'はい'],
	'action_no' => ['No', 'いいえ'],
	'save_btn' => ['Save', 'セーブ'],
	'save_btn_b' => ['Save', 'セーブ'],
	'load_btn' => ['Load', 'ロード'],
	'load_btn_b' => ['Load', 'ロード'],
];
foreach ($portUiLabels as $name => [$english, $japanese]) {
	$aliases[$name] = str_replace($english, $japanese, menuAlias($englishMenu, $name, $englishMenuPath));
}

// The English settings choices are positioned in fixed columns. Give the
// wider Japanese strings explicit column widths so adjacent choices cannot
// extend into one another while retaining their existing click positions.
$settingChoiceColumns = [
	'set_on' => [110, 'オン'], 'set_on2' => [110, 'オン'],
	'set_off' => [110, 'オフ'], 'set_off2' => [110, 'オフ'],
	'set_display_mode_win' => [330, 'ウィンドウ'], 'set_display_mode_win_on' => [330, 'ウィンドウ'],
	'set_display_mode_full' => [320, 'フルスクリーン'], 'set_display_mode_full_on' => [320, 'フルスクリーン'],
	'set_display_mode_auto' => [150, '自動'], 'set_display_mode_auto_on' => [150, '自動'],
	'set_interface_kb_mouse' => [375, 'キーボード＋マウス'], 'set_interface_kb_mouse_on' => [375, 'キーボード＋マウス'],
	'set_interface_dualshock' => [300, 'DualShock'], 'set_interface_dualshock_on' => [300, 'DualShock'],
];
foreach ($settingChoiceColumns as $name => [$width, $text]) {
	$colour = ($name !== 'set_on' && str_ends_with($name, '_on'))
		|| in_array($name, ['set_on2', 'set_off2'], true)
		? '#FFFFFF'
		: '#C7C7C7#FFFFFF';
	$aliases[$name] = ':s;' . $colour . '`{p:8:{w:' . $width . ':}{a:c:}{fit}' . $text . '}';
}

$puzzleLabels = [
	// Exact prompt from Chiru's PS3 cake.txa `msg` layer.
	'ep8_choose_cake' => ['Choose a slice of cake', 'ケーキを選んでください'],
	// Exact labels from Chiru's PS3 murderer.txa: topmenu and decide.
	'ep8_9_menu_1' => ['Re-read story', '物語を再読'], 'ep8_9_menu_2' => ['Display rules', 'ルールの表示'],
	'ep8_9_menu_3' => ['Check purple (by person)', '紫発言確認（人物指定）'],
	'ep8_9_menu_4' => ['Check purple (by chapter)', '紫発言確認（各章指定）'],
	'ep8_9_menu_5' => ['Hints', 'ヒント'], 'ep8_9_menu_6' => ['Identify culprit(s)', '犯人特定'],
	'ep8_9_criminal' => ['Select the culprit(s).', '犯人を選択してください'],
	'ep8_9_criminal_2' => ['Confirm selection', '犯人特定'],
];
foreach ($puzzleLabels as $name => [$english, $japanese]) {
	$aliases[$name] = str_replace($english, $japanese, menuAlias($englishMenu, $name, $englishMenuPath));
}

$japaneseNames = [
	'name_ama' => '天草 十三',
	'name_bea' => 'ベアトリーチェ',
	'name_ber' => 'ベルンカステル',
	'name_but' => '右代宮 戦人',
	'name_cla' => '右代宮 蔵臼',
	'name_enj' => '右代宮 縁寿',
	'name_ev2' => 'エヴァ・ベアトリーチェ',
	'name_eva' => '右代宮 絵羽',
	'name_gap' => 'ガァプ',
	'name_gen' => '呂ノ上 源次',
	'name_geo' => '右代宮 譲治',
	'name_go2' => '山羊の従者たち',
	'name_goa' => '山羊の従者',
	'name_goh' => '郷田 俊朗',
	'name_hid' => '右代宮 秀吉',
	'name_jes' => '右代宮 朱志香',
	'name_kan' => '嘉音',
	'name_kas' => '須磨寺 霞',
	'name_kaw' => '川畑船長',
	'name_kin' => '右代宮 金蔵',
	'name_kir' => '右代宮 霧江',
	'name_ku2' => '熊沢 鯖吉',
	'name_kum' => '熊沢 チヨ',
	'name_lam' => 'ラムダデルタ',
	'name_mar' => '右代宮 真里亞',
	'name_na2' => '南條 雅行',
	'name_nan' => '南條 輝正',
	'name_nat' => '右代宮 夏妃',
	'name_oko' => '小此木 鉄郎',
	'name_pro' => '大月教授',
	'name_rg1' => 'ルシファー',
	'name_rg2' => 'レヴィアタン',
	'name_rg3' => 'サタン',
	'name_rg4' => 'ベルフェゴール',
	'name_rg5' => 'マモン',
	'name_rg6' => 'ベルゼブブ',
	'name_rg7' => 'アスモデウス',
	'name_ron' => 'ロノウェ',
	'name_ros' => '右代宮 楼座',
	'name_rud' => '右代宮 留弗夫',
	'name_s00' => 'シエスタ００',
	'name_s41' => 'シエスタ４１０',
	'name_s45' => 'シエスタ４５',
	'name_s55' => 'シエスタ５５',
	'name_s56' => 'シエスタ５５６',
	'name_sak' => 'さくたろう',
	'name_sha' => '紗音',
	'name_wal' => 'ワルギリア',
	'name_fea' => 'フェザリーヌ',
	'name_zep' => 'ゼパル',
	'name_fur' => 'フルフル',
	'name_rio' => '右代宮 理御',
	'name_wil' => 'ウィラード・Ｈ・ライト',
	'name_cur' => 'クレル',
	'name_fe2' => '八城 幾子',
	'name_bu3' => '八城 十八',
	'name_be3' => 'ベアトリーチェ（姉）',
	'name_be4' => 'ベアトリーチェ',
	'name_en2' => 'エンジェ・ベアトリーチェ',
	'name_en3' => '寿ゆかり',
	'name_eri' => '古戸 ヱリカ',
	'name_dla' => 'ドラノール',
	'name_ger' => 'ガートルード',
	'name_cor' => 'コーネリア',
	'name_non' => '',
];
if (count($japaneseNames) !== 65) {
	throw new RuntimeException('Expected exactly 65 Japanese character-name aliases');
}
foreach ($japaneseNames as $name => $value) {
	$aliases[$name] = $value;
}

// Fill any puzzle aliases that are not derived below from the working English
// menu, including every hint that replaces the locked "????????????" entry.
$englishPuzzleAliases = [
	'ep8_choose_cake',
	'ep8_9_menu_1',
	'ep8_9_menu_2',
	'ep8_9_menu_3',
	'ep8_9_menu_4',
	'ep8_9_menu_5',
	'ep8_9_menu_6',
	'ep8_9_rules',
	'ep8_9_hint_unknown',
];
for ($hint = 1; $hint <= 21; $hint++) {
	$englishPuzzleAliases[] = 'ep8_9_hint_' . $hint;
}
$englishPuzzleAliases[] = 'ep8_9_criminal';
$englishPuzzleAliases[] = 'ep8_9_criminal_2';

foreach ($englishPuzzleAliases as $name) {
	if (!array_key_exists($name, $aliases)) {
		$aliases[$name] = menuAlias($englishMenu, $name, $englishMenuPath);
	}
}
$aliases['ep8_9_hint_unknown'] = '{p:36:????????????}';

// Preserve the wording used in the PS3 scenario's displayed rule list. The
// final narrator rule is stated immediately after that list in the same scene.
// Sprite text ignores the preset's requested line advance, so short invisible
// spacer lines reproduce the PS3 list's vertical distribution reliably.
$ruleLines = [
	'●犯人の定義とは、殺人者のことである。',
	'●犯人はウソをつく可能性がある。',
	'●犯人は殺人以前にもウソをつく可能性がある。',
	'●犯人でない人物は、真実のみを語る。',
	'●犯人でない人物は、犯人に協力しない。',
	'●犯人は全ての殺人を、自らの手で直接行う。',
	'●犯人が死ぬことはない。',
	'●犯人は登場人物の中にいる。',
	'●紫の発言は、赤き真実と同じ価値がある。',
	'　ただし、犯人のみ、紫の発言でウソがつける。',
	'●セリフでないト書き部分に、ウソは存在しない。',
];
$aliases['ep8_9_rules'] = ':s;#FFFFFF`{p:34:' . implode('{n}{p:35:　}{n}', $ruleLines) . '}';
// Exact choice text embedded in the five hint switches in the PS3 Chiru SNR.
$hintTexts = [
	'信用できる紫発言を探せ', '絶対に犯人でないのは誰？', 'ト書きは真実を語る', '南條と朱志香はシロ！',
	'確実な死者をどんどん探せ', '９人の無実', 'マスターキーが使えない？', '赤き真実に違和感？',
	'確実に６人を殺した？', '犯人は閉じ込められた？', '共犯の存在', '紗音は譲治以外の誰にでも殺せる',
	'死んだフリをした犯人がいる。そしてもう１人犯人がいる。', 'さらにもう１人犯人がいる？', '譲治と真里亞はニワトリとタマゴ',
	'真里亞犯人説検証', '譲治犯人説検証', '譲治の紫発言', '大人２人、子供１人。',
	'犯人の３人は家族。', '子供の犯人は戦人',
];
foreach ($hintTexts as $index => $text) {
	// The PS3 list displays a Japanese full stop after declarative hint names,
	// although those stops are absent from the strings stored in the SNR.
	if (preg_match('/[。？！～]$/u', $text) !== 1) {
		$text .= '。';
	}
	$aliases['ep8_9_hint_' . ($index + 1)] = '{p:36:' . $text . '}';
}

$pageHeadings = [
	'ep8_9_page_1' => '第一の晩',
	'ep8_9_page_2' => '第二の晩',
	'ep8_9_page_3' => '第四の晩',
	'ep8_9_page_4' => '第五・六の晩',
	'ep8_9_page_5' => '第七の晩',
	'ep8_9_page_6' => '第八の晩',
];
foreach ($pageHeadings as $name => $text) {
	$aliases[$name] = ':s;#FFFFFF#FFFFFF`{w:645:}{p:13:' . $text . '}';
}

// Exact topic labels transcribed from the Japanese PS3 chapter-selection
// screen captures.
$pageTitles = [
	'ep8_9_page_1_1' => ['Six corpses', '６人の死体', false],
	'ep8_9_page_1_2' => ['Checking the corpses', '検死', false],
	'ep8_9_page_1_3' => ['Locked‐room crime scene?', '現場は密室か', false],
	'ep8_9_page_1_4' => ["Genji's master key", '源次のマスターキー', false],
	'ep8_9_page_1_5' => ['Regarding master keys', 'マスターキーについて', true],
	'ep8_9_page_2_1' => ["Natsuhi and Krauss's room", '夏妃と蔵臼の部屋', false],
	'ep8_9_page_2_2' => ['Checking the room and corpses', '検死と状況', false],
	'ep8_9_page_2_3' => ['Alibis for the servants', '使用人のアリバイ', false],
	'ep8_9_page_2_4' => ['Preserving the crime scene', '現場の保全', false],
	'ep8_9_page_2_5' => ["The mansion's construction", '屋敷の構造', true],
	'ep8_9_page_2_6' => ['Culprit of the first twilight', '第一の晩の犯人', true],
	'ep8_9_page_2_7' => ['Seals on the room', '部屋の封印について', true],
	'ep8_9_page_3_1' => ['Shannon and Kanon vanish', '消えた紗音と嘉音', false],
	'ep8_9_page_3_2' => ['Alibis', 'アリバイ', false],
	'ep8_9_page_3_3' => ["Shannon's master key", '紗音のマスターキー', false],
	'ep8_9_page_3_4' => ['Kanon and the master key', 'マスターキーと嘉音', true],
	'ep8_9_page_4_1' => ['Two corpses', '郷田と熊沢の遺体', false],
	'ep8_9_page_4_2' => ['Alibis', 'アリバイ', false],
	'ep8_9_page_4_3' => ['Preserving the crime scene?', '現場の保全は？', false],
	'ep8_9_page_5_1' => ["Nanjo's corpse", '南條の遺体', false],
	'ep8_9_page_5_2' => ['Is one of the four the culprit?', '４人の中に犯人が？', false],
	'ep8_9_page_6_1' => ["Jessica's corpse", '朱志香の遺体', false],
	'ep8_9_page_6_2' => ['Who killed her?', '誰が殺した？', false],
];
foreach ($pageTitles as $name => [, $text, $red]) {
	$colors = $red ? '#800000#FF0000' : '#C7C7C7#FFFFFF';
	$aliases[$name] = ':s;' . $colors . '`{p:13:{fit}{w:700:}' . $text . '}';
}

$pages = [
	'ep8_9_page_1_1_t' => [
		['郷田', 4, 6], ['熊沢', 7, 8], ['郷田', 9], ['熊沢', 10, 11], ['蔵臼', 12, 13],
	],
	'ep8_9_page_1_2_t' => [
		['朱志香', 16, 17], ['紗音', 18, 19], ['嘉音', 20], ['南條', 21, 22], ['真里亞', 23, 24],
	],
	'ep8_9_page_1_3_t' => [
		['蔵臼', 25], ['戦人', 26], ['譲治', 27], ['蔵臼', 28, 30],
	],
	'ep8_9_page_1_4_t' => [['夏妃', 72]],
	'ep8_9_page_2_1_t' => [['郷田', 90, 91], ['紗音', 92], ['嘉音', 93]],
	'ep8_9_page_2_2_t' => [['戦人', 94, 95], ['南條', 96, 98], ['朱志香', 99, 100]],
	'ep8_9_page_2_3_t' => [['紗音', 105], ['嘉音', 106]],
	'ep8_9_page_2_4_t' => [['真里亞', 114], ['朱志香', 115, 116], ['戦人', 117, 118], ['南條', 119, 120]],
	'ep8_9_page_3_1_t' => [['朱志香', 169], ['熊沢', 170, 171], ['譲治', 172, 173], ['南條', 174]],
	'ep8_9_page_3_2_t' => [['郷田', 175, 176], ['譲治', 177, 178], ['真里亞', 179, 180], ['朱志香', 181, 182], ['戦人', 183, 184]],
	'ep8_9_page_3_3_t' => [['郷田', 185, 186]],
	'ep8_9_page_4_1_t' => [['南條', 209, 210], ['朱志香', 211, 212]],
	'ep8_9_page_4_2_t' => [['戦人', 213, 214], ['譲治', 215, 217], ['真里亞', 218], ['朱志香', 219]],
	'ep8_9_page_4_3_t' => [['譲治', 221], ['南條', 222], ['戦人', 223, 224]],
	'ep8_9_page_5_1_t' => [['朱志香', 237, 238], ['譲治', 239], ['真里亞', 240, 241]],
	'ep8_9_page_5_2_t' => [['朱志香', 243, 244], ['譲治', 245], ['戦人', 246, 247]],
	'ep8_9_page_6_1_t' => [['譲治', 261, 262], ['真里亞', 263, 264]],
	'ep8_9_page_6_2_t' => [['戦人', 265, 266], ['真里亞', 267, 268], ['譲治', 269, 270], ['真里亞', 271, 274]],
];
foreach ($pages as $name => $entries) {
	$text = [];
	foreach ($entries as $entry) {
		$text[] = storyEntry($story, $entry[0], $entry[1], $entry[2] ?? null);
	}
	$aliases[$name] = ':s;#FFFFFF`{p:3:' . implode('{n}{n}', $text) . '}';
}

$statements = [
	'ep8_9_page_1_5_t' => [[49, 50], [57], [63, 64], [66]],
	'ep8_9_page_2_5_t' => [[125], [127]],
	'ep8_9_page_2_6_t' => [[146]],
	'ep8_9_page_2_7_t' => [[152, 154]],
	'ep8_9_page_3_4_t' => [[188, 189]],
];
foreach ($statements as $name => $ranges) {
	$text = [];
	foreach ($ranges as $range) {
		$statement = stripDialogueQuotes(storyText($story, $range[0], $range[1] ?? null));
		$statement = preg_replace('/^…+/u', '', $statement);
		if ($name === 'ep8_9_page_1_5_t' && $range[0] === 49) {
			$statement = str_replace('。もちろん、', '。}{n}{n}{p:1:もちろん、', $statement);
		}
		$closingTags = '';
		while (str_ends_with($statement, '}')) {
			$statement = substr($statement, 0, -1);
			$closingTags .= '}';
		}
		if (!str_ends_with($statement, '。')) {
			$statement .= '。';
		}
		$text[] = $statement . $closingTags;
	}
	$aliases[$name] = ':s;#FFFFFF`{p:3:' . implode('{n}{n}', $text) . '}';
}

$characterSections = [
	'chars_bernquiz_kla_1' => [['第一の晩', [[12, 13], [25], [28, 30]]]],
	'chars_bernquiz_nat_1' => [['第一の晩', [[72]]]],
	'chars_bernquiz_jes_1' => [
		['第二の晩', [[99, 100], [115, 116]]],
		['第四の晩', [[169], [181, 182]]],
		['第五・六の晩', [[211, 212], [219]]],
		['第七の晩', [[237, 238], [243, 244]]],
	],
	'chars_bernquiz_geo_1' => [
		['第一の晩', [[27]]], ['第四の晩', [[172, 173]]], ['第五・六の晩', [[221]]],
		['第七の晩', [[239], [245]]], ['第八の晩', [[261, 262], [269, 270]]],
	],
	'chars_bernquiz_but_1' => [
		['第一の晩', [[26]]], ['第二の晩', [[94, 95], [117, 118]]], ['第四の晩', [[183, 184]]],
		['第五・六の晩', [[213, 214], [223, 224]]], ['第七の晩', [[246, 247]]], ['第八の晩', [[265, 266]]],
	],
	'chars_bernquiz_mar_1' => [
		['第一の晩', [[23, 24]]], ['第二の晩', [[114]]], ['第七の晩', [[240, 241]]],
		['第八の晩', [[263, 264], [267, 268], [271, 274]]],
	],
	'chars_bernquiz_nan_1' => [
		['第一の晩', [[21, 22]]], ['第二の晩', [[96, 98], [119, 120]]],
		['第四の晩', [[174]]], ['第五・六の晩', [[209, 210]]],
	],
	'chars_bernquiz_sha_1' => [['第一の晩', [[18, 19]]], ['第二の晩', [[92], [105]]]],
	'chars_bernquiz_kan_1' => [['第一の晩', [[20]]], ['第二の晩', [[93], [106]]]],
	'chars_bernquiz_goh_1' => [
		['第一の晩', [[4, 6], [9]]], ['第二の晩', [[90, 91]]],
		['第四の晩', [[175, 176], [185, 186]]],
	],
	'chars_bernquiz_kum_1' => [['第一の晩', [[7, 8], [10, 11]]]],
];
foreach ($characterSections as $name => $sections) {
	$text = [];
	foreach ($sections as [$heading, $ranges]) {
		$text[] = storySection($story, $heading, $ranges);
	}
	$aliases[$name] = ':s;#FFFFFF`{p:3:' . implode('{n}{n}', $text) . '}';
}

$menu = file_get_contents($menuPath);
if ($menu === false) {
	throw new RuntimeException("Unable to read $menuPath");
}

foreach ($aliases as $name => $value) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',.*$/m';
	$replacement = 'stralias ' . $name . ',"' . $value . '"';
	$menu = preg_replace_callback($pattern, static fn (): string => $replacement, $menu, 1, $count);
	if ($menu === null || $count !== 1) {
		throw new RuntimeException("Expected exactly one alias named $name");
	}
}

// Keep localized settings positions in menu aliases so the shared settings
// screen needs no language branches.
$numericAliases = [
	'settings_base_off3' => 1037,
	'settings_base_off4' => 1429,
	'settings_base_off5' => 895,
	'settings_base_off6' => 1242,
	'settings_base_off7' => 1579,
];
foreach ($numericAliases as $name => $value) {
	$pattern = '/^numalias ' . preg_quote($name, '/') . ',.*$/m';
	$replacement = 'numalias ' . $name . ',' . $value;
	$menu = preg_replace_callback($pattern, static fn (): string => $replacement, $menu, 1, $count);
	if ($menu === null || $count !== 1) {
		throw new RuntimeException("Expected exactly one numeric alias named $name");
	}
}

// These declarations are inherited from the English locale but have no
// consumers, and their referenced files do not exist in any asset locale.
$deadAliases = [
	'saveload_save_button_b',
	'saveload_load_button',
	'saveload_save_button',
	'saveload_load_button_b',
	'set_song_subtitles',
	'set_song_subtitles_no',
	'set_song_subtitles_trans',
	'set_song_subtitles_rom',
	'set_song_subtitles_both',
	'end_all00_subs',
];
for ($episode = 2; $episode <= 8; $episode++) {
	$deadAliases[] = 'r_click_chapters_' . $episode . '_omake';
}
foreach ($deadAliases as $name) {
	$pattern = '/^stralias ' . preg_quote($name, '/') . ',.*\R?/m';
	$menu = preg_replace($pattern, '', $menu, 1, $count);
	if ($menu === null || $count > 1) {
		throw new RuntimeException("Unable to remove dead alias $name");
	}
}

// Retain aliases required by the shared page, but stub its translation notes.
$menu = preg_replace('/^stralias \w*grim\w*,.*\R?/mi', '', $menu);
preg_match_all('/^stralias \w*grim\w*,.*$/mi', $englishMenu, $grimoireLines);
foreach ($grimoireLines[0] as $line) {
	// The hidden button and caption use the existing one-pixel transparent
	// menu image. Do not retain the button's two-cell tag on a 1px image.
	if (preg_match('/^stralias (r_grim|grim_caption),/', $line, $match)) {
		$line = 'stralias ' . $match[1] . ',":a;graphics\\menu_jp\\empty.png"';
	} elseif (preg_match('/^stralias (r_grim[1-8]_\d+),/', $line, $match)) {
		// Keep two text cells for the shared selection/highlight commands.
		$line = 'stralias ' . $match[1] . ',":s;#C7C7C7#FFFFFF`{p:13: }"';
	} elseif (preg_match('/^stralias (grim_[1-8]_\d+_\d+),/', $line, $match)) {
		// A space renders no visible text but remains a valid text sprite load.
		$line = 'stralias ' . $match[1] . ',":s;#FFFFFF`{p:5: }"';
	}
	$menu .= str_replace('graphics\\menu_en\\', 'graphics\\menu_jp\\', $line) . "\n";
}

if (file_put_contents($menuPath, $menu) === false) {
	throw new RuntimeException("Unable to write $menuPath");
}

echo 'Updated ' . count($aliases) . ' Japanese menu aliases from PS3 metadata and Japanese story text' . PHP_EOL;
