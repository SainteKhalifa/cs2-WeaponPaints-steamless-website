<p align="center">
    <a href="README.md"><img src="https://img.shields.io/badge/LANG-English-blue"></a>
    <a href="README_cn.md"><img src="https://img.shields.io/badge/语言-简体中文-red"></a>
</p>

# CS2 WeaponPaints 配置管理网站

> 面向私人 CS2 社区服的中英双语免 Steam 登录配置管理网站。

**本项目不需要 Steam 登录。** 玩家只需使用 Steam64 ID 创建或选择配置，即可在网页中调整饰品。

本项目适合私人服务器和可信任的小型玩家群体，不是面向公开网站的完整用户注册系统。

## 界面

<p align="center">
    <img src="./preview/img/1.png" width="45%">
    <img src="./preview/img/2.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/3.png" width="45%">
    <img src="./preview/img/4.png" width="45%">
</p>

## 主要功能

* 通过 Steam64 ID 管理配置，无需 Steam 登录
* 支持全局、T 阵营和 CT 阵营三种编辑模式
* 支持武器、匕首、手套、探员、音乐盒、CS2 徽章和武器挂件
* 支持磨损、模板、名称标签、StatTrak™ 状态和击杀数
* 每把武器提供 5 个贴纸槽位，支持全部覆盖、全部清除和单槽位磨损、位置、缩放、旋转设置
* 支持挂件模板和 X/Y 偏移设置
* 支持搜索皮肤、贴纸、挂件、音乐盒和徽章
* 支持网站访问密码、单配置 PIN 和管理员模式
* 管理员可以管理和删除所有配置
* 支持英语和简体中文
* 深色响应式界面，并提供本地图片兜底

## 运行要求

* PHP 8.0 或更高版本，并启用 Session 和 PDO MySQL
* MySQL 或 MariaDB
* 已正常运行并连接到同一数据库的 [WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) 插件

建议启用 PHP cURL 和 mbstring。数据库账号应拥有 `SELECT`、`INSERT`、`UPDATE`、`DELETE`、`CREATE` 和 `ALTER` 权限。

## 安装方法

1. 将项目复制到网页服务器的网站根目录，或者服务器已配置的网站目录中。

2. 编辑 `class/config.php`：

   ```php
   <?php
   define('DEFAULT_LANGUAGE', 'zh-CN'); // en 或 zh-CN
   define('SITE_NAME_EN', 'CS2 WeaponPaints Loadout Manager');
   define('SITE_NAME_ZH_CN', 'CS2 WeaponPaints 配置管理器');
   define('SITE_ACCESS_PASSWORD', ''); // 可选的网站访问密码
   define('ADMIN_PASSWORD', ''); // 可选的管理员密码

   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. 访问网站：

   ```text
   http://你的服务器/网站目录/
   ```

只要数据库账号拥有 `CREATE` 和 `ALTER` 权限，网站会自动创建辅助数据表并补充缺少的辅助字段。

## 使用方法

1. 使用 Steam64 ID 和可选的备注用户名新建配置。
2. 可以在新建时为配置启用 PIN。
3. 打开配置并选择全局、T 或 CT 编辑模式。
4. 选择需要的饰品，通过“编辑”调整磨损、模板、StatTrak™、名称标签、贴纸和挂件参数。
5. 在编辑弹窗中按“保存”应用设置。贴纸选择和贴纸高级参数使用各自独立的保存流程。

全局模式会将支持阵营区分的设置同时写入 T 和 CT。音乐盒只在全局模式下管理，探员则分别在 T 和 CT 模式下选择。

### 配置 PIN

* 进入受保护的配置前需要输入 PIN。
* 验证成功后，当前浏览器会话内不需要重复输入。
* 可以在配置顶部的“基础信息”中修改或关闭 PIN。
* PIN 以密码哈希保存，无法从数据库中还原明文。
* 未启用 PIN 的配置可以被其他访问者编辑，对方也可以为其启用 PIN，因此请在分享网站前保护重要配置。

### 管理员模式

在 `class/config.php` 中填写 `ADMIN_PASSWORD` 后，网页右上角会启用管理员按钮。管理员可以跳过配置 PIN、进入任意配置、修改或清除配置 PIN，并删除配置。

**所有配置都只能在管理员模式下删除。**

`SITE_ACCESS_PASSWORD` 仍然是进入网站的第一层保护，与管理员模式和配置 PIN 相互独立。

## 更新 CS2 数据

更新工具从 [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) 获取皮肤、手套、探员、音乐盒、贴纸、挂件和徽章数据。

### 首次运行

右键项目文件夹并复制地址，然后打开命令提示符或 PowerShell，切换到复制的文件夹地址：

```shell
cd "项目文件夹地址"
```

检查 PHP 命令是否可用：

```shell
php -v
```

如果系统提示无法识别 `php` 命令，请安装 PHP 8.0 或更高版本，或者找到网页服务器套件自带的 PHP。将 PHP 可执行文件所在目录添加到系统 `Path`，重新打开终端后再次运行 `php -v`。

更新工具不需要额外安装 PHP 软件包或 Composer 依赖。

运行完整更新：

```bash
php tools/update_cs2_data.php
```

仅预览结果，不写入文件：

```bash
php tools/update_cs2_data.php --dry-run
```

只更新皮肤和手套：

```bash
php tools/update_cs2_data.php --only=skins
```

下载的源文件会保存在 `data/.source_cache/`，该目录已被 `.gitignore` 排除。存在有效缓存时，工具不会再次请求 GitHub；如需获取上游最新数据，请删除对应缓存文件。替换数据前，旧文件会备份到 `data/backups/`。

如果 GitHub 返回 HTTP 429，请等待一段时间后重试。下载失败不会覆盖已有的有效缓存或数据文件。

## 数据库

网站会使用 WeaponPaints 已有的数据表，包括：

* `wp_player_skins`
* `wp_player_knife`
* `wp_player_gloves`
* `wp_player_agents`
* `wp_player_music`
* `wp_player_pins`

网站还会自动创建：

* `wp_presets`：保存配置列表、备注用户名和配置 PIN 哈希
* `wp_skin_settings_cache`：记住不同皮肤在网页侧的独立设置

如果已有的 `wp_presets` 无法被网站自动升级，请手动执行：

```sql
ALTER TABLE `wp_presets` ADD `loadout_password_hash` VARCHAR(255) NULL AFTER `nickname`;
```

## 安全说明

启用密码或 PIN 时请使用 HTTPS。网站访问密码、管理员密码和配置 PIN 的失败验证会按照客户端 IP 进行限流。默认规则是在 30 分钟内失败 5 次后锁定 1 分钟，可通过 `class/config.php` 中的 `AUTH_RATE_LIMIT_*` 设置调整。

所有会修改数据的请求均通过 CSRF 令牌校验进行保护。

本项目面向私人或可信任环境。移除 Steam 登录带来了便利，但网站无法验证访问者的真实 Steam 身份。

## 致谢

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints)：提供 WeaponPaints 插件和原始网页工作流程
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API)：提供更新工具使用的 CS2 饰品数据
