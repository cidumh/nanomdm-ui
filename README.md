# NanoMDM UI

基于 PHP 开发的 NanoMDM Webhook 扩展 UI，提供可视化设备控制面板。目前为 **第 1 版**，适用于轻量级 Apple 设备 MDM 管理场景，支持设备注册、配置下发、指令管控、设备状态监控等功能，可快速搭建私有化移动设备管理平台。

## 版本说明

| 项目 | 说明 |
|------|------|
| 当前版本 | 第 1 版 |
| 适用场景 | 轻量级用户、小规模设备管理 |
| 通信方式 | 基于 NanoMDM Webhook 转发，命令以 XML 指令下发 |
| 后续规划 | DDM 适配（系统更新 / 应用限制 / Wi-Fi 配置等 JSON 响应式策略）、多用户分管模式 |

> 本程序为**前端控制面板**，后端实际运行需依赖 SCEP、DEP、NanoMDM、MySQL、PHP 等多项服务。

## 相关项目

| 组件 | 项目地址 |
|------|----------|
| SCEP Server | [CDMH-Scep](https://github.com/cidumh/CDMH-Scep) |
| Apple DEP | [Apple-Dep](https://github.com/cidumh/Apple-Dep) |
| NanoDEP | [nanodep](https://github.com/micromdm/nanodep) |
| NanoMDM | [nanomdm](https://github.com/micromdm/nanomdm) |
| NanoHub（集成版） | [nanohub](https://github.com/micromdm/nanohub) |

## 前置依赖

部署控制面板前，请先完成以下服务的安装与配置：

| 服务 | 安装文档 |
|------|----------|
| SCEP Server | [SCEP 安装与配置](scep_Install.md) |
| Apple DEP | [DEP 安装与配置](dep_Install.md) |
| NanoMDM | [NanoMDM 安装与配置](nanomdm_Install.md) |

此外还需准备：

- **Nginx** 或其他 Web 服务器
- **PHP** 运行环境（建议 8.2+）
- **MySQL** 数据库（建议 5.7+）

---

## 安装步骤

### 第 1 步：安装 Web 环境与绑定域名

安装 Nginx（或其他 Web 服务器）、PHP、MySQL，并绑定域名。

本文以 **宝塔面板** 安装 Nginx 为例：

1. 进入宝塔面板 **软件商店**
2. 安装以下组件：
   - Nginx 1.31.1
   - PHP 8.2.31
   - MySQL 5.7.44
3. 绑定演示域名：`web.cidumh.com`（请替换为你的实际域名）

### 第 2 步：添加站点并上传源码

1. 宝塔面板 → **网站** → **添加站点**
2. 输入域名，PHP 版本选择 **PHP 8.2**，点击确定
3. 将 PHP 源码上传到站点根目录

#### 禁止访问 config 目录

网站 → 对应站点 **设置** → **配置文件**，加入以下内容：

```nginx
location ^~ /config/ {
    deny all;
    return 403;
}
```

保存后重载并重启 Nginx。

### 第 3 步：创建 MySQL 数据库

在宝塔面板或 MySQL 中创建数据库，记录以下信息供安装时使用：

- 数据库地址（本地一般为 `127.0.0.1`）
- 端口（默认 `3306`）
- 数据库名
- 用户名
- 密码

### 第 4 步：安装网站并初始化

#### 4.1 访问安装页面

```
https://web.cidumh.com/install.php
```

#### 4.2 填写数据库信息

按实际环境填写数据库连接地址、端口、库名、用户名和密码。

#### 4.3 设置管理员账号

填写**管理员用户名**和**管理员密码**（当前版本仅支持单个管理用户）。

#### 4.4 开始安装

点击 **开始安装**。若数据库中已有旧数据，请先清空表和数据再执行安装。

#### 4.5 删除 install.php

安装完成后检查 `install.php` 是否已自动删除；若仍存在，请**手动删除**，避免重复安装或被恶意调用。

#### 4.6 登录面板

访问首页 `index.php`，使用刚创建的管理员账号登录。

---

## 控制面板配置

### 5.1 策略配置

策略配置为设备注册时执行的功能设置，支持：

- 开启激活锁（需 DEP 支持，须先配置 DEP）
- 安装 DNS 代理限制
- 安装全局 HTTP 代理限制
- 安装功能限制

#### 配置标识示例

| 策略项 | 配置标识示例 | 关键字段 |
|--------|--------------|----------|
| DNS 代理 | `com.cidumh.mdm.dns` | `ServerURL`（加密 DNS 地址）、`ServerAddresses`（可选） |
| 全局代理 | `com.cidumh.mdm.qjdl` | `ProxyPACURL`（PAC 文件地址，如 `proxy.pac`） |
| 功能限制 | `com.cidumh.mdm.gnxz` | 见下方注意事项 |

#### 特别注意

**DNS 代理 / 全局代理** 建议务必安装。若不安装，用户可通过修改 DNS 或 HTTP 代理绕过监管流量，导致设备无法被管控。

**功能限制** 中以下选项建议保持**关闭**：

| 选项 | 风险说明 |
|------|----------|
| 允许与电脑配对 | 用户可能通过特定电脑程序连接设备并删除监管配置 |
| 允许创建 VPN | 用户可能通过 VPN 屏蔽与 MDM 的通讯 |
| 允许安装配置描述文件 | 用户可能安装证书或代理类描述文件，修改指令以脱离监管 |
| 允许未配对外部设备引导进入恢复 | 可能导致绕过监管 |

### 5.2 DEP 配置

1. 开启 DEP
2. 填写 DEP API 地址
3. **DEP API Name（服务名）**：使用 NanoDEP 时需填写；使用 Apple DEP 时可不填
4. 填写 DEP API 用户名与密码
5. 保存配置

> SSL 证书验证默认不开启。若需开启，请下载 [CURL CA 证书](https://curl.se/ca/cacert.pem) 并放到 `config` 目录下。

### 5.3 APNS 证书

输入 APNS 证书的 PEM 证书内容与 PEM 私钥内容。APNS 证书用于关闭设备激活锁等功能。

> 证书内容即为 NanoMDM 安装时签发的 `push.pem` 与 `push-decrypted.key`，详见 [NanoMDM 安装与配置](nanomdm_Install.md)。

### 5.4 描述文件配置

描述文件为设备安装监管用的配置描述文件，通过 `profile_install.php` 页面安装。

演示地址：

```
https://web.cidumh.com/profile_install.php
```

#### 基础信息

按页面提示填写即可。

#### MDM 服务

| 字段 | 说明 | 示例 |
|------|------|------|
| **ServerURL** | MDM 注册服务地址，填写 NanoMDM 反代域名 + `/mdm` | `https://mdm.cidumh.com/mdm` |
| **CheckInURL** | 日常命令处理接口；NanoMDM 默认与 MDM 接口不分开，可不填 | — |
| **APNS Topic ID** | APNS 证书 Topic，与 NanoMDM 上传 PUSH 证书时返回的 `topic` 一致 | `com.apple.mgmt.External.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx` |
| **MDM 配置标识** | MDM Payload 的配置标识 | `com.cidumh.mdm.mdm` |

#### 用户协议

可选项。开启后需填写协议内容，用户可在配置页面查看。

#### SCEP（必填）

Apple MDM 默认需要 SCEP 或其他方式进行设备身份验证，**必须开启**。

| 字段 | 说明 | 示例 |
|------|------|------|
| **SCEP URL** | SCEP 服务反代地址 | `https://scep.cidumh.com/scep` |
| **SCEP 挑战码** | 与 SCEP Server 启动时 `--challenge` 一致 | 见 [SCEP 安装与配置](scep_Install.md) |
| **配置标识** | SCEP Payload 的配置标识 | `com.cidumh.mdm.scep` |

填写 **基础信息 / MDM 服务 / SCEP** 并保存后，设备访问 `profile_install.php` 即可生成并安装监管配置描述文件。

### 5.5 MDM 配置

填写 NanoMDM 反代绑定的域名地址，例如：

```
https://mdm.cidumh.com/
```

| 字段 | 说明 |
|------|------|
| API 用户名 | 默认为 `nanomdm`；若启动 NanoMDM 时指定了其他用户名，请填写对应值 |
| API 密码 | 与 NanoMDM 启动参数 `-api` 一致 |

绑定成功后，可在 **发送指令** 中尝试下发指令，确认通讯正常。

### 5.6 DEP 管理

DEP 管理分为 **配置** 与 **设备列表** 两部分。

DEP 绑定的是 ABM 或 ASM 账号。设备抹除还原时，通过 **Apple Configurator 2** 扫描将设备加入组织。

#### 创建 DEP 配置

| 字段 | 说明 |
|------|------|
| 配置文件名称 | 自定义名称 |
| MDM 服务器地址 | 填写 MDM 服务中的 **ServerURL** |
| WEB URL 地址 | 设备下载监管描述文件的页面，即 `profile_install.php` 地址 |
| 监管模式 | 开启后设备抹除还原激活时会重新下载 DEP 配置（即「监管锁」） |
| 等待配置完成 | 开启后设备安装监管描述文件后需等待 MDM 发送配置完成指令 |
| 强制安装 | 建议开启 |
| 可移除配置 | 建议关闭；关闭后用户无法退出或移除监管 |
| 设备序列号 | 可选；填写则指定设备，不填可后续手动绑定 |

**区域与联系**、**跳过系统设置** 按需填写。

---

## 设备加入监管

### 第 6 步：将设备纳入 ABM/ASM 管理

#### 6.1 操作流程概览

1. **二手设备**需先还原抹除；**新/旧设备**均需进入加入 Wi-Fi 页面（激活流程中的网络配置页）
2. 在另一台 iPhone 上打开 **Apple Configurator 2**，登录 ABM/ASM 账号
3. 选择 **设置 → 设备管理服务分配 → 特定 → 选择 DEP 安装时创建的服务**
4. 扫描待监管设备上的 Wi-Fi 图标；设备跳转至图案页面后，用 Configurator 扫描图案
5. 设备加入 ABM/ASM 后会请求抹除还原；抹除期间可在 NanoMDM UI **DEP 管理 → DEP 设备列表** 中刷新并绑定配置文件
6. 设备再次进入加入 Wi-Fi 页面，连接 Wi-Fi 并激活
7. 设备读取 DEP 配置，选择注册到组织，随后打开 **WEB URL** 所指向的安装页面
8. 根据描述文件配置安装监管描述文件；按策略配置安装限制功能并开启激活锁
9. 激活完成后设备显示 **「此 iPhone 受 xxx 监管」**，表示注册成功
10. 之后可在 **设备管理** 中进行远程管控

---

## 架构说明

NanoMDM UI 第 1 版面向轻量场景，设备管理基于 XML 指令。控制面板通过 NanoMDM 的 **Webhook 消息同步 API** 运行，在性能上适合小规模部署。

若需高性能场景，建议：

- 直接对接 ServerURL 注册接口与 CheckInURL 日志指令接口
- 或接入 NanoMDM 数据库层进行数据读写，而非依赖消息同步转发

如需二次开发，可参考 Micromdm 集成方案 **[NanoHub](https://github.com/micromdm/nanohub)**（基于 NanoMDM / NanoCMD / KMFDDM 一体化），更适合深度定制。

---

## 文档索引

| 文档 | 说明 |
|------|------|
| [scep_Install.md](scep_Install.md) | SCEP Server 安装与配置 |
| [dep_Install.md](dep_Install.md) | Apple DEP 安装与配置 |
| [nanomdm_Install.md](nanomdm_Install.md) | NanoMDM 安装与 PUSH 证书配置 |
