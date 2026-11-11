=== Smartideo 复刻版 ===
Contributors:      JUYIKEJI
Tags:              video, youtube, vimeo, bilibili, responsive, shortcode
Requires at least: 5.0
Tested up to:      6.7
Stable tag:        1.0.0
License:           MIT
License URI:       https://opensource.org/licenses/MIT

给 WordPress 文章加上在线视频支持：粘贴视频地址就能嵌进来，手机、平板上都是自适应播放。

== Description ==

Smartideo 复刻版是给 WordPress 用的在线视频插件，灵感来自 Fens Liu 的 Smartideo，代码是重新写的。

在文章里写一句短代码，或者直接把视频地址单独放一行，视频就会嵌进来，变成一个 16:9 的自适应播放框，宽度跟着文章走，手机、平板上都能正常播。

支持的站：YouTube、Vimeo、哔哩哔哩、优酷、腾讯视频，以及 mp4 / webm 直链。

不想用短代码也行：文章里单独成行的视频地址，插件会自动转成播放器。不支持的站，可以用 embed 属性直接给嵌进去的地址。

== Installation ==

1. 把 ayan-smartideo 文件夹上传到 wp-content/plugins/，或者在后台 插件 → 安装插件 → 上传插件，选择压缩包。
2. 在插件列表里启用 Smartideo 复刻版。
3. 在文章里写 `[smartideo src="视频地址"]`，或者直接把视频地址单独放一行。
4. 默认宽度、自动播放这些在 设置 → Smartideo 里改。

== Frequently Asked Questions ==

= 支持哪些视频站？ =

YouTube、Vimeo、哔哩哔哩（BV 号和 av 号）、优酷、腾讯视频，以及 mp4 / webm / ogv 直链。

= 视频在手机上能自适应吗？ =

能。播放器是 16:9 的自适应框，宽度跟着文章走，高度按比例算。想改比例可以在短代码里写 ratio="4:3"。

= 不支持的站怎么办？ =

用 embed 属性直接给嵌进去的地址，哪个站的嵌法都行：

`[smartideo embed="https://player.bilibili.com/player.html?bvid=BVxxxx"]`

= 自动播放怎么开？ =

短代码里写 autoplay="1"，或者在 设置 → Smartideo 里打开默认自动播放。浏览器一般要求静音才能自动播，插件会自动加上静音。

== Changelog ==

= 1.0.0 =
* 第一版：短代码、自动嵌视频、设置页。
