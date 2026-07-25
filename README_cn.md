<p align="center">
    <a href="README.md">
        <img src="https://img.shields.io/badge/LANG-English-blue">
    </a>
    <a href="README_cn.md">
        <img src="https://img.shields.io/badge/语言-简体中文-red">
    </a>
</p>

# CS2 WeaponPaints 配置管理网站

> 面向CS2私人社区服的WeaponPaints皮肤配置管理面板。
> 无需Steam登录，只需Steam64 ID，即可写入对应数据库配置。

## 制作目的

原版WeaponPaints网站通过Steam登录识别玩家，然后把玩家选择的皮肤配置写入WeaponPaints使用的数据库。

登录Steam账号有时会产生安全顾虑以及网络问题，因此我做了这个让玩家自行输入Steam64 ID的网站。

由于移除了Steam登录，它更适合私人CS2社区服和可信任的小型玩家群体。玩家可以直接通过输入Steam64 ID创建或选择配置。

相比原网页，本项目增加了配置系统、网站访问密码、单配置编辑PIN、管理员模式、名称标签、StatTrak™、贴纸编辑、音乐盒选择、探员选择、加载图片兜底和CS2皮肤数据更新工具。

**本项目不需要Steam登录。**

它仍然不是一个面向公开网站的完整账号系统。未启用编辑PIN时，只要能访问网站，用户就可以通过Steam64 ID修改对应配置。因此建议配合HTTPS、网站访问密码和单配置PIN使用。

## 界面

<p align="center">
    <img src="./preview/img/1.png" width="45%">
    <img src="./preview/img/2.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/3.png" width="45%">
    <img src="./preview/img/4.png" width="45%">
</p>

## 功能

### 配置管理

* 通过 Steam64 ID 管理配置，不需要 Steam 登录
* 支持给配置添加备注名，方便识别玩家
* 支持为每个配置单独设置经过哈希保存的编辑 PIN
* 管理员模式可以管理任意配置并重置其 PIN
* 支持全局、T 阵营、CT 阵营三种编辑模式

### 皮肤编辑

* 支持武器、匕首、手套、探员、音乐盒
* 支持磨损、模板、StatTrak™、名称标签
* 支持武器贴纸选择，并提供一键覆盖和一键清除

### 网站和数据

* 支持设置网站访问密码
* 支持电脑和手机访问
* 支持英文和简体中文界面
* 使用本地占位图，远程图片加载成功后自动替换
* 使用来自`steamstatic.com`的图片，以确保大部分地区可以加载图片
* 提供基于 [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) 的CS2皮肤数据更新工具

## 致谢

本网站基于原WeaponPaints网页的数据库工作流和使用方式进行重写。面向私人服务器场景，移除了Steam登录，并增加了配置管理、更多饰品编辑、语言切换和数据更新等功能。

感谢：

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints)：提供CS2 WeaponPaints插件和原始网页工作流。
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API)：提供本项目更新工具使用的CS2饰品数据。

## 支持语言

当前网站支持：

* English (`en`)
* 简体中文 (`zh-CN`)

网站界面和饰品数据都支持语言切换。饰品名称和相关CS2数据来自 [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API)。

目前该数据源提供英文和简体中文数据。如果以后数据源或本项目增加更多语言，也可以继续扩展。

## 运行要求

- 支持PHP的Web服务器
- 已经搭建好的数据库
- 已经搭建好的CS2社区服，并且 [WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) 已连接到同一个数据库

## 快速开始

1. 将本项目文件夹复制到你的Web服务器目录。

   XAMPP 示例：

   ```text
   ...\xampp\htdocs\cs2-WeaponPaints-steamless-website
   ```

2. 编辑 `class/config.php`，设置网页的默认语言以及你的数据库信息。

   ```php
   <?php
   define('DEFAULT_LANGUAGE', 'zh-CN'); // 可用值：en, zh-CN
   define('SITE_ACCESS_PASSWORD', ''); // 填写密码后启用访问保护
   define('ADMIN_PASSWORD', ''); // 留空表示不启用管理员模式
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. 访问网站。

   ```text
   http://your-server/your-folder/
   ```

4. 如果设置了 `SITE_ACCESS_PASSWORD`，首次访问时需要输入访问密码。填写 `ADMIN_PASSWORD` 后会启用管理员按钮；留空则管理员功能保持关闭。

5. 使用Steam64 ID、可选备注名和可选编辑PIN创建配置。

6. 按需选择和编辑皮肤配置。

## 配置 PIN 与管理员模式

该功能不是用户注册系统。它在保留免Steam登录使用方式的同时，通过独立PIN避免某个配置被随意修改。

### 配置 PIN

* 可以在新建配置时启用PIN，也可以稍后在编辑页面的“基础信息”区域启用。
* 启用PIN的配置会在配置列表中显示锁图标和`PIN`标识。
* 打开受保护的配置时需要输入PIN。验证成功后，当前PHP浏览器会话会记住该配置，本次会话中不需要重复输入。
* 在“基础信息”中输入新PIN会替换原PIN；输入框留空则保持当前PIN不变。
* 关闭“启用PIN”并保存基础信息，即可清除该配置的PIN。
* 没有启用PIN的配置继续保持原有的直接进入和保存逻辑。

### 管理员模式

在`class/config.php`中填写`ADMIN_PASSWORD`即可启用管理员模式。管理员按钮位于语言按钮旁边；将`ADMIN_PASSWORD`留空则保持该功能关闭。

管理员登录后可以跳过所有配置PIN，直接进入任意配置，并修改Steam64 ID、备注用户名以及设置、替换或清除任意配置的PIN。管理员状态在当前PHP会话中有效，可以通过同一个管理员弹窗退出。

网站访问密码仍然是第一层保护。配置PIN和管理员模式不会取代`SITE_ACCESS_PASSWORD`。

## 更新 CS2 数据

运行命令行更新工具：

```bash
php tools/update_cs2_data.php
```

只预览，不写入文件：

```bash
php tools/update_cs2_data.php --dry-run
```

只更新皮肤和手套数据：

```bash
php tools/update_cs2_data.php --only=skins
```

远程源 JSON 文件会缓存在：

```text
data/.source_cache/
```

如果缓存文件存在且内容是有效 JSON，更新工具会优先使用缓存，避免重复请求 GitHub raw。如果你想强制从上游数据源重新下载，请删除对应的缓存文件。

更新工具会在以下目录创建备份：

```text
data/backups/
```

## 武器排序

网站和更新工具共用以下文件决定武器卡片顺序：

```text
class/weapon_order.php
```

如果你想调整网页上的武器卡片顺序，可以编辑这个文件。

匕首相关武器仍然可能出现在 `weapon_order.php` 中用于数据排序，但网页会通过专门的匕首卡片显示它们。

## 数据表

本项目会读写 WeaponPaints 的相关表：

* `wp_player_skins`
* `wp_player_knife`
* `wp_player_gloves`
* `wp_player_agents`
* `wp_player_music`

除此之外，网站还会自动创建两个仅供网站使用的辅助表。如果数据库用户有对应权限，它们会在访问网站时自动创建：

* `wp_presets`：保存网站配置列表、备注名以及可选的编辑 PIN 哈希
* `wp_skin_settings_cache`：保存网站侧的单个皮肤设置，例如磨损、模板、StatTrak™、名称标签，方便切换皮肤后继续记住这些设置

网站会先读取 `wp_presets`，再根据选中的Steam64 ID读取和写入WeaponPaints的数据。

网站会自动为已有的 `wp_presets` 表增加 `edit_pin_hash VARCHAR(255) NULL` 字段。只要配置的数据库用户拥有 `ALTER` 权限，就不需要手动执行 SQL；否则请执行：

```sql
ALTER TABLE `wp_presets` ADD `edit_pin_hash` VARCHAR(255) NULL AFTER `nickname`;
```

## 说明

* 贴纸编辑只适用于武器皮肤。大多数武器有4个默认贴纸槽，拥有5个默认贴纸槽的武器会显示5个槽位。
* 网站会优先显示本地占位图，远程图片加载成功后再自动替换。
* 饰品数据存放在`data/`目录中，并通过`tools/update_cs2_data.php`维护。
* 钥匙串和收藏品数据已经预留，用于未来功能扩展。

## 安全说明

本项目面向私人或可信任服务器环境。

配置 PIN 使用 PHP 的 `password_hash()` 保存，并通过 `password_verify()` 验证，无法从数据库中还原 PIN 明文。管理员密码仅保存在服务端的 `class/config.php` 中，不会输出到网页。启用网站密码、配置 PIN 或管理员模式时，强烈建议使用 HTTPS。
