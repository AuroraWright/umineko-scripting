# Japanese localization provenance notes

This document records where the Japanese locale's visible graphics and text
come from, including the complete inventory of English menu graphics still
copied into the Japanese locale.

The categories used here are deliberately strict:

- **Direct official asset** means that all visible image and text data comes
  from a Japanese PS3 bitmap or extracted TXA layer. Conversion to PNG,
  resizing to an existing port canvas, or centering is allowed, but no new
  visible content is drawn.
- **Adapted without new content** means that the result crops, splits,
  reorders, or composites official PS3 data and/or an existing port asset.
  Nothing visible is newly illustrated or typeset by this project.
- **Project-generated content** means that this project renders new text or
  pixels. The entry identifies the wording source, font choice, and visual
  reference where applicable.

“Official” below identifies provenance, not a claim that the PC port layout is
identical to the PS3 layout. The builders often adapt official material to the
port's dimensions and sprite-cell conventions.

## Graphics

The reproducible builders are:

- `update-manager/generate-jp-graphics.py` for `graphics_jp/locale_jp`;
- `update-manager/generate-jp-caches.py` for the runtime cache routine;
- `update-manager/generate-jp-menu-graphics.py` for `graphics_jp/menu_jp`, the
  Japanese message windows, and the Japanese cinema logos.

Generated PNGs should be changed through those builders rather than edited in
place.

`graphics_jp` is supplied as an overlay for the ordinary `graphics` tree.
Runtime script paths therefore continue to use `graphics\...`; the overlay
provides the Japanese files at those paths without rewriting the scripts to
refer to `graphics_jp` directly.

### Direct official PS3 assets

#### Scenario graphics in `graphics_jp/locale_jp`

Except for the files listed under the adapted and shared-port sections below,
the files in `graphics_jp/locale_jp` are conversions of complete Japanese PS3 BMP
assets from `jp_graphics_ps3`. The builder may resize a bitmap to the dimensions
of the corresponding established locale file, but it does not draw or replace
any visible content.

This direct-conversion group includes:

- `after_decades.png`, `congra.png`, `enj_mirai01.png`, `ep2_text.png`,
  `hibun.png`, `last[1-2].png`, `m_door5.png`, `oct_*.png`,
  `one_year_later.png`, `purgatorio.png`, `unknown01.png`, and `years_*.png`;
- `alibi/alibi*.png`, `cas/cas_ep5_*.png`, `comment/comment*.png`,
  `dai/dai_*.png`, `mondai/mondai_*.png`, `story/story_list*.png`, and
  `text/text*.png`;
- `ep4last*.png`, `ep8ed_*.png`, `op01*.png`, `op02*.png`, and
  `system[1-5].png`;
- the unsplit ending and end-card assets `end_1c.png`, `end_2a.png`,
  `end_2b.png`, `end_2c.png`, `end_2d.png`, `end_3a.png`, `end_3b.png`,
  `end_3d.png`, `end_4a.png`, `end_4b.png`, `end_5a.png`, `end_5c.png`,
  `end_6a.png`, `end_6b.png`, `end_7a.png`, `end_7c.png`, `end_8a.png`,
  `end_8a_t[1-2].png`, `end_8b.png`, `end_8c.png`, and `reend_1c.png`;
- `text001.png`, `text004.png`, `text005.png`, `text008.png`, `text010*.png`,
  `text011*.png`, and `text012*.png`;
- `goa_memory[1-4].png` and `kakera/kakera_memory[1-10].png`. These are
  complete Japanese PS3 memory screens whose dialogue is already baked into
  the bitmap. The Japanese cache routine captures them without adding another
  text layer.

The individual files `murderer/murderer_thumb_{1,2,4,56,7,8}.png` are direct
conversions of the matching layers in Chiru's `murderer.txa`.

#### Menu and system graphics

The following are extracted from PS3 TXA layers and installed as PNGs. They may
be centered or resized to the established port canvas, but contain no new
project-rendered image or text data:

| Output | Official source |
| --- | --- |
| `graphics_jp/menu_jp/title/title1_text_{start,ep1,ep2,ep3,ep4,load,bgm,cg,chars,config,tea,tips,ura}.png` | Rondo `title1.txa` text layers. |
| `graphics_jp/menu_jp/title/title1_logo.png` | Rondo `title1.txa` `logo`. |
| `graphics_jp/menu_jp/title/chiru/title1_text_{ep5,ep6,ep7,ep8}.png` | Chiru `title1.txa` text layers. |
| `graphics_jp/menu_jp/title/chiru/title1_logo.png` | Chiru `title1.txa` `logo`. |
| `graphics_jp/menu_jp/{bgmmode,cgmode,chars,config,logview,save,tips}/*_caption.png` | Corresponding Chiru TXA `caption` layers. `trophy_caption.png` is excluded and documented below. |
| `graphics_jp/menu_jp/cgmode/{cgmode_logo_chiru,cgmode_logox}.png` | Chiru `cgmode.txa`. |
| `graphics_jp/menu_jp/cgmode/cgmode_logo.png` | Rondo `cgmode.txa`. |
| `graphics_jp/menu_jp/quiz/{m1,m2}.png` | Japanese PS3 medal-notice bitmaps. |
| `graphics_jp/menu_jp/quiz2/{quiz2_back,quiz2_front}.png` | Chiru `last.txa`. |
| `graphics_jp/system/wnd/{msgwnd_jp,msgwnd_ep5_jp}.png` | Rondo and Chiru `msgwnd.txa`; centered in the port canvas when required. |
| `graphics_jp/locale_jp/circle_logo.png` | Rondo `logos/07th.pic`; the Chiru copy is byte-identical. The decoded 1920×1080 image is color-inverted to reproduce the white-on-black presentation used by the port. |

### Adapted without newly generated visible content

These outputs use only crops, splits, reordered cells, or composites of
official PS3 and existing port pixels:

| Output | Source and mechanical adaptation |
| --- | --- |
| `graphics_jp/locale_jp/end_1a_[1-3].png` | Vertical slices of PS3 `end_1a.bmp`. |
| `graphics_jp/locale_jp/end_2a_[1-3].png` | Vertical slices of PS3 `end_2a.bmp`. |
| `graphics_jp/locale_jp/end_2c_[1-2].png` | Vertical slices of PS3 `end_2c.bmp`. |
| `graphics_jp/locale_jp/end_3a_[1-4].png` | Vertical slices of PS3 `end_3a.bmp`. |
| `graphics_jp/locale_jp/end_3b_[1-2].png` | Removes 340 blank rows from PS3 `end_3b.bmp`, then splits it at the port's scrolling boundary. |
| `graphics_jp/locale_jp/end_1c_[1-2].png` | Vertical slices of revised PS3 `reend_1c.bmp`. |
| `graphics_jp/locale_jp/end_8a_small.png` | Resizes PS3 `end_8a.bmp` for the low-memory path and removes the top 540 rows required by the runtime transform. |
| `graphics_jp/menu_jp/quiz/ask_auntie_eva.png` | Crops, reorders, and aligns the two official button states from Chiru `quiz.txa` to the port's normal/hover order. |
| `graphics_jp/menu_jp/save/{saveload_area,saveload_area_5}.png` | Existing English port layouts with the corresponding Japanese Rondo/Chiru PS3 logo composited into the reserved area. |
| `graphics_jp/menu_jp/jump/EP1.png` | The first 17 existing port cells plus a final cell cropped from the already-Japanese PS3 ending art in `end_1a_2.png`. The sheet itself has no PS3 menu equivalent. |
| `graphics_jp/system/logo/cinema_logo_jp.png` | PS3 Rondo `cinema_logo.bmp`, centered on the port canvas. |
| `graphics_jp/system/logo/cinema_logo2_jp.png` | PS3 Chiru `cinema_logo3.bmp`, proportionally reduced and centered on the port canvas. |
| `graphics_jp/locale_jp/project_logo.png` | Existing English port project logo with the bottom-right Witch Hunt translation note removed from the uniform black background. The centered project logo is unchanged. |

The following are reused project assets and contain no newly generated data:

| Output | Existing source and status |
| --- | --- |
| `graphics_jp/menu_jp/empty.png` | Copy of the shared one-pixel transparent stub used for the hidden Grimoire button and caption. |
| `graphics_jp/menu_jp/config/{config_left,config_right}.png` | Existing language-neutral port arrows. |
| `graphics_jp/menu_jp/quiz/quizbtn[1-6].png` | Existing port answer-letter buttons, loaded through a dynamic filename prefix and reused without localization. |

### Copied English menu graphics

This inventory contains 73 active files copied from `graphics/menu_en`, plus
the shared English `graphics/system/loading_en.png` strip. It is not a list of
74 required translations: much of the menu wording is also English in the
Japanese PS3 originals. Matching wording does not make a copied port PNG a
direct PS3 conversion.

| Copied English files used by the Japanese locale | Count | Visible content |
| --- | ---: | --- |
| `SystemBtn/tips.png` | 1 | Tips. |
| `r_click_menu/r_btn_{char,clear,load2,logs,save2,setting,system,tips}.png` | 8 | Normal and selected right-click menu labels. |
| Every `title_menu/*.png` except `unlock_kaku_bg.png`, `yes.png`, and `no.png` | 64 | Port title-menu strips, including episode selectors, Start, Load, Music Box, Tips, Tea Party, Exit, Unlock, Official Site, and new-item variants. |
| `graphics/system/loading_en.png` | 1 | Four-cell English Loading animation shared outside `graphics_jp/menu_jp`. |

The unchanged `config/config_left.png` and `config/config_right.png` files are
language-neutral navigation arrows. The six `quiz/quizbtn[1-6].png` files are
the port's answer-letter buttons. Neither group is a missing translation.

#### Copied wording confirmed in the Japanese PS3 UI

The following wording was checked visually against extracted texture layers
from `data_extract_ps3` (Rondo) and `data_extract_chiru_ps3` (Chiru). Rondo's
`title1.txa` through `title5.txa` and Chiru's `title1.txa` through `title7.txa`
use the same English title-menu terminology.

| Copied Japanese-locale files | Confirmed PS3 wording | Official source |
| --- | --- | --- |
| `SystemBtn/tips.png` | Tips | `sysmenu.txa` `sysmenu`; also `tips.txa` `caption`, where Japanese ティップス appears underneath. |
| `r_click_menu/r_btn_{char,setting,system,tips}.png` | Characters, Config, Title, Tips | `sysmenu.txa` `sysmenu`. Its remaining PS3 menu entry is Bookmark. |
| `title_menu/config*.png` | Config | `title1.txa` `menu` and subsequent title atlases. |
| `title_menu/{start,new_start,tea,new_tea,tips,new_tips,tips_cha,new_tips_cha}.png` | Start, Tea Party, Tips, Characters | `title1.txa` `menu`; its `new` layer supplies the English NEW!! marker. |
| `title_menu/{music,music2,music3,music_1,new_music,new_music_2,new_music_3}.png` | Music Box | `title1.txa` `menu` and `new`. |
| `title_menu/picbox*.png` | Picture Box | `title1.txa` `menu` and `new`. |
| Individual `title_menu/ep1.png` through `ep8.png`, with their existing `_1` and `_new` variants | Episode 1 through Episode 8, NEW!! | `title1.txa` `menu2` and `new`. |
| `title_menu/{ura_tea,new_ura_tea}.png` | ???? | `title1.txa` `menu`; this is a deliberate hidden-entry label. |

Keeping these words in English agrees with the official Japanese interface,
although the actual PNGs remain reused port artwork.

#### Copied wording without an exact PS3 match

These copied English files are port additions or differ from the nearest PS3
control. They were left in English for consistency with other UI elements or
because they were generally deemed to be acceptable.

| English fallback files | PS3 comparison and status |
| --- | --- |
| `r_click_menu/r_btn_{load2,save2}.png` and `title_menu/load.png` | The PS3 calls its combined save/load screen Bookmark. `saveload.txa` `button_saveload` uses Japanese 再開 and 保存, so the port's separate Load/Save labels are different controls. |
| `r_click_menu/r_btn_logs.png` | `logview.txa` `caption` says Message Browser with Japanese メッセージ ブラウザ underneath. |
| `r_click_menu/r_btn_clear.png` | No Clear entry was found in the PS3 `sysmenu` layer. |
| `title_menu/{ep1_4,ep5_8,ep5_8_1}.png` | Combined Episodes 1–4 / Episodes 5–8 selectors are port additions; the PS3 lists individual episodes. |
| `title_menu/exit*.png` | The inspected PS3 title atlases contain no equivalent Exit strips. |
| `title_menu/{omake,unlock,web,trophy_btn_n}.png` | These port controls were not found in the inspected PS3 title-menu layers. |
| `graphics/system/loading_en.png` | No equivalent four-cell Japanese loading strip was found in the inspected PS3 BMP files or the 60 Rondo/Chiru TXA archives. The PS3 `autosave.txa` notice means “autosaving,” not “loading,” and cannot replace it. |

The associated combined-episode and port-only description panels are no
longer English fallbacks: this project renders their Japanese prose as
documented below. Likewise, `SystemBtn/{yes,no,title_bg}.png`,
`title_menu/{yes,no,unlock_kaku_bg}.png`, and `trophy/trophy_caption.png` are
generated Japanese replacements and are not part of the 73 copied files.

A copied English label's absence from the inspected atlases does not establish
the language of every PS3 system dialog; it records only that no exact match
was found in the audited material.

### Project-generated image or text data

#### Next-episode notice

`graphics_jp/locale_jp/text006.png` combines the complete PS3 `chess1.bmp`
background with newly rendered Japanese text for the English port's
next-episode and new-elements notices. `generate_text006` in
`update-manager/generate-jp-graphics.py` uses the supplied
`FOT-Seurat Pro M.otf` at 30 pixels, rendered at four times the resolution
and downsampled without artificial stroke thinning. The Japanese PS3
`text004.png` and `text005.png` are the references for white lettering,
square heading markers, soft dark backing, and roughly 51-pixel line spacing.
The contributor identified Seurat Pro DB as the reference font; the M weight
was selected after comparing renders for this notice. Placement follows the
English `text006.png` lower-right layout.

`■新たなゲーム盤の追加` and `次のエピソードをご用意いたしました。`
are project adaptations of the English unlock notice, written in the courteous
style of those official assets. `■新要素の追加` and
`新要素については、タイトル画面よりご確認ください。` reuse the wording
visible in official `text004.png`. The resulting notice is a project composite,
not an extracted PS3 text006 card; `text004` and `text005` remain direct
official conversions.

#### Chapter cards

`graphics_jp/menu_jp/r_click_menu/chapters/*.png` contains 164 newly rendered port
cards. The 139 numbered chapter titles and dates, and the official labels
`オープニング`, `Tea party`, and `????`, come from the `SECTION_START` and
`CHAPTER` records in `snr/output/script_rondo.xml` and
`snr/output/script_chiru.xml`. `Episode N` follows the terminology visible in
the PS3 title atlases. `おまけ1` is a project label.

The renderer decodes the original PS3 `default.fnt` through
`update-manager/ps3_font.py`, retaining its glyph masks and advance widths.
The 1920×1080 canvas, lower-right placement, line arrangement, and three-pixel
outline are project layout decisions. No PS3 chapter-menu capture was used to
establish those layout choices, so these cards must not be described as a
pixel-perfect reconstruction of that screen.

The chapter-card audit used these references:

| Reference | What it establishes |
| --- | --- |
| Decoded previews from `data_extract_ps3/default.fnt` | Sanity checks for the FNT3 decoder, Japanese and Latin glyphs, spacing, and metrics. These are local renders rather than original-game screenshots. |
| Rondo/Chiru `title1.txa` `menu` and `menu2` layers | Official use of `Episode 1` through `Episode 8`, `Tea Party`, and `????` in the Japanese title UI. These layers do not establish the chapter-card layout. |
| `SECTION_START` records in `snr/output/script_rondo.xml` and `snr/output/script_chiru.xml` | Official chapter names, dates, `オープニング`, `Tea party`, and `????`. This is textual metadata rather than a visual reference. |
| Local review of generated `1_01.png`, `4_19.png`, `8_16.png`, `8_tea.png`, and `8_ura.png` | Readability and placement in the generated port cards only. |

The two extracted fonts, `data_extract_ps3/default.fnt` and
`data_extract_chiru_ps3/default.fnt`, are byte-identical and have SHA-256
`b3cb269e39ede0c93db4974e4fafe7bb8b2bea45fab99502cb92d1ad5f3c6fce`.
The decoder reads all 8,180 FNT3 table entries, compressed four-bit glyph
masks, bearings, and advance widths. This establishes the glyph source, while
the PS3 runtime positioning and blending remain unverified.

#### Config overlay

`graphics_jp/menu_jp/config/config_main.png` is newly rendered for the port's
three-panel configuration layout. Its Japanese headings and endpoint labels
use terminology selected from the Japanese PS3 Config UI where an equivalent
exists, while the port-only structure and positioning are project work.

The text uses scaled glyph masks from the PS3 `default.fnt`. This was chosen to
give the output a deterministic official PS3 glyph source. The baked category
lettering in `config.txa` has visibly different, thinner glyphs, so use of
`default.fnt` does not establish pixel equivalence with the original Config
screen.

#### Title description panels

The following panels contain project translations and newly rendered text:

- `title/chiru/title1_text_ep1_4.png` and
  `title/title1_text_ep5_8.png` translate the English port's combined-episode
  descriptions. No combined-episode panel exists in the PS3 archives.
- `title/title1_text_{exit,unlock,warning,web}.png` translates English port
  panels that have no matching PS3 layer. The Japanese prose was written to
  follow the polite, invitational tone of the official PS3 title descriptions;
  it is not official extracted text.

All six reuse the official PS3 `text_config` translucent dark panel and soft
border. The old text area is cleared to the panel's existing black alpha. Both
headings and descriptions use the supplied `FOT-Greco Std B.otf` at 32 pixels,
chosen because it matches the lettering used by the official PS3 title
description panels. The official `text_config` and individual `text_ep*`
panels were the visual references for placement, color, and tone.

#### Confirmation controls

`graphics_jp/menu_jp/SystemBtn/{yes,no,title_bg}.png` and
`graphics_jp/menu_jp/title_menu/{yes,no,unlock_kaku_bg}.png` contain project
translations of port controls for which no matching PS3 bitmap was found.

- `はい` and `いいえ` use `SHINJIMinchoW7.otf` at 36 pixels, condensed to 85
  percent. It was selected as a Japanese visual substitute for the English
  buttons' New Boston Condensed Bold lettering.
- `タイトル画面に戻りますか？` and `ロックを解除しますか？` both use the
  supplied `FOT-Greco Std B.otf` at 32 pixels, matching the Japanese
  title-description panels. They replace the English Lava Pro Medium
  lettering.

The builder renders at four times the final resolution before downsampling.
It retains the original port canvases, backgrounds, colors, and sprite-cell
order. The copies under `title_menu` follow the duplicated layout used by the
other locales.

#### Trophy caption

`graphics_jp/menu_jp/trophy/trophy_caption.png` retains the existing port pill
background but replaces its text with newly rendered `Trophies` and
`トロフィー`. The Japanese word is a project translation; no corresponding PS3
trophy caption was found.

Both lines use the supplied `FOT-NewCezanne Pro B.otf`. The official PS3-derived
`bgmmode_caption.png` and `cgmode_caption.png` were used as visual references
for tracking, vertical placement, white fill, and black outline.

#### Generated hover treatment

`graphics_jp/menu_jp/quiz2/quiz2_front_2.png` starts with the official Japanese
`last.txa` front layer and programmatically adds the narrow pale outline and
brighter silver fill used by the port's selected Magic/Trick state. The words
themselves are official pixels; the selection effect is generated because the
PS3 archive has no separate hover layer.

### Omitted graphics

The Japanese locale intentionally omits `SystemBtn/grimoire_btn.png` and
`notes/notes_caption.png`, and stubs their aliases with `empty.png`. The unused
`on.png`, `on_1.png`, `off.png`, and `off_1.png` are also omitted.

`graphics_jp/locale_jp/{circle_logo_ga,circle_logo_ga_2}.png` are omitted because
the Golden Abyss credit cards are selected only by the Russian locale.
`graphics_jp/locale_jp/murderer/murderer_thumb_all.png` is also omitted: the
shared murderer screen loads its atlas directly from `graphics/locale`, while
the Japanese locale supplies the individual official PS3 thumbnails.

Only the Grimoire switch button is hidden. Episode 8's Tips button retains its
shared destination and opens the empty Grimoire page; the internal Grimoire
code and aliases remain intact. Blank two-state title sprites preserve the
shared highlight commands.

### Contributing graphics

1. Keep the current path, pixel dimensions, transparency, sprite-cell order,
   and normal/selected state count. Script coordinates assume those layouts.
2. Match terminology used by direct Japanese PS3 graphics when an equivalent
   exists. For a port-only control, document the Japanese wording and the
   source used for its visual style.
3. Add the source or generation step to
   `update-manager/generate-jp-menu-graphics.py`. A PNG placed only in
   `graphics_jp/menu_jp` is deleted by the next rebuild.
4. Rebuild and verify the complete asset set:

   ```sh
   python3 update-manager/generate-jp-graphics.py
   python3 update-manager/generate-jp-caches.py
   python3 update-manager/generate-jp-menu-graphics.py
   php update-manager/verify-jp-assets.php
   python3 update-manager/verify-jp-chapters.py
   ```

5. Check every sprite state in-game. Atlas files often place normal and
   selected cells side by side, so inspecting only the first state can miss
   alignment or clipping errors.

## Text

### Reused official Japanese text

| Text group | Location | Official source and adaptation |
| --- | --- | --- |
| Main Episodes 1–8 | `story/ep[1-8]/jp/*.txt` | Original Japanese PS3 Rondo/Chiru scenario dialogue aligned to the port's scenario line slots. Engine commands and port structure come from the shared scripts; the displayed prose is official Japanese. |
| Dialogue furigana | `update-manager/jp-furigana.json` | Readings extracted from the original Rondo/Chiru dialogue records and matched to dialogue ID plus exact base text. The build restores 4,674 official readings across 4,234 dialogues. Five annotations whose base dialogue/word is absent from the port remain omitted rather than guessed. |
| Character encyclopedia bodies | Generated aliases in `script/jp/menu.txt` | Extracted from the consolidated Japanese tables in official `snr/source/saku.snr`. Ruby markup is converted to the engine's inline format. |
| 34 canonical Tips titles and bodies | Generated aliases in `script/jp/menu.txt` | Extracted from `saku.snr`. Furigana is removed from the list-row title and moved in parentheses to the start of its description so the port list layout remains stable. The wording and readings remain official. |
| Chapter names, dates, and episode titles | Generated aliases in `script/jp/menu.txt` | Extracted from Rondo/Chiru `SECTION_START` and `CHAPTER` metadata. The same data supplies save-slot descriptions and generated chapter cards. Line splitting and formatting are port adaptations. |
| Trophy gallery entries | Generated aliases in `script/jp/menu.txt` | Official Japanese PS3/PSN trophy titles, descriptions, and ranks for Rondo (`NPWR01598`) and Chiru (`NPWR02896`). The port's locked/new notification sentences are separate project UI text. |
| Bernkastel puzzle story passages | Generated aliases in `script/jp/menu.txt` | Reassembled from aligned official Japanese dialogue in `story/ep8/jp/umi8_9.txt`; wording and punctuation are preserved rather than rewritten. |
| Bernkastel puzzle rules and hints | Generated aliases in `script/jp/menu.txt` | Rule wording follows the displayed Chiru PS3 scenario; the 21 hint choices are the exact strings embedded in the Chiru SNR hint switches. |
| Bernkastel puzzle main controls | Generated aliases in `script/jp/menu.txt` | `ケーキを選んでください` comes from Chiru `cake.txa`; the main puzzle menu and culprit-confirmation labels come from Chiru `murderer.txa`. |
| Character names | `script/jp/menu.txt` | Canonical Japanese names and spellings from the work, entered as aliases for the port UI. They are official terminology, although the alias table is project-authored rather than mechanically extracted. |
| Episode 2 Omake | `story/omake/jp/umio2.txt` | Uses corresponding official Japanese PS3 dialogue wherever available. Six parody-song lines replaced in the PS3 version are transcribed from a user-supplied capture of the original Japanese PC release, identified during review as `screenshot_originaljpgameplay.png`; the capture is not stored in this repository. |

The port's pre-existing Japanese BGM titles are retained. They are treated as
existing PC-port localization data; this document does not claim that every
title was extracted from the PS3 release.

### Project-written or project-translated text

The following Japanese has no exact official PS3 source and was written or
translated for this port:

| Text group | Location and rationale |
| --- | --- |
| Port settings and initial configuration | `script/jp/menu.txt`, `script/jp/code.txt`, `script/jp/header.txt`, and the generated Config artwork. These translate PC-port controls such as game language, renderer, window size, object caching, interface mode, textbox style, BGM title language, Alchemist wording edits, and lip sync. Official PS3 terminology is followed where an equivalent exists; port-only choices are project translations. |
| Port interaction hints | `script/jp/menu.txt`. Backlog voice/jump instructions, Tips scrolling, reset confirmation, line-jump warning, save/load buttons, and related Yes/No actions describe mouse, keyboard, and touch behavior absent from the PS3 UI. |
| Port status and navigation | `script/jp/menu.txt` and `script/jp/caches.txt`. Session statistics, previous/next chapter, return to main menu, work-in-progress and restart-required notices, the startup cache message, and similar labels are project translations. |
| Bernkastel puzzle nested navigation | `script/jp/menu.txt`. The PS3 screen selects a chapter directly; the port's additional topic selectors such as `検死`, `屋敷の構造`, and `第一の晩の犯人` are concise project summaries of the official passages they open. The top-level night headings follow the official puzzle divisions. |
| Port-only title prose and confirmation prompts | Baked into the generated title panels and SystemBtn images described above. The source is the English port UI, with Japanese wording adapted to the tone of the official PS3 title prose. |
| Trophy caption and notifications | `トロフィー`, the locked message, and the new-trophy notification are project UI translations. The individual trophy records remain official. |
| Three Episode 8 placeholder Tips | `r_tips_8_1` through `r_tips_8_3` in `script/jp/menu.txt`. These port placeholders have no official PS3 Tips records and retain identifiers `81`, `82`, and `83`. |
| `おまけ1` chapter-card label | Generated chapter card. This is a port label rather than extracted chapter metadata. |
| General locale glue | Remaining visible Japanese in `script/jp/{header,code,credits,menu,prefs,caches}.txt` that is not explicitly identified in the official table above should be treated as project localization or port integration text. This includes messages for behaviors implemented only by the PC port. |

The Grimoire title and body aliases intentionally contain blank text sprites.
These are compatibility stubs, not translations. `story/omake/jp/umio1.txt`
and `umio3.txt` through `umio9.txt` are empty filename-preserving overlays; an
empty overlay leaves the shared base script unchanged. The Japanese title flow
only exposes the restored Episode 2 Omake after Episode 2 is cleared, and does
not route to the untranslated Chiru Omake hub.

### Review rule for future contributions

Every new Japanese string or graphic should identify one of these sources in
its change description:

1. exact official file, archive layer, SNR record, or captured original-PC
   screen;
2. existing port asset used only for layout or compositing;
3. project translation, including the English source and any official
   terminology or visual reference consulted.

If the source is uncertain, record it as project-authored or unverified rather
than describing it as official. Mechanical use of a PS3 font or background
does not make newly written wording official.
