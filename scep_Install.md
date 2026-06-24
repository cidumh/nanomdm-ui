# SCEP Server 安装与配置

> 项目地址：[CDMH-Scep](https://github.com/cidumh/CDMH-Scep)

SCEP Server 用于为设备颁发证书，需要对外提供访问。根据 Apple MDM 规范，SCEP 服务必须使用 **HTTPS**，因此部署前请准备好 **域名** 与 **SSL 证书**。

## 环境要求

| 项目 | 要求 |
|------|------|
| 操作系统 | 本文以 Ubuntu 24.04 为例 |
| Python | 3.11 及以上 |
| 网络 | 需通过 HTTPS 对外暴露（通常配合 Nginx 反向代理） |

---

## 安装步骤

### 第 1 步：上传项目文件

将 SCEP Server 的 Python 项目文件上传到服务器。本文示例上传到 `/root/scep/`，实际路径可按需调整。

### 第 2 步：安装 Python

```bash
apt update
apt install -y python3-pip
```

### 第 3 步：进入项目目录

```bash
cd /root/scep/
```

> 请将路径替换为你实际上传项目的位置。

### 第 4 步：安装 Python 依赖

**Linux（Ubuntu / Debian）：**

```bash
python3 -m pip install -r requirements.txt --break-system-packages --ignore-installed
```

**Windows：**

```bash
pip install -r requirements.txt
```

> `requirements.txt` 为 SCEP Server 项目内的依赖配置文件。

### 第 5 步：试运行服务

```bash
python3 -m scep_server \
  --port 9001 \
  --challenge 123456 \
  --cert-validity 365 \
  --ca-cn "CDMH SCEP" \
  --ca-o "瓷都名汇" \
  --ca-c CN
```

#### 启动参数说明

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `--port` | `9001` | SCEP 服务监听端口 |
| `--host` | `0.0.0.0` | 监听地址 |
| `--depot` | `depot` | CA 证书与私钥存储目录 |
| `--challenge` | 无 | SCEP 注册挑战码；客户端申请证书时必须携带 |
| `--cert-validity` | `365` | 签发给客户端证书的有效期（天） |
| `--ca-cn` | `SCEP CA` | CA 证书通用名称（CN），**仅首次创建 CA 时生效** |
| `--ca-o` | `scep-ca` | CA 证书组织名称（O），**仅首次创建 CA 时生效** |
| `--ca-c` | `CN` | CA 证书国家代码（C），**仅首次创建 CA 时生效** |
| `--capass` | 自动 | CA 私钥 `ca.key` 解密密码；使用 micromdm/scep 默认空密码时可省略 |
| `--debug` | 关闭 | 开启调试日志 |

#### 启动成功标志

终端出现类似以下日志即表示启动成功，程序会自动生成自签名 CA 根证书并写入 `depot` 目录：

```text
... INFO [werkzeug] Press CTRL+C to quit
```

确认无误后，可按 `Ctrl+C` 停止前台进程，继续配置 systemd 后台服务。

---

## 配置 systemd 开机自启

### 6.1 创建服务单元

```bash
cat > /etc/systemd/system/scep_server.service << 'EOF'
[Unit]
Description=SCEP Server Service
After=network.target

[Service]
User=root
WorkingDirectory=/root/scep
ExecStart=/usr/bin/python3 -m scep_server --port 9001 --challenge 123456 --cert-validity 365 --ca-cn "CDMH SCEP" --ca-o "瓷都名汇" --ca-c CN
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

> 请根据实际情况修改 `WorkingDirectory`、`ExecStart` 中的路径与启动参数。

### 6.2 启用并启动服务

```bash
sudo systemctl daemon-reload
sudo systemctl enable scep_server
sudo systemctl start scep_server
```

### 6.3 查看运行状态

```bash
sudo systemctl status scep_server
```

### 常用命令

```bash
# 查看运行状态
sudo systemctl status scep_server

# 停止服务
sudo systemctl stop scep_server

# 重启服务
sudo systemctl restart scep_server

# 实时查看日志（排查问题）
journalctl -u scep_server -f
```

---

## Nginx 反向代理（HTTPS）

SCEP Server 在后台运行后，需通过 Nginx 绑定域名并启用 HTTPS，以便设备从外网申请证书。

以下示例假设域名为 `scep.cidumh.com`，SCEP 服务监听端口为 `9001`：

```nginx
location /scep {
    proxy_pass http://127.0.0.1:9001;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

    proxy_http_version 1.1;
    proxy_set_header Connection "";
}
```

保存配置并重载 Nginx 后，访问 `https://scep.cidumh.com/scep` 即可转发到 SCEP Server。

> **重要：** Apple MDM 规范要求 SCEP 地址使用 HTTPS。域名需绑定受信任 CA 签发的 SSL 证书，并在 Nginx 中开启 HTTPS 支持。

---

## Apple MDM 集成说明

在 Apple MDM 注册流程中，通常需要配置 SCEP 作为证书验证方式。建议启动时设置 `--challenge` 挑战码，因为多数设备的安装描述文件都要求设备身份验证，**挑战码为必填参数**。

---

## 延伸阅读

如需了解 SCEP Server 的详细功能与扩展 API，请访问项目仓库：

**[https://github.com/cidumh/CDMH-Scep](https://github.com/cidumh/CDMH-Scep)**
