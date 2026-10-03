# Japanese project text for native-speaker review

This document lists Japanese wording written or translated specifically for
this port. It is intended for language review, so repeated normal/selected
sprite states are consolidated. Line breaks shown as `<br>` are intentional.

The following are outside this review list because their wording comes from an
official Japanese source: scenario dialogue, furigana, character and Tips
entries, trophy records, episode and chapter metadata, Bernkastel's puzzle,
pre-existing Japanese BGM titles, and direct PS3 graphic conversions. Copied
English interface graphics, blank compatibility stubs, and the numeric Episode
8 placeholders `81`, `82`, and `83` contain no project-written Japanese.
Unused declarations are also excluded and have blank compatibility sprites:
`set_alchemist_wording_edits` has no active load, while `show_dream1` through
`show_dream4` belong to Omake 3, which is unreachable through the Japanese
title flow. `verification_ancient` is also an empty compatibility alias;
its release-age warning has no active display in the script.

Suggested review points are naturalness, consistency with Umineko's tone,
terminology, punctuation, spacing around Latin text, and whether concise button
labels clearly describe their actions.

## Settings and initial configuration

These strings translate PC-port settings. Official PS3 terminology was used
where an equivalent existed, but their use in this port-specific interface
should still be reviewed.

| Japanese | Intended meaning | Source |
| --- | --- | --- |
| `初期ゲーム設定` | Initial Game Configuration | `script/umi_ftr.txt` |
| `ゲーム言語` | Game language | `set_game_language`; initial configuration |
| `ロシア語` | Russian | `set_game_language_ru*` |
| `英語` | English | `set_game_language_en*`, `set_bgm_title_language_en` |
| `日本語` | Japanese | `set_bgm_title_language_jp`; language selector |
| `BGMタイトル言語` | BGM title language | `set_bgm_title_language` |
| `ローマ字` | Romanized | `set_bgm_title_language_rom` |
| `ウィンドウサイズ` | Window size | `set_window_size`; initial configuration |
| `カスタム` | Custom window size | `set_window_size_custom`; initial configuration |
| `表示モード` | Display mode | `set_display_mode`; initial configuration |
| `ウィンドウ` | Windowed | `set_display_mode_win*`; initial configuration |
| `フルスクリーン` | Fullscreen | `set_display_mode_full*`; initial configuration |
| `自動` | Automatic display mode | `set_display_mode_auto*`; initial configuration |
| `動画解像度` | Video resolution | `set_video_resolution` |
| `操作方法` | Interface/input method | `set_interface`; initial configuration |
| `キーボード＋マウス` | Keyboard + Mouse | `set_interface_kb_mouse*`; initial configuration |
| `オブジェクトキャッシュ` | Object caching | `set_object_caching` |
| `優先レンダラー` | Preferred renderer | `set_render` |
| `エラー` | Renderer error | `set_render_1` |
| `オン` | On | `set_on`, `set_on2` |
| `オフ` | Off | `set_off`, `set_off2`; Config graphic |
| `BGM 音量` | BGM volume | `set_bgm_volume` |
| `効果音 音量` | Effect volume | `set_effect_volume` |
| `音声 音量` | Voice volume | `set_voice_volume` |
| `振動の強さ` | Rumble strength | `set_rumble_strength` |
| `メッセージ表示速度` | Text speed | `set_text_speed` |
| `オートモード速度` | Automode speed | `set_automode_speed` |
| `ウィンドウタイプ` | Textbox window/type | `set_textbox_window` |
| `リップシンク` | Lip synchronization | `set_lip_synchronization` |
| `BGM／チャプター表示` | BGM/Chapter display | `set_bgm_chapter_display` |
| `変更を適用するにはゲームの再起動が必要です。` | The game must restart to apply changes. | `set_restarted_apply` |
| `適用` | Apply | Initial configuration in `script/umi_ftr.txt` |
| `システム設定` | System Settings | Generated `config_main.png` |
| `音響設定` | Sound Settings | Generated `config_main.png` |
| `ゲーム設定` | Game Settings | Generated `config_main.png` |
| `最大` | Maximum volume | Generated `config_main.png` |
| `弱` / `強` | Weak / Strong | Generated `config_main.png` |
| `遅` / `速` | Slow / Fast | Generated `config_main.png` |

Product and renderer names such as `DualShock`, `DirectX`, `OpenGL`, and
`OpenGLES` are unchanged and are not translation candidates.

## Navigation, choices, and port features

| Japanese | Intended meaning | Source alias |
| --- | --- | --- |
| `復活` | Resurrect | `r_resu` |
| `処刑` | Execute | `r_exec` |
| `人間側` | Human side | `r_people*` |
| `魔女側` | Witch side | `r_witches*` |
| `縁寿側` | Ange's side | `r_ange*` |
| `変更` | Change | `r_change` |
| `魔法を使い、時間を先へ進めることができます。` | You may use magic to jump forward in time. | `use_magic1` |
| `魔法を使う` | Cast the spell/use magic | `use_magic2` |
| `魔法を使わない` | Do not cast the spell/use magic | `use_magic3` |
| `はい` | Yes | `action_yes`; generated Yes buttons |
| `このエピソード／チャプターは開発中です！` | This episode/chapter is still under development. | `work_in_progress` |
| `タイトル画面へ戻る` | Back to Main Menu | `rmenu_btn_Exit_text` |
| `現在日時` | Current date and time | `sess_real_world` |
| `総クリック数` | Total clicks | `sess_total_clicks` |
| `総プレイ時間` | Total playtime | `sess_total_time` |
| `再生中の曲` | Current track | `sess_current_track` |
| `戻る` | Back | `set_back*` |
| `次へ` | Next | `set_next*` |
| `終了` | Exit | `set_exit` |
| `前のチャプター` | Previous chapter | `log_prev` |
| `次のチャプター` | Next chapter | `log_next` |
| `セーブ` | Save | `save_btn*` |
| `ロード` | Load | `load_btn*` |
| `操作説明` | Controls | `set_ctrl` |
| `ゲームをリセット` | Reset game | `set_reset` |
| `いいえ` | No | `action_no`; generated No buttons |

## Interaction instructions and confirmations

| Japanese | English intent | Source |
| --- | --- | --- |
| `台詞の音声を再生するには、1本指でタップするか、左クリックするか、Enterキーを押してください。`<br>`この台詞の場面へ移動するには、3本指でタップするか、中クリックするか、Zキーを押してください。`<br>`履歴をすばやくスクロールするには、Page Up／Page Downキーを使用してください。` | Backlog instructions for replaying voice, jumping to a line, and fast scrolling. | `log_hint_text` |
| `文章の横に矢印がある場合は、マウスホイールまたは2本指のスワイプでスクロールできます。` | Tips text can be scrolled when arrows are shown. | `tips_hint_text` |
| `ゲームをリセットしますか？`<br>`トロフィーを含むすべてのセーブデータが削除されます。` | Reset confirmation and data-loss warning. | `reset_hint_text` |
| `この台詞の場面へ移動しますか？`<br>`時間がかかる場合があります。先にセーブすることをおすすめします。` | Line-jump confirmation and save recommendation. | `jump_hint_text` |
| `タイトル画面に戻りますか？` | Return to the title screen? | Generated `SystemBtn/title_bg.png` |
| `ロックを解除しますか？` | Unlock? | Generated `title_menu/unlock_kaku_bg.png` |

## Startup cache and file verification

| Japanese | English intent | Source alias |
| --- | --- | --- |
| `しばらくお待ちください。`<br>`データをキャッシュしています。` | Please wait while data is cached. | `script/jp/caches.txt` |
| `'backgrounds' フォルダーが正しく展開されているか確認してください。一部のファイルにアクセスできません。` | The backgrounds folder may be unpacked incorrectly or inaccessible. | `verify_backgrounds` |
| `'graphics' フォルダーが正しく展開されているか確認してください。一部のファイルにアクセスできません。` | The graphics folder may be unpacked incorrectly or inaccessible. | `verify_graphics` |
| `'sound' フォルダーが正しく展開されているか確認してください。一部のファイルにアクセスできません。` | The sound folder may be unpacked incorrectly or inaccessible. | `verify_sound` |
| `'sprites' フォルダーが正しく展開されているか確認してください。一部のファイルにアクセスできません。` | The sprites folder may be unpacked incorrectly or inaccessible. | `verify_sprites` |
| `'video' フォルダーが正しく展開されているか確認してください。一部のファイルにアクセスできません。` | The video folder may be unpacked incorrectly or inaccessible. | `verify_video` |
| `最新の更新データがすべて適用されているか確認してください。一部のファイルにアクセスできません。` | Recent file updates may be missing. | `verify_updates` |
| `最新のゲームスクリプトとエンジンを使用してください。現在のエンジンとスクリプトには互換性がありません。` | The engine and script versions are incompatible. | `verify_engine` |
| `ゲームファイルの検証` | Game file verification | `verification_title` |
| `ファイルを検証しています……`<br>`数分かかる場合があります。` | Verifying files; this may take several minutes. | `verification_verifying` |
| `凡例: ` | Legend | `verification_legend` |
| `変更されたファイル` | File modified | `verification_modified` |
| `不足しているファイル` | File missing | `verification_missing` |
| `問題ありません！` | All good | `verification_good` |
| `ゲームファイルの検証に失敗しました！` | Game file verification failed. | `verification_bad` |
| `ゲームディレクトリに game.hash がありません！` | `game.hash` is missing. | `verification_nohash` |
| `ゲームディレクトリ内の game.hash は対応していない形式です。` | An unsupported `game.hash` was found. | `verification_unsupported` |
| `ゲームディレクトリの game.hash が古くなっています！` | The installed `game.hash` is outdated. | `verification_outdated` |
| `右クリックで終了！` | Right-click to exit. | `verification_end` |

## Port-only title description panels

The headings remain in English to match the title menu. The Japanese body text
was written to imitate the polite tone of the official PS3 description panels.

### Episodes 1–4

> 過去のゲームを振り返れば、  
> 学ぶべきことは数多くございます。
>
> 新たな視点で盤面を見つめ直した時、  
> 何が見えてくるでしょうか。
>
> お相手を理解されたなら、  
> 今度はチェス盤をひっくり返してみましょう。

### Episodes 5–8

> おはようございます。  
> 物語はいよいよ後半へ。  
> 黄金の魔女が紡ぐ物語は、すでに語り尽くされました。
>
> あとは、それらを理解するだけでございます。
>
> 新たなゲームマスターならば、  
> また違った盤面を見せて下さるかもしれません。

### Exit

> ゲームを終了します。
>
> またのお越しを、心よりお待ちしております。

### Unlock

> このエピソードのロックを解除します。
>
> エピソードを順番通りにお読みにならないと、  
> 物語の理解や楽しみを損なうおそれがあります。
>
> この機能は、それまでのエピソードを  
> すでにプレイされた方のみ  
> ご利用下さいますよう、強くお勧めします。

### Warning

> このエピソードはロックされています。
>
> ロックは、エピソードをクリアするたびに解除されます。  
> まずは前のエピソードを最後までお読み下さい。
>
> ただし、以前のエピソードをすでにプレイ済みの場合は、  
> Unlockボタンでロックを解除できます。

### Official Site

> 07th Expansionの公式サイトへようこそ。
>
> 掲示板では、六軒島で過ごした思い出について  
> 語り合うことができます。
>
> 他の方々の意見に耳を傾ければ、  
> 新たな発見があるかもしれません。
>
> ご自身の考えを披露し、  
> 議論に参加するのもまた一興でしょう。

## Next-episode notice

These lines are newly rendered in `graphics_jp/locale_jp/text006.png`.

| Japanese | Status |
| --- | --- |
| `■新たなゲーム盤の追加` | Project adaptation of the English next-episode heading. |
| `次のエピソードをご用意いたしました。` | Project adaptation of the English next-episode notice. |

The same image also contains `■新要素の追加` and
`新要素については、タイトル画面よりご確認ください。`; those two lines
reuse wording visible in official Japanese `text004.png` and are included here
only to give the reviewer the complete context of the composite.

## Trophy interface

| Japanese | Intended meaning | Source |
| --- | --- | --- |
| `トロフィー` | Trophies | Generated `trophy_caption.png` |
| `未獲得のトロフィーです。` | Trophy is locked. | `trophy_text_locked` |
| `トロフィーを獲得しました！` | A trophy has been earned. | `trophy_text_new` |

The individual trophy titles, descriptions, and ranks are official PS3/PSN
text and are intentionally excluded.
