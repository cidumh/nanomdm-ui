# Apple DEP 安装与配置

> 项目地址：
> - [Apple-Dep](https://github.com/cidumh/Apple-Dep)
> - [NanoDEP](https://github.com/micromdm/nanodep)（官方参考实现）

## 项目选择

建议使用 **NanoDEP**，其自动化流程更完整。**Apple Dep** 参考 NanoDEP 编写，功能相近，但不具备「设备自动绑定 JSON 配置文件」能力，仅支持手动绑定。

本文以 **Apple Dep** 为例，演示系统在 **Ubuntu 24.04** 上完成部署。

## 环境要求

| 项目 | 要求 |
|------|------|
| 操作系统 | 本文以 Ubuntu 24.04 为例 |
| Python | 3.x（需已安装 `pip`） |
| 网络 | 若 NanoMDM UI 不在同一台机器或同一局域网，需通过 Nginx 反代对外暴露 |

---

## 安装步骤

### 第 1 步：上传项目文件

将 DEP 程序上传到服务器。本文示例路径为 `/root/dep/`，实际路径可按需调整。

### 第 2 步：进入项目目录

```bash
cd /root/dep/
```

> 请将路径替换为你实际上传项目的位置。

### 第 3 步：安装 Python 依赖

**Linux（Ubuntu / Debian）：**

```bash
python3 -m pip install -r requirements.txt --break-system-packages --ignore-installed
```

**Windows：**

```bash
pip install -r requirements.txt
```

### 第 4 步：试运行服务

```bash
python3 main.py \
  --host 127.0.0.1 \
  --port 9002 \
  --username cdmh \
  --password ciduminghui \
  --token-refresh-interval 3600
```

#### 启动参数说明

| 参数 | 示例值 | 说明 |
|------|--------|------|
| `--host` | `127.0.0.1` | 绑定 IP 地址 |
| `--port` | `9002` | 监听端口 |
| `--username` | `cdmh` | API 认证用户名 |
| `--password` | `ciduminghui` | API 认证密码 |
| `--token-refresh-interval` | `3600` | Token 刷新间隔（秒） |

确认服务正常后，可按 `Ctrl+C` 停止前台进程，继续后续配置。

---

## ABM / ASM 证书交换

### 第 5 步：下载证书并在 ABM/ASM 创建服务

服务启动成功后，访问以下地址下载 PEM 证书：

```text
http://<host>:<port>/api/certificate
```

示例（本地）：

```text
http://127.0.0.1:9002/api/certificate
```

然后前往 **Apple Business Manager（ABM）** 或 **Apple School Manager（ASM）** 后台创建新服务：

1. 进入 **设备 → 管理服务 → 添加设备管理服务**
2. 选择 **连接外部设备管理**
3. 填写服务名，并勾选 **允许此服务解绑设备**
4. 上传刚下载的 PEM 证书
5. 下载令牌到本地（通常为 `.p7m` 格式）

### 第 6 步：上传令牌并解密

将 `.p7m` 令牌上传到服务器 `/root/dep/data/` 目录。

> DEP 服务首次启动后会在项目目录下自动创建 `data` 目录，并将证书保存在该目录中。

**方式一：命令行解密**

```bash
python3 dep/register_token.py
```

**方式二：API 上传解密**

- 接口地址：`http://<host>:<port>/api/token`
- 请求方式：`POST`
- 请求体：令牌文件内容
- 认证：Basic Auth（用户名/密码为启动时指定的值，示例为 `cdmh:ciduminghui`）

示例（本地）：

```text
http://127.0.0.1:9002/api/token
```

**Windows 使用 curl 上传：**

```powershell
curl.exe -u cdmh:ciduminghui -F "file=@C:\path\to\DEP.p7m" http://127.0.0.1:9002/api/token
```

> 请将 `C:\path\to\DEP.p7m` 替换为本地令牌文件的实际路径。

---

## 配置 systemd 开机自启

### 第 7 步：创建服务单元

```bash
cat > /etc/systemd/system/dep_server.service << 'EOF'
[Unit]
Description=DEP Server Service
After=network.target

[Service]
User=root
WorkingDirectory=/root/dep
ExecStart=/usr/bin/python3 main.py --host 127.0.0.1 --port 9002 --username cdmh --password ciduminghui --token-refresh-interval 3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

> 请根据实际情况修改 `WorkingDirectory`、`ExecStart` 中的路径与启动参数。

### 第 8 步：启用并启动服务

```bash
sudo systemctl daemon-reload
sudo systemctl enable dep_server
sudo systemctl start dep_server
```

### 查看运行状态

```bash
sudo systemctl status dep_server
```

### 常用命令

```bash
# 查看运行状态
sudo systemctl status dep_server

# 停止服务
sudo systemctl stop dep_server

# 重启服务
sudo systemctl restart dep_server

# 实时查看日志（排查问题）
journalctl -u dep_server -f
```

---

## Nginx 反向代理

DEP 服务配置完成后，会在后台运行并随系统开机自启。若需供 **NanoMDM UI** 从外网接入管理，可通过 Nginx 绑定域名进行反代。

> 若 NanoMDM UI 与 DEP 在同一台机器或同一局域网内，可直接使用内网地址或本地地址，无需对外暴露。

以下示例假设域名为 `dep.cidumh.com`，DEP 服务监听端口为 `9002`：

```nginx
location / {
    proxy_pass http://127.0.0.1:9002;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

    proxy_http_version 1.1;
    proxy_set_header Connection "";
}
```

保存配置并重载 Nginx 后，访问 `https://dep.cidumh.com/` 即可转发到 DEP 服务。

> `9002` 为 DEP 服务启动时设置的监听端口，请与实际配置保持一致。

---

## 延伸阅读

如需了解 Apple Dep 的详细功能与扩展 API，请访问项目仓库：

**[https://github.com/cidumh/Apple-Dep](https://github.com/cidumh/Apple-Dep)**
