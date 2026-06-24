# NanoMDM 安装与配置

> 项目地址：
> - [NanoMDM](https://github.com/micromdm/nanomdm)
> - [mdmctl](https://github.com/micromdm/micromdm/tree/main/cmd/mdmctl)

运行 NanoMDM 需要 **nanomdm** 与 **mdmctl** 两个组件，均可在上述项目地址下载。mdmctl 可先下载 micromdm 完整包，再从压缩包中找到对应的 `mdmctl` 可执行文件。

## 环境要求

| 项目 | 要求 |
|------|------|
| 操作系统 | 本文以 Linux（amd64）为例 |
| 依赖服务 | 需先部署并运行 SCEP Server（用于设备证书） |
| 网络 | MDM 通信需 **HTTPS**（SCEP 与 MDM 均须启用） |
| 其他 | Apple 开发者账号（用于申请 MDM CSR 与 APNS 证书） |

---

## 安装步骤

### 第 1 步：上传运行文件

将 `nanomdm`、`mdmctl`、`cmdr.py` 上传到服务器。本文示例上传到 `/root/` 目录。

上传完成后，设置可执行权限：

```bash
chmod +x /root/nanomdm-linux-amd64
chmod +x /root/cmdr.py
chmod +x /root/mdmctl
```

> 请根据实际文件名与路径替换上述命令。

### 第 2 步：下载 SCEP 证书

从 SCEP Server 反代地址获取 CA 证书并保存到本地：

```bash
curl 'https://scep.cidumh.com/scep?operation=GetCACert' | openssl x509 -inform DER > /root/ca.pem
```

> `https://scep.cidumh.com/scep?operation=GetCACert` 为 SCEP Server 的 HTTPS 反代地址，请替换为你实际部署的 SCEP 域名。Apple MDM 规范要求 SCEP 与 MDM 均使用 HTTPS。

### 第 3 步：试运行 NanoMDM

```bash
/root/nanomdm-linux-amd64 -ca /root/ca.pem -api nanomdm -debug
```

#### 启动参数说明

| 参数 | 示例值 | 说明 |
|------|--------|------|
| `-ca` | `/root/ca.pem` | SCEP 签发的设备 CA 证书路径 |
| `-api` | `nanomdm` | API 认证密码（用户名默认为 `nanomdm`） |
| `-debug` | — | 开启调试日志（生产环境可省略） |
| `-webhook-url` | — | MDM 事件转发地址（systemd 配置中使用，见下一步） |

确认运行正常后，按 `Ctrl+C` 退出，继续配置 systemd 服务。

---

## 配置 systemd 开机自启

### 第 4 步：创建服务单元

```bash
cat > /etc/systemd/system/nanomdm.service << 'EOF'
[Unit]
Description=NanoMDM
After=network.target

[Service]
User=root
WorkingDirectory=/root
ExecStart=/root/nanomdm-linux-amd64 -ca /root/ca.pem -api nanomdm -webhook-url https://your-ui-domain.com/api/mdm/webhook.php
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
```

> **说明：**
> - `-ca` 指定 SCEP 设备 CA 证书路径
> - `-api nanomdm` 设置 API 认证密码；默认用户名为 `nanomdm`，密码即为 `-api` 指定的值
> - `-webhook-url` 用于将 MDM 收到的数据转发到指定 URL，此处应填写 **NanoMDM UI** 的 Webhook 地址
> - 建议先启动 NanoMDM，再部署 NanoMDM UI，最后将 `-webhook-url` 替换为实际 UI 域名

### 第 5 步：启用并启动服务

```bash
sudo systemctl daemon-reload
sudo systemctl enable nanomdm
sudo systemctl start nanomdm
```

### 查看运行状态

```bash
sudo systemctl status nanomdm
```

### 常用命令

```bash
# 查看运行状态
sudo systemctl status nanomdm

# 停止服务
sudo systemctl stop nanomdm

# 重启服务
sudo systemctl restart nanomdm

# 实时查看日志（排查问题）
journalctl -u nanomdm -f
```

---

## Nginx 反向代理

### 第 6 步：配置 HTTPS 反代

NanoMDM 启动成功后，需通过 Nginx 绑定域名并开启 **HTTPS**。后续设备注册与管理均通过该域名传输数据。

以下示例域名为 `mdm.cidumh.com`，NanoMDM 默认监听端口为 `9000`：

```nginx
location / {
    proxy_pass http://127.0.0.1:9000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 60s;

    proxy_http_version 1.1;
    proxy_set_header Connection "";
}
```

> 端口 `9000` 为 NanoMDM 默认监听端口，启动时也可自定义其他端口，详见 [NanoMDM 项目仓库](https://github.com/micromdm/nanomdm)。

---

## 配置 mdmctl

### 第 7 步：设置 mdmctl 配置

```bash
/root/mdmctl config set \
  -name production \
  -api-token MySecretAPIKey \
  -server-url https://mdm.cidumh.com
```

> 此处配置值可按需填写；mdmctl 运行前必须先完成配置。`-server-url` 请替换为你的 MDM 反代域名。

---

## 创建并签发 PUSH 证书

### 第 8 步：APNS 推送证书完整流程

#### 8.1 创建公钥

```bash
/root/mdmctl mdmcert vendor -password=123456 -country=CN -email=your@email.com
```

> 请将 `-password`、`-email` 替换为实际值。演示密码为 `123456`。

执行后会在 `mdmctl` 所在目录创建 `mdm-certificates` 目录，其中保存证书、公钥与私钥。此步骤生成的是 `mdm.cer` 公钥文件。

#### 8.2 上传公钥到 Apple 开发者后台

1. 打开 [Apple 开发者证书管理页面](https://developer.apple.com/account/resources/certificates/list)
2. 点击 **Certificates → Services → MDM CSR → Continue**
3. 上传刚创建的 `mdm.cer` 公钥
4. 点击 **Continue**，下载 MDM CSR 证书到本地
5. 将下载的 `mdm.cer` 上传到服务器

#### 8.3 创建 APNS 证书公钥和私钥

```bash
/root/mdmctl mdmcert push -password=123456 -country=CN -email=your@email.com
```

#### 8.4 使用 APNS 公私钥签发 MDM CSR，生成 plist 文件

```bash
/root/mdmctl mdmcert vendor -sign -cert=./mdm-certificates/mdm.cer -password=123456
```

| 参数 | 说明 |
|------|------|
| `-password` | 创建公钥时设置的密码 |
| `-cert` | MDM CSR 证书路径 |

签名成功后，`mdm-certificates` 目录下会新增 `.plist` 后缀文件。

#### 8.5 使用 plist 文件申请 APNS 证书

1. 打开 [APNS 证书管理页面](https://identity.apple.com/pushcert/)（需使用 Apple 开发者账号登录）
2. 点击 **Create a Certificate**
3. 勾选协议并点击 **Accept**
4. 选择 plist 文件上传，点击 **Upload**
5. 点击 **Download**，下载 APNS 证书到本地（如 `apns.pem`）
6. 将 APNS 证书上传到服务器

#### 8.6 将 APNS 私钥转换为无密码明文私钥

```bash
openssl rsa \
  -in /root/mdm-certificates/PushCertificatePrivateKey.key \
  -out /root/mdm-certificates/push-decrypted.key
```

> `PushCertificatePrivateKey.key` 为 **8.3** 步骤中生成的私钥文件。

#### 8.7 上传证书到 NanoMDM

```bash
cat /root/mdm-certificates/push.pem /root/mdm-certificates/push-decrypted.key \
  | curl -T - -u nanomdm:nanomdm 'http://127.0.0.1:9000/v1/pushcert'
```

> - `127.0.0.1:9000` 为 NanoMDM 监听地址，也可使用 Nginx 反代后的 HTTPS 域名
> - `nanomdm:nanomdm` 为 API 认证用户名与密码（用户名未指定时默认为 `nanomdm`）
> - 若 APNS 证书文件名为 `apns.pem`，请将命令中的 `push.pem` 替换为实际文件名

执行成功后，响应示例：

```json
{
    "not_after": "2027-01-01T00:00:00Z",
    "topic": "com.apple.mgmt.External.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

**`topic` 即为 APNS 证书 ID**，请务必记录。MDM 注册时的安装描述文件（Enrollment Profile）中的 Topic 须与此一致，否则设备无法收到 MDM 推送指令。

---

## 延伸阅读

如需了解 NanoMDM 的详细功能与扩展 API，请访问项目仓库：

**[https://github.com/micromdm/nanomdm](https://github.com/micromdm/nanomdm)**
